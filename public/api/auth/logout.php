<?php
/**
 * ============================================================================
 * CIERRE DE SESIÓN (LOGOUT)
 * ============================================================================
 * 
 * Endpoint que procesa el cierre de sesión del usuario.
 * Invalida el token JWT en base de datos y destruye la sesión PHP.
 * 
 * Seguridad:
 * - Solo acepta método POST (previene CSRF vía GET)
 * - Valida token CSRF del formulario
 * - Delega invalidación de token y destrucción de sesión a UsuarioController
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/bootstrap.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';
require_once PRIVATE_PATH . '/Controllers/UsuarioController.php';

// Solo aceptar POST para prevenir CSRF via URL
if (!validateHttpMethod('POST')) {
    header('Location: ' . base_url() . '/login.php');
    exit;
}

// Validar token CSRF del formulario de logout
if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
    session_destroy();
    header('Location: ' . base_url() . '/login.php?error=csrf');
    exit;
}

// Obtener JWT actual de la sesión
$token = $_SESSION['jwt'] ?? null;

// Delegar invalidación de token y destrucción de sesión al controller
$ctrl = new UsuarioController($connLogic);
$result = $ctrl->logout($token);

if (!$result['success']) {
    error_log('Logout failed: ' . $result['message']);
}

header('Location: ' . base_url() . '/login.php');
exit;
