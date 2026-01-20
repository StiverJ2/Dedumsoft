<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE CONSUMO DE MATERIALES (ARTESANO)
 * ============================================================================
 * 
 * Endpoint para registrar el consumo de materiales en una orden de producción.
 * Permite a los artesanos declarar qué materiales utilizaron y en qué cantidad.
 * 
 * Métodos soportados:
 * - POST: Registrar consumo de material (oro o insumo)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 3 (Producción)
 * 
 * Tipos de material soportados:
 * - 'oro': Consumo de oro del inventario
 * - 'insumo': Consumo de insumos/materiales varios
 * 
 * Impacto en inventario:
 * - Descuenta automáticamente del inventario correspondiente
 * - Registra trazabilidad del consumo por orden y usuario
 * 
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

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
// POST: Registrar consumo de material
// =============================================================================
// Body JSON:
//   - orden_id (int, requerido): ID de la orden de producción
//   - tipo_material (string, requerido): 'oro' o 'insumo'
//   - material_id (int, requerido): ID del material en su tabla correspondiente
//   - cantidad (float, requerido): Cantidad consumida (> 0)
//
// Respuesta exitosa: { CODIGO: 201, MENSAJE: '...', ID: <consumo_id> }
// Error de inventario: { CODIGO: 400, MENSAJE: 'Stock insuficiente' }

// Leer y validar JSON del body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
    exit;
}

// Extraer y validar campos requeridos
$orden_id = isset($input['orden_id']) ? (int) $input['orden_id'] : 0;
$tipo_material = $input['tipo_material'] ?? '';
$material_id = isset($input['material_id']) ? (int) $input['material_id'] : 0;
$cantidad = isset($input['cantidad']) ? (float) $input['cantidad'] : 0;

// Validaciones de campos
if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'orden_id es requerido.']);
    exit;
}

// Validar tipo de material contra lista blanca
if (!in_array($tipo_material, ['oro', 'insumo'])) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'tipo_material debe ser "oro" o "insumo".']);
    exit;
}

if ($material_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'material_id es requerido.']);
    exit;
}

if ($cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'cantidad debe ser mayor a 0.']);
    exit;
}

// Obtener usuario actual para auditoría
$user = get_session_user();
$usuario_id = $user['id'] ?? null;

try {
    // Llamar función de registro de consumo
    // La función valida stock disponible y descuenta del inventario
    $stmt = $connLogic->prepare(
        'SELECT success, mensaje, consumo_id FROM fun_registrar_consumo_material(:orden_id, :tipo_material, :material_id, :cantidad, :usuario_id)'
    );
    $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
    $stmt->bindValue(':tipo_material', $tipo_material, PDO::PARAM_STR);
    $stmt->bindValue(':material_id', $material_id, PDO::PARAM_INT);
    $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_STR);
    $stmt->bindValue(':usuario_id', $usuario_id, $usuario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si la operación fue exitosa
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => $result['mensaje']]);
        exit;
    }
} catch (PDOException $e) {
    error_log('artesano_consumo POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

// Retornar resultado exitoso con ID del consumo registrado
echo json_encode(['CODIGO' => 201, 'MENSAJE' => $result['mensaje'], 'ID' => $result['consumo_id']]);
