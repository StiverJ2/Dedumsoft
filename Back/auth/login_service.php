<?php
/**
 * ============================================================================
 * SERVICIO DE AUTENTICACIÓN DE USUARIOS
 * ============================================================================
 * 
 * Maneja el proceso completo de inicio de sesión:
 * 1. Verificación de rate limiting (anti fuerza bruta)
 * 2. Validación de credenciales contra la base de datos
 * 3. Generación de token JWT
 * 4. Registro de sesión en base de datos
 * 5. Carga de permisos del rol
 * 
 * Flujo de autenticación:
 * Usuario -> login.php -> login_service.php -> BD -> JWT -> Sesión
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/../env/env.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/../connection/security_log.php';

/**
 * Procesa el inicio de sesión de un usuario.
 * 
 * Validaciones realizadas en orden:
 * 1. Rate limiting - Previene ataques de fuerza bruta
 * 2. Campos vacíos - Usuario y contraseña requeridos
 * 3. Usuario existe - Consulta a fun_iniciar_sesion()
 * 4. Contraseña correcta - Verificación con password_verify()
 * 
 * En caso de éxito:
 * - Genera JWT con datos del usuario
 * - Registra sesión en seg_login (para revocación)
 * - Carga permisos de menú del rol
 * - Regenera ID de sesión (previene session fixation)
 * - Rota token CSRF
 * 
 * @param PDO $connLogic Conexión a la base de datos
 * @param string $username Nombre de usuario
 * @param string $password Contraseña en texto plano
 * @return array Respuesta con CODIGO y MENSAJE (200=éxito, 4xx=error)
 */
function login_user(PDO $connLogic, string $username, string $password): array
{
    // =========================================================================
    // PASO 1: Verificar rate limiting (protección contra fuerza bruta)
    // =========================================================================
    $rate_check = check_rate_limit();
    if (!$rate_check['allowed']) {
        dedumsoft_log_rate_limited($rate_check['count'] ?? 0);
        $minutes = ceil($rate_check['retry_after'] / 60);
        return [
            'CODIGO' => 429,
            'MENSAJE' => "Demasiados intentos fallidos. Intente de nuevo en $minutes minutos."
        ];
    }

    // =========================================================================
    // PASO 2: Validar campos requeridos
    // =========================================================================
    $username = trim($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        return ['CODIGO' => 400, 'MENSAJE' => 'Usuario y contrasena son obligatorios.'];
    }

    // =========================================================================
    // PASO 3: Buscar usuario en base de datos
    // =========================================================================
    // La función fun_iniciar_sesion retorna los datos del usuario si existe
    // y está activo, incluyendo el hash de la contraseña para verificación
    try {
        $stmt = $connLogic->prepare(
            'SELECT codigo, mensaje, username, id_usuario, rolid, hash, nombre, artesano_id FROM seguridad.fun_iniciar_sesion(:username)'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('login_service select error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return ['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.'];
    }

    // Usuario no encontrado o inactivo
    if (!$row || (int) $row['codigo'] !== 200) {
        record_failed_attempt(); // Registrar intento fallido
        dedumsoft_log_login($username, false, 'User not found or inactive');
        return ['CODIGO' => 401, 'MENSAJE' => 'Usuario o contrasena incorrectos.'];
    }

    // =========================================================================
    // PASO 4: Verificar contraseña con bcrypt
    // =========================================================================
    // password_verify() compara de forma segura (timing-safe)
    if (!password_verify($password, $row['hash'])) {
        record_failed_attempt(); // Registrar intento fallido
        dedumsoft_log_login($username, false, 'Invalid password');
        return ['CODIGO' => 401, 'MENSAJE' => 'Usuario o contrasena incorrectos.'];
    }

    // =========================================================================
    // PASO 5: Generar token JWT
    // =========================================================================
    $exp = time() + (int) ENV['JWT_EXP_SECONDS'];
    $payload = [
        'sub' => (int) $row['id_usuario'],      // Subject (ID usuario)
        'username' => $row['username'],
        'rolid' => (int) $row['rolid'],
        'exp' => $exp                            // Expiración (Unix timestamp)
    ];

    $token = jwt_encode($payload);

    // Token de refresco para sesiones prolongadas (opcional)
    $refresh = bin2hex(random_bytes(32));

    // =========================================================================
    // PASO 6: Registrar sesión en base de datos
    // =========================================================================
    // Esto permite:
    // - Revocar tokens antes de su expiración
    // - Ver sesiones activas del usuario
    // - Auditoría de accesos (IP, User-Agent)
    try {
        $stmt = $connLogic->prepare(
            'INSERT INTO seguridad.seg_login (token, refresh_token, refresh_expira, timestamp_expira, ip_origen, user_agent, usuarioid)
             VALUES (:token, :refresh_token, NOW() + INTERVAL \'7 days\', TO_TIMESTAMP(:exp), :ip, :ua, :usuarioid)'
        );
        $stmt->execute([
            ':token' => $token,
            ':refresh_token' => $refresh,
            ':exp' => $exp,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':usuarioid' => (int) $row['id_usuario']
        ]);
    } catch (PDOException $e) {
        error_log('login_service insert error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return ['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.'];
    }

    // =========================================================================
    // PASO 7: Configurar sesión PHP
    // =========================================================================
    dedumsoft_start_session();

    // Regenerar ID previene ataques de session fixation
    session_regenerate_id(true);

    // Almacenar token y datos del usuario en sesión
    $_SESSION['jwt'] = $token;
    $_SESSION['user'] = [
        'id_usuario' => (int) $row['id_usuario'],
        'username' => $row['username'],
        'rolid' => (int) $row['rolid'],
        'nombre' => $row['nombre'],
        'artesano_id' => $row['artesano_id'] !== null ? (int) $row['artesano_id'] : null
    ];

    // =========================================================================
    // PASO 8: Cargar permisos de menú del rol
    // =========================================================================
    // Los permisos determinan a qué módulos puede acceder el usuario
    try {
        $stmt = $connLogic->prepare(
            'SELECT m.id_menu, m.nombre, m.ruta, mr.abrir, mr.guardar, mr.editar, mr.eliminar
             FROM seguridad.seg_menurol mr
             JOIN seguridad.seg_menu m ON mr.menuid = m.id_menu
             WHERE mr.rolid = :rolid AND mr.abrir = TRUE'
        );
        $stmt->execute([':rolid' => (int) $row['rolid']]);
        $permisos = [];
        while ($perm = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permisos[$perm['id_menu']] = $perm;
        }
        $_SESSION['user']['permisos_menu'] = $permisos;
    } catch (PDOException $e) {
        error_log('login_service permisos error: ' . $e->getMessage());
        $_SESSION['user']['permisos_menu'] = [];
    }

    // =========================================================================
    // PASO 9: Finalizar - Limpiar rate limit y rotar CSRF
    // =========================================================================
    clear_rate_limit();      // Resetear contador de intentos
    dedumsoft_rotate_csrf(); // Nuevo token CSRF post-login
    dedumsoft_log_login($username, true);

    return [
        'CODIGO' => 200,
        'MENSAJE' => 'Inicio de sesion exitoso.'
    ];
}