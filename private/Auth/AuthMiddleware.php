<?php
/**
 * ============================================================================
 * MÓDULO DE AUTENTICACIÓN Y AUTORIZACIÓN
 * ============================================================================
 * 
 * Este archivo contiene las funciones principales para gestionar la
 * autenticación de usuarios y el control de acceso basado en roles.
 * 
 * Funcionalidades:
 * - Verificación de tokens JWT activos en base de datos
 * - Control de acceso a páginas protegidas
 * - Validación de permisos por menú/módulo
 * - Redirección inteligente según rol del usuario
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
require_once __DIR__ . '/JwtHandler.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/../Http/SecurityLogger.php';

/**
 * Verifica si un token JWT está activo en la base de datos.
 * 
 * Consulta la tabla seg_login para validar que:
 * - El token existe
 * - El estado del token es activo (TRUE)
 * - No ha expirado (timestamp_expira > NOW())
 * 
 * @param string $token El token JWT a verificar
 * @return bool TRUE si el token está activo, FALSE en caso contrario
 */
function dedumsoft_token_is_active(string $token): bool
{
    // Obtener conexión global a la base de datos
    $conn = $GLOBALS['connLogic'] ?? null;
    if (!$conn instanceof PDO) {
        return true; // Si no hay conexión, permitir (fallback)
    }

    try {
        // Consulta preparada para prevenir SQL injection
        $stmt = $conn->prepare(
            'SELECT 1 FROM seguridad.seg_login WHERE token = :token AND estado_token = TRUE AND timestamp_expira > NOW() LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Registrar error en logs del servidor
        error_log('auth token lookup error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return false;
    }
}

/**
 * Requiere que el usuario esté autenticado para acceder a una página.
 * 
 * Esta función debe llamarse al inicio de cada página protegida.
 * Realiza tres validaciones:
 * 1. Existe un JWT en la sesión
 * 2. El JWT es válido (firma correcta, no expirado)
 * 3. El token está activo en la base de datos
 * 
 * Si alguna validación falla, redirige al login y termina la ejecución.
 * 
 * @param string $login_path Ruta a la página de login (se ignora, se usa base_url)
 * @return void
 */
function require_login(string $login_path = 'login.php'): void
{
    dedumsoft_start_session();

    // Construir URL de login usando base_url para evitar problemas de subdirectorio
    $login_url = base_url() . '/login.php';

    // Validación 1: Verificar que existe JWT en sesión
    if (empty($_SESSION['jwt'])) {
        header('Location: ' . $login_url);
        exit;
    }

    // Validación 2: Decodificar y validar firma del JWT
    $payload = jwt_decode($_SESSION['jwt']);
    if ($payload === null) {
        session_destroy();
        header('Location: ' . $login_url);
        exit;
    }

    // Validación 3: Verificar que el token está activo en BD
    if (!dedumsoft_token_is_active($_SESSION['jwt'])) {
        session_destroy();
        header('Location: ' . $login_url);
        exit;
    }
}

/**
 * Obtiene los datos del usuario actualmente autenticado.
 * 
 * @return array|null Arreglo con datos del usuario o NULL si no hay sesión
 *                    Estructura: [id_usuario, username, rolid, nombre, artesano_id, permisos_menu]
 */
function get_session_user(): ?array
{
    dedumsoft_start_session();
    return $_SESSION['user'] ?? null;
}

/**
 * Valida la autenticación para endpoints de la API REST.
 * 
 * Similar a require_login() pero diseñada para APIs:
 * - Retorna respuestas JSON en lugar de redirecciones
 * - Devuelve códigos HTTP apropiados (401 Unauthorized)
 * 
 * @return bool TRUE si el usuario está autenticado, FALSE si no
 */
function require_api_auth(): bool
{
    dedumsoft_start_session();

    // Sin JWT en sesión = no autenticado
    if (empty($_SESSION['jwt'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'No autenticado.']);
        return false;
    }

    // JWT inválido o manipulado
    $payload = jwt_decode($_SESSION['jwt']);
    if ($payload === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'Sesion invalida.']);
        return false;
    }

    // Token revocado o expirado en BD
    if (!dedumsoft_token_is_active($_SESSION['jwt'])) {
        session_destroy();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 401, 'MENSAJE' => 'Sesion expirada.']);
        return false;
    }

    return true;
}

/**
 * Verifica si el usuario tiene permiso para acceder a un menú específico.
 * 
 * Los permisos se cargan durante el login desde la tabla seg_menurol
 * y se almacenan en $_SESSION['user']['permisos_menu'].
 * 
 * @param int $menu_id ID del menú a verificar (de seg_menu)
 * @param array|null $user Datos del usuario (opcional, usa sesión si es NULL)
 * @return bool TRUE si tiene permiso de "abrir" el menú
 */
