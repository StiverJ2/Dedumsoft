<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE ORO
 * ============================================================================
 *
 * Endpoint CRUD para gestion del inventario de oro.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/InventarioOroRepository.php';

$repo = new InventarioOroRepository($connLogic);

$method = api_init_dual(2, 2, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(2) && !dedumsoft_user_can_menu(3)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar inventario de oro
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        try {
            $row = $repo->obtenerPorId((int) $_GET['id']);
        } catch (PDOException $e) {
            api_log_error('inventario_oro', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            api_error(500, 'Error interno del servidor.');
        }
        api_ok($row ? [$row] : []);
    }

    $offset  = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
    $limit   = isset($_GET['limit'])   ? (int) $_GET['limit']   : 50;
    $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;

    $activo_raw = $_GET['activo'] ?? null;
    $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    try {
        $rows = $repo->listar($offset, $limit, $tipo_id, $activo);
    } catch (PDOException $e) {
        api_log_error('inventario_oro', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear registro de oro
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['tipo_oro_id', 'peso_gramos', 'precio_gramo']);

    try {
        $ok = $repo->crear(
            (int) $input['tipo_oro_id'],
            $input['peso_gramos'],
            $input['precio_gramo'],
            isset($input['proveedor_id']) ? (int) $input['proveedor_id'] : null,
            $input['fecha_ingreso'] ?? null,
            $input['ubicacion'] ?? null,
            $input['pureza'] ?? null
        );

        if (!$ok) {
            api_error(422, 'No se pudo crear el registro.');
        }

        api_ok(null, 201, 'Registro creado.');
    } catch (PDOException $e) {
        api_log_error('inventario_oro', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear registro.');
    }
}

// =============================================================================
// PATCH: Actualizar registro de oro
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    try {
        $ok = $repo->actualizar(
            (int) $input['id'],
            isset($input['tipo_oro_id']) ? (int) $input['tipo_oro_id'] : null,
            $input['peso_gramos'] ?? null,
            $input['precio_gramo'] ?? null,
            isset($input['proveedor_id']) ? (int) $input['proveedor_id'] : null,
            $input['fecha_ingreso'] ?? null,
            $input['ubicacion'] ?? null,
            $input['pureza'] ?? null
        );

        if (!$ok) {
            api_error(422, 'No se pudo actualizar el registro.');
        }

        api_ok(null, 200, 'Registro actualizado.');
    } catch (PDOException $e) {
        api_log_error('inventario_oro', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Registro no encontrado.' : 'Error al actualizar.');
    }
}

// =============================================================================
// DELETE: Eliminar registro de oro
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
            api_error(422, 'No se pudo eliminar el registro.');
        }

        api_ok(null, 200, 'Registro eliminado.');
    } catch (PDOException $e) {
        api_log_error('inventario_oro', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Registro no encontrado.' : 'Error al eliminar.');
    }
}
