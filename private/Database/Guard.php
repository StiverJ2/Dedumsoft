<?php
/**
 * ============================================================================
 * GUARDIA DE SEGURIDAD Y UTILIDADES GLOBALES
 * ============================================================================
 * 
 * Este archivo proporciona protección contra acceso directo a archivos PHP
 * y funciones de utilidad para detección de navegadores legacy (IE8/IE7).
 * 
 * Protección:
 * - Verifica que la constante DEDUMSOFT_APP esté definida
 * - Previene que usuarios accedan directamente a includes
 * 
 * Compatibilidad IE8:
 * - Detecta navegadores antiguos para servir CSS/JS alternativo
 * - Permite override manual via cookie
 * 
 * @package Dedumsoft\Connection
 * @author  Equipo Dedumsoft
 */

// =============================================================================
// PROTECCIÓN CONTRA ACCESO DIRECTO
// =============================================================================
// Todos los puntos de entrada (index.php, api/*.php) deben definir:
// define('DEDUMSOFT_APP', true);
// Antes de incluir cualquier archivo.
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 403, 'MENSAJE' => 'Acceso no autorizado.']);
    exit;
}

// =============================================================================
// DETECCIÓN DE NAVEGADORES LEGACY (IE8/IE7)
// =============================================================================

/**
 * Determina si se debe usar el modo legacy (IE8 compatible).
 * 
 * Prioridad:
 * 1. Cookie de override (usuario puede forzar modo)
 * 2. Detección automática por User-Agent
 * 
 * @return bool TRUE si debe usar CSS/JS legacy
 */
if (!function_exists('dedumsoft_is_legacy_browser')) {
    function dedumsoft_is_legacy_browser(): bool
    {
        // Primero verificar si hay override manual
        $override = dedumsoft_ui_mode_override();
        if ($override === 'legacy') {
            return true;
        }
        if ($override === 'normal') {
            return false;
        }

        // Si no hay override, detectar por User-Agent
        return dedumsoft_is_legacy_ua();
    }
}

/**
 * Detecta IE7 o IE8 por el User-Agent.
 * 
 * @return bool TRUE si es Internet Explorer 7 u 8
 */
if (!function_exists('dedumsoft_is_legacy_ua')) {
    function dedumsoft_is_legacy_ua(): bool
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return stripos($ua, 'MSIE 8.0') !== false || stripos($ua, 'MSIE 7.0') !== false;
    }
}

/**
 * Lee el override de modo UI desde cookie.
 * 
 * El usuario puede forzar modo 'legacy' o 'normal' desde
 * la página de configuración.
 * 
 * @return string 'legacy', 'normal' o '' (sin override)
 */
if (!function_exists('dedumsoft_ui_mode_override')) {
    function dedumsoft_ui_mode_override(): string
    {
        $mode = strtolower(trim($_COOKIE['dedumsoft_ui_mode'] ?? ''));
        if ($mode === 'legacy' || $mode === 'normal') {
            return $mode;
        }
        return '';
    }
}

// =============================================================================
// MANEJADOR GLOBAL DE EXCEPCIONES
// =============================================================================
// Captura excepciones no manejadas para:
// - Registrarlas en error_log
// - Mostrar mensaje generico al usuario
// - Evitar exponer detalles del error en producción

if (!defined('DEDUMSOFT_EXCEPTION_HANDLER')) {
    define('DEDUMSOFT_EXCEPTION_HANDLER', true);

    set_exception_handler(function (Throwable $e): void {
        // Formatear mensaje para logs (incluye stack trace)
        $message = sprintf(
            "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        error_log($message);

        // Solo responder si es HTTP (no CLI) y no se han enviado headers
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);

            // Detectar si es API o navegador
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $is_api = strpos($request_uri, '/api/') !== false || strpos($accept, 'application/json') !== false;

            if ($is_api) {
                // Respuesta JSON para APIs
                header('Content-Type: application/json');
                echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
            } else {
                // Texto plano para navegadores
                header('Content-Type: text/plain; charset=UTF-8');
                echo 'Error interno del servidor.';
            }
        }
        exit(1);
    });
}
