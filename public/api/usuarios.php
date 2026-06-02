<?php
/**
 * ============================================================================
 * API REST: USUARIOS (ACTIVAR/DESACTIVAR)
 * ============================================================================
 *
 * Endpoint para gestionar usuarios del sistema.
 * Usa soft-delete en seguridad.seg_usuario (deleted_at).
 *
 * Autenticacion: Requerida (JWT en sesion)
 * Autorizacion: Menu 5 (Usuarios)
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/UsuarioRepository.php';

$repo = new UsuarioRepository($connLogic);
$method = api_init(5, ['POST', 'PATCH']);

// =============================================================================
// POST: Crear usuario
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['username', 'nombre', 'rolid', 'password']);

    $username = trim((string) $input['username']);
    $nombre = trim((string) $input['nombre']);
    $email = isset($input['email']) ? trim((string) $input['email']) : '';
    $rolid = (int) $input['rolid'];
    $password = (string) $input['password'];
    $apellido = isset($input['apellido']) ? trim((string) $input['apellido']) : '';
    $especialidad_id = isset($input['especialidad_id']) ? (int) $input['especialidad_id'] : 0;
    $telefono = isset($input['telefono']) ? trim((string) $input['telefono']) : '';

    if (strlen($password) < 8) {
        api_error(400, 'La contraseña debe tener al menos 8 caracteres.');
    }

    $email = $email === '' ? null : $email;
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error(400, 'Email invalido.');
    }

    $apellido = $apellido === '' ? null : $apellido;
    $especialidad_id = $especialidad_id > 0 ? $especialidad_id : null;
    $telefono = $telefono === '' ? null : $telefono;

    if ($rolid === 2 && $apellido === null) {
        api_error(400, 'Apellido requerido para rol Operador.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        api_error(500, 'No se pudo generar la contraseña.');
    }

    try {
        $id = $repo->crear($username, $nombre, $hash, $rolid, $email, $apellido, $especialidad_id, $telefono);
    } catch (PDOException $e) {
        api_log_error('usuarios', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $message = $e->getMessage();
        $code = 500;
        $clientMessage = 'Error interno del servidor.';
        if (strpos($message, 'Usuario ya existe') !== false || strpos($message, 'Email ya registrado') !== false) {
            $code = 409;
            $clientMessage = 'Usuario o email ya registrado.';
        } elseif (strpos($message, 'Rol invalido') !== false) {
            $code = 400;
            $clientMessage = 'Rol invalido.';
        } elseif (strpos($message, 'Apellido requerido') !== false) {
            $code = 400;
            $clientMessage = 'Apellido requerido para rol Operador.';
        } elseif (strpos($message, 'Especialidad invalida') !== false) {
            $code = 400;
            $clientMessage = 'Especialidad invalida.';
        }
        api_error($code, $clientMessage);
    }

    if ($id <= 0) {
        api_error(500, 'Error al crear usuario.');
    }

    api_ok(['id' => $id], 201, 'Usuario creado.');
}

// =============================================================================
// PATCH: Activar/desactivar usuario
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    $id = (int) $input['id'];
    $activo = isset($input['activo']) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
    if ($activo === null) {
        api_error(400, 'Estado activo es requerido.');
    }

    $session_user = get_session_user();
    if (!empty($session_user) && (int) ($session_user['id_usuario'] ?? 0) === $id && $activo === false) {
        api_error(400, 'No puedes desactivar tu propio usuario.');
    }

    try {
        $ok = $repo->actualizarEstado($id, $activo);
    } catch (PDOException $e) {
        api_log_error('usuarios', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Usuario no encontrado.' : 'Error al actualizar.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo actualizar el usuario.');
    }

    api_ok(null, 200, $activo ? 'Usuario activado.' : 'Usuario desactivado.');
}
