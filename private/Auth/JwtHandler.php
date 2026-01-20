<?php
/**
 * ============================================================================
 * MÓDULO DE JSON WEB TOKENS (JWT)
 * ============================================================================
 * 
 * Implementación propia de JWT usando HMAC-SHA256 para la firma.
 * Los tokens se usan para autenticación stateless de usuarios.
 * 
 * Estructura de un JWT:
 * - Header: {"alg": "HS256", "typ": "JWT"}
 * - Payload: Datos del usuario (sub, username, rolid, exp)
 * - Signature: HMAC-SHA256(header.payload, JWT_SECRET)
 * 
 * Seguridad:
 * - Usa hash_equals() para comparación timing-safe
 * - Valida expiración automáticamente
 * - El secreto se obtiene de variables de entorno
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
 * Codifica datos en Base64 URL-safe.
 * 
 * Diferencias con Base64 estándar:
 * - Reemplaza '+' por '-' y '/' por '_'
 * - Elimina el padding '=' del final
 * 
 * Esto permite usar el token en URLs sin problemas de encoding.
 * 
 * @param string $data Datos binarios a codificar
 * @return string Cadena Base64 URL-safe
 */
function jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodifica datos de Base64 URL-safe.
 * 
 * Proceso inverso a jwt_base64url_encode():
 * - Restaura los caracteres '+' y '/'
 * - Añade el padding '=' necesario
 * 
 * @param string $data Cadena Base64 URL-safe
 * @return string Datos binarios originales
 */
function jwt_base64url_decode(string $data): string
{
    // Calcular padding necesario (Base64 requiere múltiplos de 4)
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Genera un token JWT firmado.
 * 
 * Estructura del token generado: header.payload.signature
 * 
 * El payload debe contener al menos:
 * - sub: ID del usuario
 * - exp: Timestamp de expiración (Unix)
 * 
 * @param array $payload Datos a incluir en el token
 * @return string Token JWT completo
 */
function jwt_encode(array $payload): string
{
    // Header fijo: algoritmo HS256, tipo JWT
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];

    // Construir segmentos del token
    $segments = [];
    $segments[] = jwt_base64url_encode(json_encode($header));
    $segments[] = jwt_base64url_encode(json_encode($payload));

    // Crear firma HMAC-SHA256
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, ENV['JWT_SECRET'], true);
    $segments[] = jwt_base64url_encode($signature);

    // Unir: header.payload.signature
    return implode('.', $segments);
}

/**
 * Decodifica y valida un token JWT.
 * 
 * Validaciones realizadas:
 * 1. Formato correcto (3 partes separadas por '.')
 * 2. Firma válida (HMAC-SHA256 coincide)
 * 3. No expirado (exp > tiempo actual)
 * 
 * IMPORTANTE: Usa hash_equals() para prevenir ataques de timing.
 * 
 * @param string $token Token JWT a validar
 * @return array|null Payload decodificado o NULL si es inválido
 */
function jwt_decode(string $token): ?array
{
    // Validar formato: debe tener exactamente 3 partes
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$header_b64, $payload_b64, $sig_b64] = $parts;

    // Recalcular la firma esperada
    $signing_input = $header_b64 . '.' . $payload_b64;
    $signature = jwt_base64url_encode(hash_hmac('sha256', $signing_input, ENV['JWT_SECRET'], true));

    // Comparación timing-safe para prevenir ataques de tiempo
    if (!hash_equals($signature, $sig_b64)) {
        return null; // Firma inválida = token manipulado
    }

    // Decodificar payload
    $payload = json_decode(jwt_base64url_decode($payload_b64), true);
    if (!is_array($payload)) {
        return null;
    }

    // Validar expiración
    if (isset($payload['exp']) && time() > (int) $payload['exp']) {
        return null; // Token expirado
    }

    return $payload;
}
