<?php
/**
 * ============================================================================
 * PROCESADOR DE LOGIN
 * ============================================================================
 * 
 * Endpoint que procesa el formulario de inicio de sesión.
 * Valida CSRF, autentica usuario y redirige según resultado.
 * 
 * Flujo:
 * 1. Validar token CSRF (previene ataques CSRF)
 * 2. Obtener credenciales del formulario
 * 3. Llamar a login_user() para autenticar
 * 4. Si exitoso: Redirigir a home según rol
 * 5. Si falla: Redirigir a login.php con código de error
 * 
 * Métodos: POST (recibe formulario de login.php)
 * 
 * Parámetros POST:
 * - csrf_token: Token de validación CSRF
 * - username: Nombre de usuario
 * - password: Contraseña
 * 
 * Respuestas:
 * - Éxito: Redirige a index.php o index_operario.php según rol
 * - Error CSRF: Redirige a login.php?error=csrf
 * - Error auth: Redirige a login.php?error=1
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';
require_once PRIVATE_PATH . '/Auth/LoginService.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

// Validar token CSRF antes de procesar
if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
    header('Location: ' . base_url() . '/login.php?error=csrf');
    exit;
}

// Obtener credenciales del formulario
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Intentar autenticación (incluye rate limiting)
$response = login_user($connLogic, $username, $password);

// Verificar resultado y redirigir
if ((int) ($response['CODIGO'] ?? 500) === 200) {
    // Login exitoso: obtener home según rol del usuario
    $target = dedumsoft_role_home(get_session_user());
    header('Location: ' . $target);
    exit;
}

// Login fallido: redirigir con error
header('Location: ' . base_url() . '/login.php?error=1');
exit;
