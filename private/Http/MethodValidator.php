<?php
/**
 * ============================================================================
 * VALIDADOR DE MÉTODOS HTTP
 * ============================================================================
 * 
 * Proporciona validación del método HTTP de la petición.
 * 
 * Uso típico:
 * if (!validateHttpMethod('POST')) {
 *     exit;  // Ya se envió respuesta 405
 * }
 * // Continuar procesando petición POST...
 * 
 * @package Dedumsoft\Http
 * @author  Equipo Dedumsoft
 */

// Verificar carga via bootstrap
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/../Database/Guard.php';

/**
 * Valida que el método HTTP de la petición sea el esperado.
 * 
 * Si el método no coincide:
 * - Responde con HTTP 405 Method Not Allowed
 * - Envía JSON con código y mensaje de error
 * - Retorna FALSE para que el caller pueda hacer exit
 * 
 * @param string|array $method Método(s) esperado(s) ('GET', 'POST', 'PATCH', 'DELETE')
 * @return bool TRUE si el método coincide, FALSE si no
 */
function validateHttpMethod($method): bool
{
    $allowed = is_array($method) ? $method : [$method];
    $current = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($current, $allowed, true)) {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode([
            'CODIGO' => 405,
            'MENSAJE' => 'Metodo HTTP no permitido.'
        ]);
        return false;
    }
    return true;
}
