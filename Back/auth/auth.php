<?php
require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/session.php';

function dedumsoft_token_is_active(string $token): bool
{
    $conn = $GLOBALS['connLogic'] ?? null;
    if (!$conn instanceof PDO) {
        return true;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT 1 FROM seguridad.seg_login WHERE token = :token AND estado_token = TRUE AND timestamp_expira > NOW() LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('auth token lookup error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return false;
    }
}

function require_login(string $login_path = 'login.php'): void
{
    dedumsoft_start_session();
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
    if (!dedumsoft_token_is_active($_SESSION['jwt'])) {
        session_destroy();
        header('Location: ' . $login_path);
        exit;
    }
}

function get_session_user(): ?array
{
    dedumsoft_start_session();
    return $_SESSION['user'] ?? null;
}

function require_api_auth(): bool
{
    dedumsoft_start_session();
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
    if (!dedumsoft_token_is_active($_SESSION['jwt'])) {
        session_destroy();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'Sesion expirada.']);
        return false;
    }
    return true;
}
