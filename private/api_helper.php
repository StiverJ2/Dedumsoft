<?php
/**
 * ============================================================================
 * API HELPER — Reducción de boilerplate para endpoints REST
 * ============================================================================
 *
 * Este archivo centraliza el patron repetitivo de cada public/api/.../*.php:
 *   require bootstrap -> header JSON -> validar metodo -> auth -> menu -> switch
 *
 * Uso típico en un endpoint:
 *   <?php
 *   require_once __DIR__ . '/../../../private/api_helper.php';
 *   api_init(2, ['GET', 'POST', 'PATCH', 'DELETE']);
 *   // ... lógica del endpoint ...
 *   api_ok($rows); // o api_error(400, 'Mensaje')
 *
 * @package Dedumsoft\Helpers
 * @author  Equipo Dedumsoft
 */

// Solo se carga desde un script que ya hizo bootstrap (o lo hacemos nosotros)
if (!defined('DEDUMSOFT_APP')) {
    require_once __DIR__ . '/bootstrap.php';
}

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

// Header JSON por defecto para todas las APIs
header('Content-Type: application/json');

/**
 * Inicializa un endpoint API con autenticación y validación de método.
 *
 * @param int      $menu_id         ID del menú requerido
 * @param string[] $allowed_methods Métodos HTTP permitidos (ej: ['GET','POST'])
 * @return string Método HTTP actual (GET/POST/PATCH/PUT/DELETE)
 */
function api_init(int $menu_id, array $allowed_methods = ['GET']): string
{
    if (!validateHttpMethod($allowed_methods)) {
        exit;
    }

    if (!require_api_auth()) {
        exit;
    }

    require_menu_access($menu_id);

    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Inicializa un endpoint API donde GET es menos restrictivo
 * que las mutaciones (POST/PATCH/DELETE).
 *
 * Ejemplo: GET accesible desde Inventario(2) o Producción(3),
 *          pero POST/PATCH/DELETE solo desde Inventario(2).
 *
 * @param int      $menu_id_read    Menú para lecturas (GET)
 * @param int      $menu_id_write   Menú para escrituras (POST/PATCH/DELETE)
 * @param string[] $allowed_methods Métodos HTTP permitidos
 * @return string Método HTTP actual
 */
function api_init_dual(int $menu_id_read, int $menu_id_write, array $allowed_methods = ['GET', 'POST', 'PATCH', 'DELETE']): string
{
    if (!validateHttpMethod($allowed_methods)) {
        exit;
    }

    if (!require_api_auth()) {
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        // Lectura: puede venir de cualquiera de los dos menús
        if (!dedumsoft_user_can_menu($menu_id_read) && !dedumsoft_user_can_menu($menu_id_write)) {
            dedumsoft_forbidden();
        }
    } else {
        // Escritura: solo el menú principal
        if (!dedumsoft_user_can_menu($menu_id_write)) {
            dedumsoft_forbidden();
        }
    }

    return $method;
}

/**
 * Devuelve una respuesta exitosa (200 OK) y termina la ejecución.
 *
 * @param mixed $data    Datos a incluir en la respuesta
 * @param int   $code    Código HTTP (default 200)
 * @param string $message Mensaje (default 'OK')
 * @return never
 */
function api_ok(mixed $data, int $code = 200, string $message = 'OK'): never
{
    http_response_code($code);
    echo json_encode(['CODIGO' => $code, 'MENSAJE' => $message, 'DATOS' => $data]);
    exit;
}

/**
 * Devuelve una respuesta de error y termina la ejecución.
 *
 * @param int    $code    Código HTTP
 * @param string $message Mensaje de error
 * @return never
 */
function api_error(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['CODIGO' => $code, 'MENSAJE' => $message]);
    exit;
}

/**
 * Lee y decodifica el body JSON de la petición.
 * Si falla, devuelve null (el endpoint debe manejar el error).
 *
 * @return array|null
 */
function api_json_body(): ?array
{
    $input = file_get_contents('php://input');
    if ($input === false || $input === '') {
        return null;
    }
    $decoded = json_decode($input, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Valida que ciertos campos requeridos existan en el body JSON.
 * Si falta alguno, responde con 400 y termina.
 *
 * @param array $body     Body decodificado (de api_json_body)
 * @param array $required Lista de nombres de campo requeridos
 * @return void
 */
function api_require_fields(array $body, array $required): void
{
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '' || $body[$field] === null) {
            api_error(400, "Campo requerido: {$field}");
        }
    }
}

/**
 * Log de errores de API con prefijo consistente.
 *
 * @param string $endpoint Nombre del endpoint (ej: 'inventario_oro')
 * @param string $method   Método HTTP
 * @param string $message  Mensaje de error
 * @return void
 */
function api_log_error(string $endpoint, string $method, string $message): void
{
    error_log("[API:{$endpoint}] {$method} error: {$message}");
}
