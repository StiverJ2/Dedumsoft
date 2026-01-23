<?php
/**
 * ============================================================================
 * API REST: USUARIOS (ACTIVAR/DESACTIVAR)
 * ============================================================================
 * 
 * Endpoint para activar o desactivar usuarios del sistema.
 * Usa soft-delete en seguridad.seg_usuario (deleted_at).
 * 
 * Métodos soportados:
 * - PATCH: Actualizar estado activo/inactivo
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 5 (Usuarios)
 * 
 * Entrada JSON (PATCH):
 * - id (int, requerido): ID del usuario
 * - activo (bool, requerido): true=activar, false=desactivar
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(5); // Menú: Usuarios

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int) $input['id'] : 0;
$activo_raw = $input['activo'] ?? null;

$activo = null;
if ($activo_raw !== null) {
    $activo = filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

if ($id <= 0 || $activo === null) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID y estado son requeridos.']);
    exit;
}

$session_user = get_session_user();
if (!empty($session_user) && (int) ($session_user['id_usuario'] ?? 0) === $id && $activo === false) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'No puedes desactivar tu propio usuario.']);
    exit;
}

try {
    $stmt = $connLogic->prepare('SELECT seguridad.fun_actualizar_usuario_estado(:id, :activo)');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('usuarios PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
    http_response_code($code);
    echo json_encode([
        'CODIGO' => $code,
        'MENSAJE' => $code === 404 ? 'Usuario no encontrado.' : 'Error interno del servidor.'
    ]);
    exit;
}

echo json_encode([
    'CODIGO' => 200,
    'MENSAJE' => $activo ? 'Usuario activado.' : 'Usuario desactivado.'
]);
