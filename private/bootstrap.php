<?php
/**
 * ============================================================================
 * BOOTSTRAP - INICIALIZACIÓN DE LA APLICACIÓN
 * ============================================================================
 * 
 * Este archivo centraliza la carga de configuración y autoloading.
 * Debe incluirse al inicio de cada script PHP de la aplicación.
 * 
 * Proporciona:
 * - Constante DEDUMSOFT_APP para validar acceso directo
 * - Carga de configuración desde config/env.php
 * - Autoloader de Composer
 * - Rutas base del proyecto
 * 
 * Uso:
 *   require_once __DIR__ . '/../private/bootstrap.php';
 * 
 * @package Dedumsoft
 * @author  Equipo Dedumsoft
 */

// Prevenir acceso directo a este archivo
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'bootstrap.php') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Constante para validar que los archivos se cargan correctamente
define('DEDUMSOFT_APP', true);

// =========================================================================
// Rutas base del proyecto
// =========================================================================

/** Directorio raíz del proyecto */
define('BASE_PATH', dirname(__DIR__));

/** Directorio de código privado (no accesible via web) */
define('PRIVATE_PATH', BASE_PATH . '/private');

/** Directorio público (web root) */
define('PUBLIC_PATH', BASE_PATH . '/public');

/** Directorio de vistas */
define('VIEWS_PATH', BASE_PATH . '/views');

/** Directorio de configuración */
define('CONFIG_PATH', BASE_PATH . '/config');

// =========================================================================
// Cargar configuración del entorno
// =========================================================================

$envFile = CONFIG_PATH . '/env.php';
if (!file_exists($envFile)) {
    http_response_code(500);
    error_log('[DEDUMSOFT] Archivo de configuración no encontrado: ' . $envFile);
    exit('Error de configuración del servidor');
}
require_once $envFile;

// =========================================================================
// Autoloader de Composer
// =========================================================================

$autoloader = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// =========================================================================
// Configuración de PHP según entorno
// =========================================================================

if (ENV['PROD'] ?? false) {
    // Producción: ocultar errores
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    // Desarrollo: mostrar errores
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Zona horaria
date_default_timezone_set('America/Bogota');

// Codificación UTF-8
mb_internal_encoding('UTF-8');

// =========================================================================
// HTTP Method Override (Legacy/Proxies)
// =========================================================================
// Permite soportar PATCH/PUT/DELETE via POST + X-HTTP-Method-Override o _method.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $override = '';
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    } elseif (!empty($_POST['_method'])) {
        $override = $_POST['_method'];
    } elseif (!empty($_GET['_method'])) {
        $override = $_GET['_method'];
    }

    if ($override !== '') {
        $override = strtoupper(trim($override));
        if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            $_SERVER['REQUEST_METHOD'] = $override;
        }
    }
}

// =========================================================================
// Funciones de ayuda para rutas
// =========================================================================

/**
 * Obtiene la ruta absoluta a un archivo privado.
 * 
 * @param string $path Ruta relativa desde private/
 * @return string Ruta absoluta
 */
function private_path(string $path = ''): string {
    return PRIVATE_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Obtiene la ruta absoluta a una vista.
 * 
 * @param string $path Ruta relativa desde views/
 * @return string Ruta absoluta
 */
function view_path(string $path = ''): string {
    return VIEWS_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Obtiene la URL base del sitio.
 * 
 * @return string URL base sin trailing slash
 */
function base_url(): string {
    return ENV['SITE_URL'] ?? '';
}

/**
 * Obtiene la URL de un asset.
 * 
 * @param string $path Ruta relativa desde assets/
 * @return string URL completa del asset
 */
function asset_url(string $path): string {
    return base_url() . '/assets/' . ltrim($path, '/');
}
