<?php
/**
 * ============================================================================
 * API REST: PROVEEDORES
 * ============================================================================
 *
 * Endpoint CRUD para gestion de proveedores de la joyeria.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';

$repo = new ProveedorRepository($connLogic);

$method = api_init_dual(6, 6, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(6) && !dedumsoft_user_can_menu(2)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar proveedores
// =============================================================================
if ($method === 'GET') {
    $offset  = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
    $limit   = isset($_GET['limit'])   ? (int) $_GET['limit']   : 50;
    $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;

    $activo_raw = $_GET['activo'] ?? null;
    $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    try {
        $rows = $repo->listar($offset, $limit, $tipo_id, $activo);
    } catch (PDOException $e) {
        api_log_error('proveedores', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear proveedor
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['nombre', 'tipo_proveedor_id']);

    try {
        $ok = $repo->crear(
            $input['nombre'],
            (int) $input['tipo_proveedor_id'],
            $input['contacto'] ?? null,
            $input['telefono'] ?? null,
            $input['email'] ?? null,
            $input['direccion'] ?? null
        );

        if (!$ok) {
            api_error(422, 'No se pudo crear el proveedor.');
        }

        api_ok(null, 201, 'Proveedor creado.');
    } catch (PDOException $e) {
        api_log_error('proveedores', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear proveedor.');
    }
}

// =============================================================================
// PATCH: Actualizar proveedor
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
            isset($input['tipo_proveedor_id']) ? (int) $input['tipo_proveedor_id'] : null,
            $input['contacto'] ?? null,
            $input['telefono'] ?? null,
            $input['email'] ?? null,
            $input['direccion'] ?? null,
            isset($input['activo']) ? (bool) $input['activo'] : null
        );

        if (!$ok) {
            api_error(422, 'No se pudo actualizar el proveedor.');
        }

        api_ok(null, 200, 'Proveedor actualizado.');
    } catch (PDOException $e) {
        api_log_error('proveedores', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Proveedor no encontrado.' : 'Error al actualizar.');
    }
}

// =============================================================================
// DELETE: Eliminar proveedor (soft-delete)
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
            api_error(422, 'No se pudo eliminar el proveedor.');
        }

        api_ok(null, 200, 'Proveedor eliminado.');
    } catch (PDOException $e) {
        api_log_error('proveedores', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Proveedor no encontrado.' : 'Error al eliminar.');
    }
}
