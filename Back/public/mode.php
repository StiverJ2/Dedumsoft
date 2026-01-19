<?php
/**
 * ============================================================================
 * SELECTOR DE MODO DE INTERFAZ
 * ============================================================================
 * 
 * Permite cambiar entre modo "normal" (moderno) y modo "legacy" (IE8+).
 * Guarda la preferencia en una cookie segura.
 * 
 * Parámetros GET:
 * - mode: 'legacy', 'normal' o vacío (elimina cookie)
 * - redirect: Página destino después de cambiar modo
 * 
 * Seguridad:
 * - Cookie HttpOnly (no accesible desde JavaScript)
 * - Cookie SameSite=Lax (protección CSRF)
 * - Lista blanca de URLs de redirección permitidas
 * - Duración: 1 año
 * 
 * Modos de interfaz:
 * - normal: CSS moderno, ES6, DataTables, Notyf, Bootstrap JS
 * - legacy: CSS compatible IE8, tablas simples, JSONP, iconos PNG
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

define('DEDUMSOFT_APP', true);
require_once __DIR__ . '/../auth/session.php';

// Parsear y validar el modo solicitado
$mode = strtolower(trim($_GET['mode'] ?? ''));
if ($mode !== 'legacy' && $mode !== 'normal') {
    $mode = '';
}

// Configuración segura de la cookie
$secure = dedumsoft_cookie_secure();
$cookie_opts = [
    'expires' => time() + 60 * 60 * 24 * 365,  // 1 año
    'path' => '/Back',
    'secure' => $secure,
    'httponly' => true,   // No accesible desde JavaScript
    'samesite' => 'Lax'   // Protección CSRF
];

// Aplicar o eliminar cookie según modo
if ($mode === '') {
    // Modo vacío: eliminar cookie (usar detección automática)
    $cookie_opts['expires'] = time() - 3600;
    setcookie('dedumsoft_ui_mode', '', $cookie_opts);
} else {
    // Guardar preferencia del usuario
    setcookie('dedumsoft_ui_mode', $mode, $cookie_opts);
}

// =========================================================================
// VALIDACIÓN SEGURA DE REDIRECCIÓN
// =========================================================================
// Solo permite redirigir a páginas conocidas (whitelist)

$redirect_raw = trim($_GET['redirect'] ?? 'index.php');
$redirect = 'index.php';  // Default seguro

// Lista blanca de páginas permitidas
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

// Validar URL y extraer solo el nombre de archivo
$parts = parse_url($redirect_raw);
$path = $parts['path'] ?? '';
$query = $parts['query'] ?? '';

// Rechazar URLs con esquema o host (previene open redirect)
if ($path !== '' && empty($parts['scheme']) && empty($parts['host'])) {
    $target = basename($path);
    if (in_array($target, $allowed_redirects, true)) {
        $redirect = $target;
        // Preservar query string si existe
        if ($query !== '') {
            $redirect .= '?' . $query;
        }
    }
}

header('Location: ' . $redirect);
exit;