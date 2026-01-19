<?php
/**
 * ============================================================================
 * MÓDULO DE LOGGING DE SEGURIDAD
 * ============================================================================
 * 
 * Registra eventos de seguridad en los logs de Apache/PHP para auditoría.
 * Los logs se escriben con error_log() al archivo configurado en php.ini.
 * 
 * Formato de log:
 * [DEDUMSOFT_SECURITY] EVENT_TYPE | IP | Username | Details
 * 
 * Para ver los logs:
 * - macOS/Linux: tail -f /var/log/apache2/error.log
 * - O el archivo configurado en error_log de php.ini
 * 
 * @package Dedumsoft\Connection
 * @author  Equipo Dedumsoft
 */

/**
 * Obtiene la dirección IP real del cliente.
 * 
 * @return string Dirección IP del cliente
 */
function dedumsoft_get_client_ip(): string
{
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Registra un evento de seguridad en los logs de Apache/PHP.
 * 
 * @param string      $event_type Tipo de evento (LOGIN_SUCCESS, LOGIN_FAILED, etc.)
 * @param string|null $username   Usuario afectado (si aplica)
 * @param string|null $details    Detalles adicionales
 * 
 * @return void
 */
function dedumsoft_security_log(string $event_type, ?string $username = null, ?string $details = null): void
{
    $ip = dedumsoft_get_client_ip();
    $user = $username ?? '-';
    $info = $details ?? '-';
    $uri = $_SERVER['REQUEST_URI'] ?? '-';

    // Formato: [DEDUMSOFT_SECURITY] EVENT | IP | User | URI | Details
    $message = sprintf(
        '[DEDUMSOFT_SECURITY] %s | %s | %s | %s | %s',
        $event_type,
        $ip,
        $user,
        $uri,
        $info
    );

    error_log($message);
}

/**
 * Log de intento de login.
 */
function dedumsoft_log_login(string $username, bool $success, string $reason = ''): void
{
    $event = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
    $details = $success ? 'OK' : $reason;
    dedumsoft_security_log($event, $username, $details);
}

/**
 * Log de acceso denegado.
 */
function dedumsoft_log_access_denied(string $resource, ?string $username = null): void
{
    dedumsoft_security_log('ACCESS_DENIED', $username, "Resource: {$resource}");
}

/**
 * Log de CSRF inválido.
 */
function dedumsoft_log_csrf_invalid(?string $username = null): void
{
    dedumsoft_security_log('CSRF_INVALID', $username, 'Token missing or invalid');
}

/**
 * Log de rate limiting.
 */
function dedumsoft_log_rate_limited(int $request_count = 0): void
{
    dedumsoft_security_log('RATE_LIMITED', null, "Requests: {$request_count}");
}
