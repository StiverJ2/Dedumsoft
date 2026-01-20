<?php
/**
 * ============================================================================
 * API: RECUPERACIÓN DE CONTRASEÑA
 * ============================================================================
 * 
 * Endpoints para el sistema de recuperación de contraseña.
 * 
 * Métodos:
 * - POST /request: Solicitar token de recuperación (envía email)
 * - POST /validate: Validar si un token es válido
 * - POST /reset: Cambiar contraseña con token válido
 * 
 * Seguridad:
 * - Rate limiting en solicitudes
 * - Tokens con expiración de 1 hora
 * - No revela si el email existe (previene enumeración)
 * - Invalida sesiones activas al cambiar contraseña
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

// Cargar dependencias
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Http/SecurityLogger.php';
require_once PRIVATE_PATH . '/Mail/Mailer.php';
require_once PRIVATE_PATH . '/Auth/RateLimiter.php';

header('Content-Type: application/json; charset=UTF-8');


if (!validateHttpMethod('POST')) {
    exit;
}

// Obtener acción del query string
$action = $_GET['action'] ?? '';

// Leer body JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'request':
            handleRequestReset($connLogic, $input);
            break;

        case 'validate':
            handleValidateToken($connLogic, $input);
            break;

        case 'reset':
            handleResetPassword($connLogic, $input);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    error_log('password_reset error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}

/**
 * Solicitar token de recuperación de contraseña.
 * Genera token y envía email con enlace de recuperación.
 */
function handleRequestReset(PDO $conn, array $input): void
{
    // Rate limiting: máximo 5 solicitudes por IP cada 15 minutos
    $rate = check_rate_limit(5, 900);
    if (!$rate['allowed']) {
        dedumsoft_log_rate_limited($rate['count'] ?? 0);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Demasiadas solicitudes. Intenta en ' . ceil($rate['retry_after'] / 60) . ' minutos.'
        ]);
        return;
    }

    $email = trim($input['email'] ?? '');

    // Validar formato de email
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email inválido']);
        return;
    }

    // Obtener IP y User-Agent
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip) {
        $ip = explode(',', $ip)[0];
    }
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    // Llamar función de BD
    $stmt = $conn->prepare(
        'SELECT codigo, mensaje, token, usuario_id, nombre 
         FROM seguridad.fun_crear_reset_token(:email, :ip::inet, :ua)'
    );
    $stmt->execute([
        ':email' => $email,
        ':ip' => $ip,
        ':ua' => $user_agent
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Log del intento
    dedumsoft_security_log('PASSWORD_RESET_REQUEST', $email, $result['token'] ? 'Token generated' : 'Email not found');

    // Siempre responder igual al usuario (no revelar si email existe)
    $response = [
        'success' => true,
        'message' => 'Si el email existe en nuestro sistema, recibirás instrucciones para recuperar tu contraseña.'
    ];

    // Si hay token, enviar email
    if ($result['token']) {
        // Construir URL de reset
        $siteUrl = ENV['SITE_URL'] ?? getBaseUrl();
        $reset_link = $siteUrl . '/public/reset_password.php?token=' . $result['token'];

        // Enviar email con PHPMailer
        $emailResult = send_password_reset_email(
            $email,
            $result['nombre'] ?? 'Usuario',
            $result['token'],
            $reset_link
        );

        // Log resultado del envío
        if ($emailResult['success']) {
            dedumsoft_security_log('PASSWORD_RESET_EMAIL_SENT', $email, 'Email sent successfully');
        } else {
            dedumsoft_security_log('PASSWORD_RESET_EMAIL_FAILED', $email, $emailResult['error'] ?? 'Unknown error');
            error_log('[DEDUMSOFT] Error enviando email de reset a ' . $email . ': ' . ($emailResult['error'] ?? 'Unknown'));
        }

        // En desarrollo, incluir info de debug
        if (!(ENV['PROD'] ?? false)) {
            $response['dev_email_sent'] = $emailResult['success'];
            $response['dev_email_error'] = $emailResult['error'];
            $response['dev_link'] = $reset_link;
        }
    }

    echo json_encode($response);
}

/**
 * Validar si un token es válido (sin usarlo).
 */
function handleValidateToken(PDO $conn, array $input): void
{
    $token = trim($input['token'] ?? '');

    if ($token === '' || strlen($token) !== 64) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        return;
    }

    $stmt = $conn->prepare(
        'SELECT codigo, mensaje, usuario_id, username, nombre 
         FROM seguridad.fun_validar_reset_token(:token)'
    );
    $stmt->execute([':token' => $token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int) $result['codigo'] !== 200) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['mensaje']]);
        return;
    }

    echo json_encode([
        'success' => true,
        'username' => $result['username'],
        'nombre' => $result['nombre']
    ]);
}

/**
 * Cambiar contraseña usando token válido.
 */
function handleResetPassword(PDO $conn, array $input): void
{
    $token = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';
    $password_confirm = $input['password_confirm'] ?? '';

    // Validaciones
    if ($token === '' || strlen($token) !== 64) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        return;
    }

    if ($password === '' || strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
        return;
    }

    if ($password !== $password_confirm) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Las contraseñas no coinciden']);
        return;
    }

    // Validar fortaleza de contraseña
    $strength_error = validatePasswordStrength($password);
    if ($strength_error) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $strength_error]);
        return;
    }

    // Hash de la nueva contraseña
    $hash = password_hash($password, PASSWORD_ARGON2ID);

    // Cambiar contraseña
    $stmt = $conn->prepare(
        'SELECT codigo, mensaje FROM seguridad.fun_reset_password(:token, :hash)'
    );
    $stmt->execute([':token' => $token, ':hash' => $hash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int) $result['codigo'] !== 200) {
        dedumsoft_security_log('PASSWORD_RESET_FAILED', null, $result['mensaje']);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['mensaje']]);
        return;
    }

    dedumsoft_security_log('PASSWORD_RESET_SUCCESS', null, 'Password changed via reset token');

    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada exitosamente. Ya puedes iniciar sesión.'
    ]);
}

/**
 * Validar fortaleza de contraseña.
 * Requiere: 8+ caracteres, mayúscula, minúscula, número.
 */
function validatePasswordStrength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'La contraseña debe tener al menos una letra mayúscula';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'La contraseña debe tener al menos una letra minúscula';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'La contraseña debe tener al menos un número';
    }
    return null;
}

/**
 * Obtener URL base del sitio.
 */
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
    return $protocol . '://' . $host . $path;
}