function dedumsoft_user_can_menu(int $menu_id, ?array $user = null): bool
{
    // Obtener usuario de sesión si no se proporciona
    if ($user === null) {
        $user = get_session_user();
    }

    // Sin usuario válido = sin permisos
    if (empty($user) || !is_array($user)) {
        return false;
    }

    // Buscar permisos del menú específico
    $perms = $user['permisos_menu'] ?? [];
    if (!isset($perms[$menu_id]) || !is_array($perms[$menu_id])) {
        return false;
    }

    // Verificar permiso "abrir" (acceso básico al módulo)
    return !empty($perms[$menu_id]['abrir']);
}

/**
 * Muestra error de acceso denegado (403 Forbidden).
 * 
 * Detecta automáticamente si la petición es API o navegador:
 * - API: Responde con JSON y código 403
 * - Navegador: Redirige a la página principal del rol con parámetro denied=1
 * 
 * @param string $message Mensaje de error personalizado
 * @return void (termina la ejecución)
 */
function dedumsoft_forbidden(string $message = 'Acceso no autorizado.'): void
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    // Log de acceso denegado
    $user = get_session_user();
    dedumsoft_log_access_denied($request_uri, $user['username'] ?? null);

    // Detectar si es una petición API
    $is_api = strpos($request_uri, '/api/') !== false;

    if ($is_api || strpos($accept, 'application/json') !== false) {
        // Respuesta JSON para APIs
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['CODIGO' => 403, 'MENSAJE' => $message]);
    } else {
        // Redirección para navegadores
        $target = dedumsoft_role_home();
        $separator = strpos($target, '?') === false ? '?' : '&';
        $target .= $separator . 'denied=1';

        if (!headers_sent()) {
            header('Location: ' . $target);
            exit;
        }

        // Fallback si ya se enviaron headers
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
    }
    exit;
}

/**
 * Requiere acceso a un menú específico o muestra error 403.
 * 
 * Función de conveniencia que combina verificación de permisos
 * con manejo automático de acceso denegado.
 * 
 * @param int $menu_id ID del menú requerido
 * @return void
 */
function require_menu_access(int $menu_id): void
{
    if (!dedumsoft_user_can_menu($menu_id)) {
        dedumsoft_forbidden();
    }
}

/**
 * Determina la página de inicio según el rol y permisos del usuario.
 * 
 * Orden de prioridad para redirección:
 * 1. Dashboard principal (si tiene acceso a menú 1)
 * 2. Órdenes de artesano (si es artesano y no admin)
 * 3. Panel de operario (menú 3)
 * 4. Inventario (menú 2)
 * 5. Reportes (menú 4)
 * 6. Proveedores (menú 6)
 * 7. Configuración (menú 7)
 * 8. Usuarios (menú 5)
 * 9. Login (sin permisos)
 * 
 * @param array|null $user Datos del usuario (opcional)
 * @return string URL completa de la página de inicio correspondiente
 */
function dedumsoft_role_home(?array $user = null): string
{
    if ($user === null) {
        $user = get_session_user();
    }

    $base = base_url();

    // Sin usuario = ir al login
    if (empty($user) || !is_array($user)) {
        return $base . '/login.php';
    }

    // Prioridad 1: Dashboard principal (administradores)
    if (dedumsoft_user_can_menu(1, $user)) {
        return $base . '/index.php';
    }

    // Prioridad 2: Artesanos van a su panel de órdenes
    $rolid = (int) ($user['rolid'] ?? 0);
    if (!empty($user['artesano_id']) && $rolid !== 1) {
        return $base . '/artesano_ordenes.php';
    }

    // Prioridad 3-8: Según permisos disponibles
    if (dedumsoft_user_can_menu(3, $user)) {
        return $base . '/index_operario.php';
    }
    if (dedumsoft_user_can_menu(2, $user)) {
        return $base . '/inventario_insumos.php';
    }
    if (dedumsoft_user_can_menu(4, $user)) {
        return $base . '/reportes.php';
    }
    if (dedumsoft_user_can_menu(6, $user)) {
        return $base . '/proveedores.php';
    }
    if (dedumsoft_user_can_menu(7, $user)) {
        return $base . '/configuracion.php';
    }
    if (dedumsoft_user_can_menu(5, $user)) {
        return $base . '/usuarios.php';
    }

    // Sin permisos = login
    return $base . '/login.php';
}