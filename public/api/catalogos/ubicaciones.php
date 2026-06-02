<?php
/**
 * ============================================================================
 * API REST: UBICACIONES DE ALMACEN
 * ============================================================================
 *
 * Endpoint CRUD para gestion de ubicaciones fisicas del inventario.
 *
 * Autenticacion: Requerida (JWT en sesion)
 * Autorizacion: Menu 7 (Configuracion)
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/UbicacionRepository.php';

$repo = new UbicacionRepository($connLogic);
$method = api_init(7, ['GET', 'POST', 'PATCH', 'DELETE']);

// =============================================================================
// GET: Listar ubicaciones
// =============================================================================
if ($method === 'GET') {
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $area_id = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? (int) $_GET['area_id'] : null;
    $activo_raw = $_GET['activo'] ?? null;
    $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    try {
        $rows = $repo->listar($offset, $limit, $area_id, $activo);
    } catch (PDOException $e) {
        api_log_error('ubicaciones', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear ubicacion
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['nombre']);

    try {
        $id = $repo->crear($input['nombre'], $input['descripcion'] ?? null, isset($input['area_id']) ? (int) $input['area_id'] : null);
    } catch (PDOException $e) {
        api_log_error('ubicaciones', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear ubicacion.');
    }

    if ($id <= 0) {
        api_error(422, 'No se pudo crear la ubicacion.');
    }

    api_ok(['id' => $id], 201, 'Ubicacion creada.');
}

// =============================================================================
// PATCH: Actualizar ubicacion
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    try {
        $ok = $repo->actualizar(
            (int) $input['id'],
            $input['nombre'] ?? null,
            $input['descripcion'] ?? null,
            isset($input['area_id']) ? (int) $input['area_id'] : null,
            isset($input['activo']) ? (bool) $input['activo'] : null
        );
    } catch (PDOException $e) {
        api_log_error('ubicaciones', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Ubicacion no encontrada.' : 'Error al actualizar.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo actualizar la ubicacion.');
    }

    api_ok(null, 200, 'Ubicacion actualizada.');
}

// =============================================================================
// DELETE: Eliminar ubicacion
// =============================================================================
if ($method === 'DELETE') {
    $input = api_json_body();
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        api_error(400, 'ID requerido.');
    }

    try {
        $ok = $repo->eliminar((int) $id);
    } catch (PDOException $e) {
        api_log_error('ubicaciones', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Ubicacion no encontrada.' : 'Error al eliminar.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo eliminar la ubicacion.');
    }

    api_ok(null, 200, 'Ubicacion eliminada.');
}
