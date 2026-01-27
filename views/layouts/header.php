<?php
/**
 * ============================================================================
 * PARTIAL: CABECERA HTML COMÚN
 * ============================================================================
 * 
 * Archivo parcial que genera el <head> y apertura del <body> para todas
 * las páginas del sistema. Incluye:
 * 
 * - Headers de seguridad HTTP (X-Content-Type-Options, X-Frame-Options, etc.)
 * - Content Security Policy (CSP) para navegadores modernos
 * - Carga condicional de estilos según modo legacy
 * - Google Fonts (Playfair Display, Raleway)
 * - Bootstrap CSS, DataTables (solo moderno), CSS principal
 * - uPlot para gráficos (si $load_uplot está definido)
 * - Polyfills IE8 (JSON2, ie8.js, ie8.css en modo legacy)
 * 
 * Variables esperadas:
 * - $load_uplot (bool): Si true, carga uPlot para gráficos
 * - $legacy (bool): Calculada automáticamente desde guard.php
 * 
 * @package Dedumsoft\Partials
 * @author  Equipo Dedumsoft
 */

require_once PRIVATE_PATH . '/Database/Guard.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
$user = get_session_user();
$legacy = dedumsoft_is_legacy_browser();

// =============================================================================
// HEADERS DE SEGURIDAD HTTP
// =============================================================================
// Estos headers protegen contra varios tipos de ataques web.
header("X-Content-Type-Options: nosniff");              // Previene MIME sniffing
header("X-Frame-Options: SAMEORIGIN");                  // Previene clickjacking
header("X-XSS-Protection: 1; mode=block");             // Activa filtro XSS
header("Referrer-Policy: strict-origin-when-cross-origin"); // Controla referrer

// CSP solo para navegadores modernos (IE8 no lo soporta)
if (!$legacy) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedumsoft</title>
    <base href="<?php echo rtrim(base_url(), '/'); ?>/">
    <script>
        // URL base global para llamadas AJAX y JavaScript
        var DEDUMSOFT_BASE_URL = '<?php echo rtrim(base_url(), '/'); ?>';
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Raleway:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <?php if (!$legacy): ?>
        <link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/dedumsoft.css">
    <?php if (!empty($load_uplot)): ?>
        <link rel="stylesheet" href="assets/uplot/uPlot.min.css">
        <script src="assets/uplot/uPlot.min.js"></script>
    <?php endif; ?>
    <?php if ($legacy): ?>
        <link rel="stylesheet" href="assets/css/ie8.css">
        <script src="assets/js/json2.min.js"></script>
        <script src="assets/js/ie8.js"></script>
    <?php endif; ?>
</head>

<body class="ds-body">
    <?php if ($legacy): ?>
        <div class="legacy-banner">
            <img src="assets/icons/fatcow/16/information.png" alt="" class="legacy-icon">
            <span>Modo clasico activo &mdash; Interfaz optimizada para su navegador</span>
        </div>
    <?php endif; ?>
    <div class="ds-shell">
