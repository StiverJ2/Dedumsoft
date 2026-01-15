<?php
require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/jwt.php';

function require_login(string $login_path = 'login.php'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['jwt'])) {
        header('Location: ' . $login_path);
        exit;
    }
    $payload = jwt_decode($_SESSION['jwt']);
    if ($payload === null) {
        session_destroy();
        header('Location: ' . $login_path);
        exit;
    }
}

function get_session_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

function require_api_auth(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['jwt'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'No autenticado.']);
        return false;
    }
    $payload = jwt_decode($_SESSION['jwt']);
    if ($payload === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'Sesion invalida.']);
        return false;
    }
    return true;
}
