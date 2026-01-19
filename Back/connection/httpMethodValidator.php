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
 * @package Dedumsoft\Connection
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/guard.php';

/**
 * Valida que el método HTTP de la petición sea el esperado.
 * 
 * Si el método no coincide:
 * - Responde con HTTP 405 Method Not Allowed
 * - Envía JSON con código y mensaje de error
 * - Retorna FALSE para que el caller pueda hacer exit
 * 
 * @param string $method Método esperado ('GET', 'POST', 'PUT', 'DELETE')
 * @return bool TRUE si el método coincide, FALSE si no
 */
function validateHttpMethod(string $method): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
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
