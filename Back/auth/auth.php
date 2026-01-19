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

function dedumsoft_user_can_menu(int $menu_id, ?array $user = null): bool
{
    if ($user === null) {
        $user = get_session_user();
    }
    if (empty($user) || !is_array($user)) {
        return false;
    }
    $perms = $user['permisos_menu'] ?? [];
    if (!isset($perms[$menu_id]) || !is_array($perms[$menu_id])) {
        return false;
    }
    return !empty($perms[$menu_id]['abrir']);
}

function dedumsoft_forbidden(string $message = 'Acceso no autorizado.'): void
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $is_api = strpos($request_uri, '/api/') !== false;
    if ($is_api || strpos($accept, 'application/json') !== false) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 403, 'MENSAJE' => $message]);
    } else {
        $target = dedumsoft_role_home();
        $separator = strpos($target, '?') === false ? '?' : '&';
        $target .= $separator . 'denied=1';
        if (!headers_sent()) {
            header('Location: ' . $target);
            exit;
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
    }
    exit;
}

function require_menu_access(int $menu_id): void
{
    if (!dedumsoft_user_can_menu($menu_id)) {
        dedumsoft_forbidden();
    }
}

function dedumsoft_role_home(?array $user = null): string
{
    if ($user === null) {
        $user = get_session_user();
    }
    if (empty($user) || !is_array($user)) {
        return 'login.php';
    }
    if (dedumsoft_user_can_menu(1, $user)) {
        return 'index.php';
    }
    $rolid = (int) ($user['rolid'] ?? 0);
    if (!empty($user['artesano_id']) && $rolid !== 1) {
        return 'artesano_ordenes.php';
    }
    if (dedumsoft_user_can_menu(3, $user)) {
        return 'index_operario.php';
    }
    if (dedumsoft_user_can_menu(2, $user)) {
        return 'inventario_insumos.php';
    }
    if (dedumsoft_user_can_menu(4, $user)) {
        return 'reportes.php';
    }
    if (dedumsoft_user_can_menu(6, $user)) {
        return 'proveedores.php';
    }
    if (dedumsoft_user_can_menu(7, $user)) {
        return 'configuracion.php';
    }
    if (dedumsoft_user_can_menu(5, $user)) {
        return 'usuarios.php';
    }
    return 'login.php';
}
