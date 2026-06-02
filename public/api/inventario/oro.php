<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE ORO
 * ============================================================================
 *
 * Delega toda la logica de negocio a InventarioController.
 * Este archivo solo maneja HTTP: autenticacion, metodo y respuesta JSON.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/InventarioController.php';

$ctrl = new InventarioController($connLogic);

$method = api_init_dual(2, 2, ['GET', 'POST', 'PATCH', 'DELETE']);
if ($method === 'GET') {
    if (!dedumsoft_user_can_menu(2) && !dedumsoft_user_can_menu(3)) {
        dedumsoft_forbidden();
    }
}

// =============================================================================
// GET: Listar / Obtener por ID
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $result = $ctrl->obtenerOro((int) $_GET['id']);
    } else {
        $offset  = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
        $limit   = isset($_GET['limit'])   ? (int) $_GET['limit']   : 50;
        $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;

        $activo_raw = $_GET['activo'] ?? null;
        $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $result = $ctrl->listarOro($offset, $limit, $tipo_id, $activo);
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

    $result = $ctrl->crearOro($input);

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

    $result = $ctrl->actualizarOro($input);

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

    $result = $ctrl->eliminarOro((int) $id);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
