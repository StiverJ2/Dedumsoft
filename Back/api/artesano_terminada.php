<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE PIEZA TERMINADA (ARTESANO)
 * ============================================================================
 * 
 * Endpoint para registrar la finalización de una pieza de joyería.
 * Captura peso final, tiempo real de producción y calidad.
 * 
 * Métodos soportados:
 * - POST: Registrar pieza terminada con sus métricas
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 3 (Producción)
 * 
 * Datos capturados:
 * - Peso final de la pieza (comparación con materiales consumidos)
 * - Tiempo real de producción (para análisis de eficiencia)
 * - Nivel de calidad (evaluación del producto)
 * - Observaciones del artesano
 * 
 * Resultados calculados:
 * - Costo de materiales consumidos
 * - Eficiencia del artesano
 * - Merma de material
 * 
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Solo aceptar POST
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(3); // Menú: Producción

// =============================================================================
// POST: Registrar pieza terminada
// =============================================================================
// Body JSON:
//   - orden_id (int, requerido): ID de la orden de producción
//   - peso_final (float, requerido): Peso final de la pieza en gramos (> 0)
//   - tiempo_real (float, opcional): Tiempo real de producción en horas
//   - calidad_id (int, opcional): ID del nivel de calidad
//   - observaciones (string, opcional): Notas del artesano
//
// Respuesta exitosa: 
//   { CODIGO: 201, MENSAJE: '...', ID: <creacion_id>, COSTO_MATERIALES: <float> }

// Leer y validar JSON del body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
    exit;
}

// Extraer y validar campos
$orden_id = isset($input['orden_id']) ? (int) $input['orden_id'] : 0;
$peso_final = isset($input['peso_final']) ? (float) $input['peso_final'] : 0;
$tiempo_real = isset($input['tiempo_real']) && $input['tiempo_real'] !== '' ? (float) $input['tiempo_real'] : null;
$calidad_id = isset($input['calidad_id']) && $input['calidad_id'] !== '' ? (int) $input['calidad_id'] : null;
$observaciones = $input['observaciones'] ?? null;

// Validaciones de campos requeridos
if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'orden_id es requerido.']);
    exit;
}

if ($peso_final <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'peso_final debe ser mayor a 0.']);
    exit;
}

try {
    // Llamar función de registro de pieza terminada
    // La función calcula costos, actualiza estado y genera métricas
    $stmt = $connLogic->prepare(
        'SELECT success, mensaje, creacion_id, costo_materiales FROM fun_registrar_pieza_terminada(:orden_id, :peso_final, :tiempo_real, :calidad_id, :observaciones)'
    );
    $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
    $stmt->bindValue(':peso_final', $peso_final, PDO::PARAM_STR);
    $stmt->bindValue(':tiempo_real', $tiempo_real, $tiempo_real === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':calidad_id', $calidad_id, $calidad_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':observaciones', $observaciones, $observaciones === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si la operación fue exitosa
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => $result['mensaje']]);
        exit;
    }
} catch (PDOException $e) {
    error_log('artesano_terminada POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

// Retornar resultado exitoso con datos de la pieza creada
echo json_encode([
    'CODIGO' => 201,
    'MENSAJE' => $result['mensaje'],
    'ID' => $result['creacion_id'],
    'COSTO_MATERIALES' => $result['costo_materiales']  // Costo calculado de materiales consumidos
]);
