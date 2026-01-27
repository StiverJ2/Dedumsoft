<?php
/**
 * ============================================================================
 * API REST: USUARIOS (ACTIVAR/DESACTIVAR)
 * ============================================================================
 * 
 * Endpoint para activar o desactivar usuarios del sistema.
 * Usa soft-delete en seguridad.seg_usuario (deleted_at).
 * 
 * Métodos soportados:
 * - POST: Crear nuevo usuario
 * - PATCH: Actualizar estado activo/inactivo
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 5 (Usuarios)
 * 
 * Entrada JSON (POST):
 * - username (string, requerido)
 * - nombre (string, requerido)
 * - email (string, opcional)
 * - rolid (int, requerido)
 * - password (string, requerido)
 * - apellido (string, requerido si rol=OPERADOR)
 * - especialidad_id (int, opcional, se guarda en artesano_especialidad)
 * - telefono (string, opcional)
 *
 * Entrada JSON (PATCH):
 * - id (int, requerido): ID del usuario
 * - activo (bool, requerido): true=activar, false=desactivar
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

if (!validateHttpMethod(['POST', 'PATCH'])) {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!require_api_auth()) {
    exit;
}
require_menu_access(5); // Menú: Usuarios

// =============================================================================
// POST: Crear usuario
// =============================================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    $username = trim((string) ($input['username'] ?? ''));
    $nombre = trim((string) ($input['nombre'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $rolid = isset($input['rolid']) ? (int) $input['rolid'] : 0;
    $password = (string) ($input['password'] ?? '');
    $apellido = trim((string) ($input['apellido'] ?? ''));
    $especialidad_id = isset($input['especialidad_id']) ? (int) $input['especialidad_id'] : 0;
    $telefono = trim((string) ($input['telefono'] ?? ''));

    if ($username === '' || $nombre === '' || $rolid <= 0 || $password === '') {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Username, nombre, rol y contraseña son requeridos.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'La contraseña debe tener al menos 8 caracteres.']);
        exit;
    }

    $email = $email === '' ? null : $email;
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Email inválido.']);
        exit;
    }

    $apellido = $apellido === '' ? null : $apellido;
    $especialidad_id = $especialidad_id > 0 ? $especialidad_id : null;
    $telefono = $telefono === '' ? null : $telefono;

    if ($rolid === 2 && $apellido === null) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Apellido requerido para rol Operador.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'No se pudo generar la contraseña.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT seguridad.fun_crear_usuario(:username, :nombre, :clave, :rolid, :email, :apellido, :especialidad_id, :telefono) AS id_usuario'
        );
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':clave', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':rolid', $rolid, PDO::PARAM_INT);
        $stmt->bindValue(':email', $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':apellido', $apellido, $apellido === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':especialidad_id', $especialidad_id, $especialidad_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':telefono', $telefono, $telefono === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) ($stmt->fetchColumn() ?: 0);
    } catch (PDOException $e) {
        error_log('usuarios POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $message = $e->getMessage();
        $code = 500;
        $clientMessage = 'Error interno del servidor.';
        if (strpos($message, 'Usuario ya existe') !== false || strpos($message, 'Email ya registrado') !== false) {
            $code = 409;
            $clientMessage = 'Usuario o email ya registrado.';
        } elseif (strpos($message, 'Rol invalido') !== false) {
            $code = 400;
            $clientMessage = 'Rol inválido.';
        } elseif (strpos($message, 'Apellido requerido') !== false) {
            $code = 400;
            $clientMessage = 'Apellido requerido para rol Operador.';
        } elseif (strpos($message, 'Especialidad invalida') !== false) {
            $code = 400;
            $clientMessage = 'Especialidad inválida.';
        }
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $clientMessage]);
        exit;
    }

    if ($id <= 0) {
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear usuario.']);
        exit;
    }

    http_response_code(201);
    echo json_encode([
        'CODIGO' => 201,
        'MENSAJE' => 'Usuario creado.',
        'DATOS' => ['id' => (int) $id]
    ]);
    exit;
}

// =============================================================================
// PATCH: Activar/desactivar usuario
// =============================================================================
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int) $input['id'] : 0;
$activo_raw = $input['activo'] ?? null;

$activo = null;
if ($activo_raw !== null) {
    $activo = filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

if ($id <= 0 || $activo === null) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID y estado son requeridos.']);
    exit;
}

$session_user = get_session_user();
if (!empty($session_user) && (int) ($session_user['id_usuario'] ?? 0) === $id && $activo === false) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'No puedes desactivar tu propio usuario.']);
    exit;
}

try {
    $stmt = $connLogic->prepare('SELECT seguridad.fun_actualizar_usuario_estado(:id, :activo)');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('usuarios PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
    http_response_code($code);
    echo json_encode([
        'CODIGO' => $code,
        'MENSAJE' => $code === 404 ? 'Usuario no encontrado.' : 'Error interno del servidor.'
    ]);
    exit;
}

echo json_encode([
    'CODIGO' => 200,
    'MENSAJE' => $activo ? 'Usuario activado.' : 'Usuario desactivado.'
]);
