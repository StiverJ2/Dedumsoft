<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE INSUMOS
 * ============================================================================
 *
 * Endpoint CRUD para gestion del inventario de insumos.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/InventarioInsumosRepository.php';

$repo = new InventarioInsumosRepository($connLogic);

$method = api_init_dual(2, 2, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(2) && !dedumsoft_user_can_menu(3)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar inventario de insumos
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        try {
            $row = $repo->obtenerPorId((int) $_GET['id']);
        } catch (PDOException $e) {
            api_log_error('inventario_insumos', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            api_error(500, 'Error interno del servidor.');
        }
        api_ok($row ? [$row] : []);
    }

    $offset    = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
    $limit     = isset($_GET['limit'])   ? (int) $_GET['limit']   : 50;
    $categoria = $_GET['categoria'] ?? null;
    $categoria = ($categoria === '') ? null : $categoria;

    $stock_bajo = filter_var($_GET['stock_bajo'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($stock_bajo === null) {
        $stock_bajo = false;
    }

    $activo_raw = $_GET['activo'] ?? null;
    $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    try {
        $rows = $repo->listar($offset, $limit, $categoria, $stock_bajo, $activo);
    } catch (PDOException $e) {
        api_log_error('inventario_insumos', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear insumo
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['nombre', 'categoria', 'unidad_medida', 'precio_unitario']);

    try {
        $ok = $repo->crear(
            $input['nombre'],
            $input['categoria'],
            $input['unidad_medida'],
            $input['precio_unitario'],
            $input['descripcion'] ?? null,
            $input['cantidad'] ?? null,
            $input['stock_minimo'] ?? null,
            isset($input['proveedor_id']) ? (int) $input['proveedor_id'] : null,
            isset($input['ubicacion_id']) ? (int) $input['ubicacion_id'] : null
        );

        if (!$ok) {
            api_error(422, 'No se pudo crear el insumo.');
        }

        api_ok(null, 201, 'Insumo creado.');
    } catch (PDOException $e) {
        api_log_error('inventario_insumos', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear insumo.');
    }
}

// =============================================================================
// PATCH: Actualizar insumo
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
            $input['categoria'] ?? null,
            $input['descripcion'] ?? null,
            $input['cantidad'] ?? null,
            $input['unidad_medida'] ?? null,
            $input['precio_unitario'] ?? null,
            $input['stock_minimo'] ?? null,
            isset($input['proveedor_id']) ? (int) $input['proveedor_id'] : null,
            isset($input['ubicacion_id']) ? (int) $input['ubicacion_id'] : null
        );

        if (!$ok) {
            api_error(422, 'No se pudo actualizar el insumo.');
        }

        api_ok(null, 200, 'Insumo actualizado.');
    } catch (PDOException $e) {
        api_log_error('inventario_insumos', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Insumo no encontrado.' : 'Error al actualizar.');
    }
}

// =============================================================================
// DELETE: Eliminar insumo
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
            api_error(422, 'No se pudo eliminar el insumo.');
        }

        api_ok(null, 200, 'Insumo eliminado.');
    } catch (PDOException $e) {
        api_log_error('inventario_insumos', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Insumo no encontrado.' : 'Error al eliminar.');
    }
}
