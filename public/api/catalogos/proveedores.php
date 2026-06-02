<?php
/**
 * ============================================================================
 * API REST: PROVEEDORES
 * ============================================================================
 *
 * Delega toda la lógica de negocio a ProveedorController.
 * Este archivo solo maneja HTTP: autenticación, método y respuesta JSON.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/ProveedorController.php';

$ctrl = new ProveedorController($connLogic);

$method = api_init_dual(6, 6, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(6) && !dedumsoft_user_can_menu(2)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar / Obtener por ID
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $result = $ctrl->obtener((int) $_GET['id']);
    } else {
        $offset  = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
        $limit   = isset($_GET['limit'])   ? (int) $_GET['limit']   : 50;
        $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;
        $activo_raw = $_GET['activo'] ?? null;
        $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $result = $ctrl->listar($offset, $limit, $tipo_id, $activo);
    }

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// POST: Crear
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }

    $result = $ctrl->crear($input);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// PATCH: Actualizar
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }

    $result = $ctrl->actualizar($input);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// DELETE: Eliminar
// =============================================================================
if ($method === 'DELETE') {
    $input = api_json_body();
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        api_error(400, 'ID requerido.');
    }

    $result = $ctrl->eliminar((int) $id);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
