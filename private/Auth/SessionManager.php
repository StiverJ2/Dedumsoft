<?php
/**
 * ============================================================================
 * MÓDULO DE GESTIÓN DE SESIONES
 * ============================================================================
 * 
 * Maneja la configuración segura de sesiones PHP y la protección CSRF.
 * 
 * Características de seguridad implementadas:
 * - Cookies HttpOnly (no accesibles desde JavaScript)
 * - Cookies Secure (solo HTTPS en producción)
 * - SameSite=Lax (protección contra CSRF básico)
 * - Modo estricto de sesiones (rechaza IDs no generados por PHP)
 * - Solo cookies (no acepta ID de sesión por URL)
 * - Tokens CSRF con 256 bits de entropía
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 */

// Verificar carga via bootstrap
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/../Database/Guard.php';

/**
 * Detecta si la conexión actual usa HTTPS.
 * 
 * Verifica múltiples indicadores porque la detección varía
 * según la configuración del servidor:
 * - $_SERVER['HTTPS']: Indicador estándar de Apache/IIS
 * - Puerto 443: Puerto estándar de HTTPS
 * - X-Forwarded-Proto: Header de proxies/balanceadores
 * 
 * @return bool TRUE si es HTTPS, FALSE si es HTTP
 */
function dedumsoft_is_https(): bool
{
    // Método 1: Variable HTTPS directa
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    // Método 2: Puerto estándar HTTPS
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    // Método 3: Header de proxy reverso (Nginx, CloudFlare, etc.)
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }

    return false;
}

/**
 * Determina si las cookies deben marcarse como Secure.
 * 
 * En producción (ENV['PROD'] = true), siempre usa Secure.
 * En desarrollo, solo si detecta HTTPS.
 * 
 * @return bool TRUE para marcar cookies como Secure
 */
function dedumsoft_cookie_secure(): bool
{
    // En producción, siempre requerir HTTPS
    if (defined('ENV') && array_key_exists('PROD', ENV)) {
        return (bool) ENV['PROD'];
    }

    // En desarrollo, depende de la conexión actual
    return dedumsoft_is_https();
}

/**
 * Inicia una sesión PHP con configuración de seguridad.
 * 
 * Configuraciones aplicadas:
 * - lifetime=0: Cookie de sesión (expira al cerrar navegador)
 * - httponly=true: Cookie no accesible desde JavaScript
 * - samesite=Lax: Protección CSRF para navegación normal
 * - use_strict_mode=1: Rechaza IDs de sesión arbitrarios
 * - use_only_cookies=1: No acepta ID de sesión en URL
 * 
 * Es seguro llamar múltiples veces (verifica si ya hay sesión activa).
 * 
 * @return void
 */
function dedumsoft_start_session(): void
{
    // Evitar iniciar sesión si ya está activa
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = dedumsoft_cookie_secure();
    $params = session_get_cookie_params();

    // Configurar parámetros de cookie de sesión
    session_set_cookie_params([
        'lifetime' => 0,              // Cookie de sesión
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => $secure,          // Solo HTTPS en producción
        'httponly' => true,           // No accesible desde JS
        'samesite' => 'Lax'           // Protección CSRF
    ]);

    // Configuraciones adicionales de seguridad
    ini_set('session.use_strict_mode', '1');    // Rechazar IDs arbitrarios
    ini_set('session.use_only_cookies', '1');   // Solo cookies, no URLs

    session_start();
}

/**
 * Obtiene o genera el token CSRF de la sesión actual.
 * 
 * El token se genera con random_bytes() para 256 bits (32 bytes)
 * de entropía criptográficamente segura.
 * 
 * Uso en formularios:
 * <input type="hidden" name="csrf_token" value="<?= dedumsoft_csrf_token() ?>">
 * 
 * @return string Token CSRF hexadecimal de 64 caracteres
 */
function dedumsoft_csrf_token(): string
{
    dedumsoft_start_session();

    // Generar token si no existe
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Rota el token CSRF generando uno nuevo.
 * 
 * Se debe llamar después de acciones sensibles (login, cambio de
 * contraseña, etc.) para prevenir ataques de fijación de CSRF.
 * 
 * @return void
 */
function dedumsoft_rotate_csrf(): void
{
    dedumsoft_start_session();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Valida un token CSRF enviado por el usuario.
 * 
 * IMPORTANTE: Usa hash_equals() para comparación timing-safe,
 * previniendo ataques de timing que podrían revelar el token.
 * 
 * @param string|null $token Token enviado en el formulario
 * @return bool TRUE si el token es válido
 */
function dedumsoft_validate_csrf(?string $token): bool
{
    dedumsoft_start_session();

    // Verificar que ambos tokens existen
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Comparación timing-safe
    return hash_equals($_SESSION['csrf_token'], $token);
}
