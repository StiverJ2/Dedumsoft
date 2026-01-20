<?php
/**
 * ============================================================================
 * API REST: TIPOS DE MAQUINARIA
 * ============================================================================
 * 
 * Endpoint para obtener los tipos de maquinaria disponibles.
 * Usado como catálogo auxiliar para formularios de inventario.
 * 
 * Métodos soportados:
 * - GET: Listar tipos de maquinaria
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 2 (Inventario)
 * 
 * Parámetros:
 * - activo (bool): Filtrar por estado activo (default: true)
 * 
 * Datos retornados:
 * - id: ID del tipo
 * - codigo: Código único del tipo
 * - nombre: Nombre descriptivo
 * - descripcion: Descripción del tipo de maquinaria
 * - activo: Estado de activación
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Solo aceptar GET
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(2); // Menú: Inventario

// =============================================================================
// GET: Listar tipos de maquinaria
// =============================================================================
// Parámetros:
//   - activo (bool): Filtrar por estado (default: true)
//
// Respuesta: { CODIGO: 200, DATOS: [...] }

// Parsear parámetro de estado activo
$activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($activo === null) {
    $activo = true;
}

try {
    // Llamar función de obtención de tipos de maquinaria
    $stmt = $connLogic->prepare(
        'SELECT id, codigo, nombre, descripcion, activo FROM fun_obtener_tipos_maquinaria(:activo)'
    );
    $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('tipos_maquinaria GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
