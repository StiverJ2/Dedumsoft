<?php
/**
 * ============================================================================
 * API: RECUPERACION DE CONTRASENA
 * ============================================================================
 *
 * Endpoint HTTP para solicitar, validar y ejecutar reset de contrasena.
 * La logica de negocio vive en Auth\PasswordResetService.
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/bootstrap.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/PasswordResetService.php';

header('Content-Type: application/json; charset=UTF-8');

if (!validateHttpMethod('POST')) {
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$service = new PasswordResetService($connLogic);

try {
    switch ($action) {
        case 'request':
            $siteUrl = ENV['SITE_URL'] ?? dedumsoft_password_reset_base_url();
            $result = $service->request(
                trim($input['username'] ?? ''),
                trim($input['email'] ?? ''),
                $siteUrl,
                !(ENV['PROD'] ?? false)
            );
            dedumsoft_password_reset_json($result);
            break;

        case 'validate':
            $result = $service->validateToken(trim($input['token'] ?? ''));
            dedumsoft_password_reset_json($result);
            break;

        case 'reset':
            $result = $service->reset(
                trim($input['token'] ?? ''),
                $input['password'] ?? '',
                $input['password_confirm'] ?? ''
            );
            dedumsoft_password_reset_json($result);
            break;

        default:
            dedumsoft_password_reset_json([
                'success' => false,
                'code' => 400,
                'message' => 'Accion no valida.',
            ]);
    }
} catch (Exception $e) {
    error_log('password_reset error: ' . $e->getMessage());
    dedumsoft_password_reset_json([
        'success' => false,
        'code' => 500,
        'message' => 'Error interno del servidor',
    ]);
}

/**
 * Emite respuesta JSON estandar para reset de contrasena.
 *
 * @param array<string, mixed> $result
 * @return void
 */
function dedumsoft_password_reset_json(array $result): void
{
    $code = (int) ($result['code'] ?? 500);
    http_response_code($code);

    $payload = [
        'CODIGO' => $code,
        'MENSAJE' => $result['message'] ?? 'Error interno del servidor',
    ];
    if (array_key_exists('data', $result)) {
        $payload['DATOS'] = $result['data'];
    }

    echo json_encode($payload);
    exit;
}

function dedumsoft_password_reset_base_url(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($protocol . '://' . $host . $path, '/');
}
