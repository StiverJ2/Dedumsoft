<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE MAQUINARIA
 * ============================================================================
 *
 * Endpoint CRUD para gestion del inventario de maquinaria y equipos.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/InventarioMaquinariaRepository.php';

$repo = new InventarioMaquinariaRepository($connLogic);

$method = api_init_dual(2, 2, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(2) && !dedumsoft_user_can_menu(3)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar inventario de maquinaria
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        try {
            $row = $repo->obtenerPorId((int) $_GET['id']);
        } catch (PDOException $e) {
            api_log_error('inventario_maquinaria', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            api_error(500, 'Error interno del servidor.');
        }
        api_ok($row ? [$row] : []);
    }

    $offset    = isset($_GET['offset'])    ? (int) $_GET['offset']    : 0;
    $limit     = isset($_GET['limit'])     ? (int) $_GET['limit']     : 50;
    $estado_id = isset($_GET['estado_id']) && $_GET['estado_id'] !== '' ? (int) $_GET['estado_id'] : null;

    $activo_raw = $_GET['activo'] ?? null;
    $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    try {
        $rows = $repo->listar($offset, $limit, $estado_id, $activo);
    } catch (PDOException $e) {
        api_log_error('inventario_maquinaria', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear equipo de maquinaria
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['nombre', 'sku', 'tipo_maquinaria_id']);

    try {
        $ok = $repo->crear(
            $input['nombre'],
            $input['sku'],
            (int) $input['tipo_maquinaria_id'],
            $input['marca'] ?? null,
            $input['modelo'] ?? null,
            $input['fecha_compra'] ?? null,
            $input['valor_compra'] ?? null,
            isset($input['estado_id']) ? (int) $input['estado_id'] : null,
            isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? (int) $input['ubicacion_id'] : null
        );

        if (!$ok) {
            api_error(422, 'No se pudo crear la maquinaria.');
        }

        api_ok(null, 201, 'Maquinaria creada.');
    } catch (PDOException $e) {
        api_log_error('inventario_maquinaria', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear maquinaria.');
    }
}

// =============================================================================
// PATCH: Actualizar maquinaria
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
            $input['sku'] ?? null,
            isset($input['tipo_maquinaria_id']) ? (int) $input['tipo_maquinaria_id'] : null,
            $input['marca'] ?? null,
            $input['modelo'] ?? null,
            $input['fecha_compra'] ?? null,
            $input['valor_compra'] ?? null,
            isset($input['estado_id']) ? (int) $input['estado_id'] : null,
            $input['ultima_mantenimiento'] ?? null,
            $input['proxima_mantenimiento'] ?? null,
            isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? (int) $input['ubicacion_id'] : null,
            isset($input['activo']) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN) : null
        );

        if (!$ok) {
            api_error(422, 'No se pudo actualizar la maquinaria.');
        }

        api_ok(null, 200, 'Maquinaria actualizada.');
    } catch (PDOException $e) {
        api_log_error('inventario_maquinaria', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Maquinaria no encontrada.' : 'Error al actualizar.');
    }
}

// =============================================================================
// DELETE: Eliminar maquinaria
// =============================================================================
if ($method === 'DELETE') {
    $input = api_json_body();
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        api_error(400, 'ID requerido.');
    }

    try {
        $ok = $repo->eliminar((int) $id);

        if (!$ok) {
            api_error(422, 'No se pudo eliminar la maquinaria.');
        }

        api_ok(null, 200, 'Maquinaria eliminada.');
    } catch (PDOException $e) {
        api_log_error('inventario_maquinaria', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Maquinaria no encontrada.' : 'Error al eliminar.');
    }
}
