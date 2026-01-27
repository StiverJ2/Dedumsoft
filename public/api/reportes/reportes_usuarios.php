<?php
/**
 * ============================================================================
 * API REST: REPORTE DE USUARIOS
 * ============================================================================
 * 
 * Endpoint para obtener el listado de usuarios del sistema.
 * Muestra información básica de la tabla seguridad.usuarios.
 * 
 * Métodos soportados:
 * - GET: Obtener listado de usuarios
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 * 
 * Parámetros: Ninguno
 * 
 * Datos retornados:
 * - id_usuario: ID único del usuario
 * - username: Nombre de usuario para login
 * - nombre: Nombre completo del usuario
 * - rol: Rol asignado (Administrador, Supervisor, etc.)
 * - activo: Estado de activación
 * - created_at: Fecha de creación del usuario
 * 
 * @package Dedumsoft\API\Reportes
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

// Solo aceptar GET
if (!validateHttpMethod('GET')) {
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(4); // Menú: Reportes

try {
    // Llamar función de reporte de usuarios (esquema seguridad)
    $stmt = $connLogic->prepare(
        'SELECT id_usuario, username, nombre, rol, activo, created_at FROM seguridad.fun_reporte_usuarios()'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_usuarios error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
