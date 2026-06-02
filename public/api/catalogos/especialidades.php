<?php
/**
 * ============================================================================
 * API REST: ESPECIALIDADES DE ARTESANOS
 * ============================================================================
 *
 * Endpoint CRUD para catalogo de especialidades.
 *
 * Autenticacion: Requerida (JWT en sesion)
 * Autorizacion: Menu 7 (Configuracion)
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

$repo = new CatalogoRepository($connLogic);
$method = api_init(7, ['GET', 'POST', 'PATCH', 'DELETE']);

// =============================================================================
// GET: Listar especialidades
// =============================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    if ($limit <= 0) $limit = 100;
    if ($limit > 500) $limit = 500;

    $activo_param = isset($_GET['activo']) ? trim((string) $_GET['activo']) : null;
    $use_activo_filter = true;
    $activo = true;
    if ($activo_param !== null && $activo_param !== '') {
        $activo_param = strtolower($activo_param);
        if ($activo_param === 'all') {
            $use_activo_filter = false;
            $activo = null;
        } else {
            $activo = ($activo_param === '1' || $activo_param === 'true' || $activo_param === 't');
        }
    }

    try {
        if ($id > 0) {
            $row = $repo->obtenerEspecialidadPorId($id);
            if (!$row) {
                api_error(404, 'Especialidad no encontrada.');
            }
            $rows = [$row];
        } else {
            $rows = $repo->listarEspecialidades($offset, $limit, $use_activo_filter ? $activo : null);
        }
    } catch (PDOException $e) {
        api_log_error('especialidades', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear especialidad
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['nombre']);

    $nombre = trim((string) $input['nombre']);
    $descripcion = isset($input['descripcion']) ? trim((string) $input['descripcion']) : '';
    $descripcion = $descripcion === '' ? null : $descripcion;

    try {
        $id = $repo->crearEspecialidad($nombre, $descripcion);
    } catch (PDOException $e) {
        api_log_error('especialidades', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        if ($e->getCode() == 23505) {
            api_error(409, 'La especialidad ya existe.');
        }
        api_error(500, 'Error interno del servidor.');
    }

    if ($id <= 0) {
        api_error(500, 'Error al crear especialidad.');
    }

    api_ok(['id' => $id], 201, 'Especialidad creada.');
}

// =============================================================================
// PATCH: Actualizar especialidad
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    $id = (int) $input['id'];
    $nombre = isset($input['nombre']) ? trim((string) $input['nombre']) : null;
    $descripcion = isset($input['descripcion']) ? trim((string) $input['descripcion']) : null;
    $activo = array_key_exists('activo', $input) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

    $nombre = ($nombre !== null && $nombre !== '') ? $nombre : null;
    $descripcion = ($descripcion !== null && $descripcion !== '') ? $descripcion : null;

    if ($nombre === null && $descripcion === null && $activo === null) {
        api_error(400, 'No hay campos para actualizar.');
    }

    try {
        $ok = $repo->actualizarEspecialidad($id, $nombre, $descripcion, $activo);
    } catch (PDOException $e) {
        api_log_error('especialidades', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo actualizar la especialidad.');
    }

    api_ok(null, 200, 'Especialidad actualizada.');
}

// =============================================================================
// DELETE: Eliminar especialidad
// =============================================================================
if ($method === 'DELETE') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    $id = (int) $input['id'];

    try {
        $ok = $repo->eliminarEspecialidad($id);
    } catch (PDOException $e) {
        api_log_error('especialidades', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo eliminar la especialidad.');
    }

    api_ok(null, 200, 'Especialidad eliminada.');
}
