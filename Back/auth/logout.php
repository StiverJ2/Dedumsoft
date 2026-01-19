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
 * - Invalida token JWT en BD antes de destruir sesión
 * 
 * Flujo:
 * 1. Validar método POST
 * 2. Validar token CSRF
 * 3. Obtener JWT de sesión
 * 4. Marcar token como inválido en BD (estado_token = FALSE)
 * 5. Destruir sesión PHP
 * 6. Redirigir a login
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 */

define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/session.php';

// Solo aceptar POST para prevenir CSRF via URL
if (!validateHttpMethod('POST')) {
    header('Location: ../public/login.php');
    exit;
}

// Validar token CSRF del formulario de logout
if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
    session_destroy();
    header('Location: ../public/login.php?error=csrf');
    exit;
}

// Obtener JWT actual de la sesión
$token = $_SESSION['jwt'] ?? null;

// Invalidar token en base de datos
if ($token) {
    try {
        // Marcar el token como inválido para prevenir reutilización
        $stmt = $connLogic->prepare('UPDATE seguridad.seg_login SET estado_token = FALSE WHERE token = :token');
        $stmt->execute([':token' => $token]);
    } catch (PDOException $e) {
        // Loggear error pero continuar con el logout
        error_log('logout error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

// Destruir sesión PHP y redirigir al login
session_destroy();
header('Location: ../public/login.php');
exit;
