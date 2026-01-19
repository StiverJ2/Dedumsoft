<?php
/**
 * ============================================================================
 * MÓDULO DE RATE LIMITING (LIMITACIÓN DE INTENTOS)
 * ============================================================================
 * 
 * Protege contra ataques de fuerza bruta limitando los intentos de login.
 * 
 * Configuración por defecto:
 * - Máximo 5 intentos por IP
 * - Ventana de 15 minutos
 * - Bloqueo de 15 minutos al exceder límite
 * 
 * Almacenamiento:
 * Usa sesión PHP para simplicidad (funciona en despliegues de un servidor).
 * Para entornos multi-servidor, considerar Redis o base de datos.
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/session.php';

// =============================================================================
// CONFIGURACIÓN DE RATE LIMITING
// =============================================================================
const RATE_LIMIT_MAX_ATTEMPTS = 5;        // Intentos máximos permitidos
const RATE_LIMIT_WINDOW_SECONDS = 900;     // Ventana de tiempo (15 minutos)
const RATE_LIMIT_LOCKOUT_SECONDS = 900;    // Duración del bloqueo (15 minutos)

/**
 * Verifica si la IP actual está bloqueada por rate limiting.
 * 
 * Lógica de verificación:
 * 1. Si hay un bloqueo activo, denegamos acceso
 * 2. Si el bloqueo expiró, reseteamos el contador
 * 3. Si la ventana de tiempo expiró, reseteamos
 * 4. Si alcanzó el máximo de intentos, activamos bloqueo
 * 
 * @return array Estructura con:
 *               - allowed: bool - TRUE si puede intentar login
 *               - remaining: int - Intentos restantes
 *               - retry_after: int|null - Segundos hasta poder reintentar
 */
function check_rate_limit(): array
{
    dedumsoft_start_session();

    // Identificar al cliente por su IP (hasheada para privacidad en logs)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);
    $now = time();

    // Inicializar estructura si es primer intento de esta IP
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,           // Contador de intentos
            'first_attempt' => $now,   // Timestamp del primer intento
            'locked_until' => null     // Timestamp hasta cuando está bloqueado
        ];
    }

    // Usar referencia para modificar directamente
    $data = &$_SESSION[$key];

    // CASO 1: IP actualmente bloqueada
    if ($data['locked_until'] !== null && $now < $data['locked_until']) {
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $data['locked_until'] - $now
        ];
    }

    // CASO 2: Bloqueo expirado - resetear todo
    if ($data['locked_until'] !== null && $now >= $data['locked_until']) {
        $data['attempts'] = 0;
        $data['first_attempt'] = $now;
        $data['locked_until'] = null;
    }

    // CASO 3: Ventana de tiempo expirada - resetear contador
    if ($now - $data['first_attempt'] > RATE_LIMIT_WINDOW_SECONDS) {
        $data['attempts'] = 0;
        $data['first_attempt'] = $now;
    }

    // CASO 4: Máximo de intentos alcanzado - activar bloqueo
    if ($data['attempts'] >= RATE_LIMIT_MAX_ATTEMPTS) {
        $data['locked_until'] = $now + RATE_LIMIT_LOCKOUT_SECONDS;
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => RATE_LIMIT_LOCKOUT_SECONDS
        ];
    }

    // CASO 5: Permitido - aún tiene intentos disponibles
    return [
        'allowed' => true,
        'remaining' => RATE_LIMIT_MAX_ATTEMPTS - $data['attempts'],
        'retry_after' => null
    ];
}

/**
 * Registra un intento de login fallido.
 * 
 * Debe llamarse después de cada intento fallido de autenticación
 * para incrementar el contador de la IP.
 * 
 * @return void
 */
function record_failed_attempt(): void
{
    dedumsoft_start_session();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    // Inicializar si no existe
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => null
        ];
    }

    // Incrementar contador de intentos
    $_SESSION[$key]['attempts']++;
}

/**
 * Limpia el rate limit después de un login exitoso.
 * 
 * Resetea el contador de intentos para la IP actual,
 * permitiendo nuevos intentos sin restricción.
 * 
 * @return void
 */
function clear_rate_limit(): void
{
    dedumsoft_start_session();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    // Eliminar registro de rate limit
    unset($_SESSION[$key]);
}
