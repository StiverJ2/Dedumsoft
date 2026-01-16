<?php
define('DEDUMSOFT_APP', true);
require_once __DIR__ . '/../auth/session.php';

$mode = strtolower(trim($_GET['mode'] ?? ''));
if ($mode !== 'legacy' && $mode !== 'normal') {
    $mode = '';
}

$secure = dedumsoft_cookie_secure();
$cookie_opts = [
    'expires' => time() + 60 * 60 * 24 * 365,
    'path' => '/Back',
    'secure' => $secure,
    'httponly' => false,
    'samesite' => 'Lax'
];

if ($mode === '') {
    $cookie_opts['expires'] = time() - 3600;
    setcookie('dedumsoft_ui_mode', '', $cookie_opts);
} else {
    setcookie('dedumsoft_ui_mode', $mode, $cookie_opts);
}

$redirect = trim($_GET['redirect'] ?? 'index.php');
if ($redirect === '' || strpos($redirect, '://') !== false || substr($redirect, 0, 1) === '/') {
    $redirect = 'index.php';
}

header('Location: ' . $redirect);
exit;
