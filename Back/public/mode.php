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

$redirect_raw = trim($_GET['redirect'] ?? 'index.php');
$redirect = 'index.php';
$allowed_redirects = [
    'index.php',
    'index_operario.php',
    'inventario.php',
    'inventario_insumos.php',
    'inventario_maquinaria.php',
    'inventario_oro.php',
    'proveedores.php',
    'ubicaciones.php',
    'produccion.php',
    'reportes.php',
    'usuarios.php',
    'configuracion.php',
    'artesano_ordenes.php',
    'login.php'
];
$parts = parse_url($redirect_raw);
$path = $parts['path'] ?? '';
$query = $parts['query'] ?? '';
if ($path !== '' && empty($parts['scheme']) && empty($parts['host'])) {
    $target = basename($path);
    if (in_array($target, $allowed_redirects, true)) {
        $redirect = $target;
        if ($query !== '') {
            $redirect .= '?' . $query;
        }
    }
}

header('Location: ' . $redirect);
exit;
