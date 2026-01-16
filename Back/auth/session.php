<?php
require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/../env/env.php';

function dedumsoft_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    return false;
}

function dedumsoft_cookie_secure(): bool
{
    if (defined('ENV') && array_key_exists('PROD', ENV)) {
        return (bool) ENV['PROD'];
    }
    return dedumsoft_is_https();
}

function dedumsoft_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = dedumsoft_cookie_secure();
    $params = session_get_cookie_params();

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function dedumsoft_csrf_token(): string
{
    dedumsoft_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function dedumsoft_rotate_csrf(): void
{
    dedumsoft_start_session();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function dedumsoft_validate_csrf(?string $token): bool
{
    dedumsoft_start_session();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
