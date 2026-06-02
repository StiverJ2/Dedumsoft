<?php
/**
 * ============================================================================
 * API REST: UBICACIONES DE ALMACEN
 * ============================================================================
 *
 * Delega toda la lógica de negocio a CatalogoController.
 * Este archivo solo maneja HTTP: autenticación, método y respuesta JSON.
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/CatalogoController.php';

$ctrl = new CatalogoController($connLogic);

$method = api_init(7, ['GET', 'POST', 'PATCH', 'DELETE']);

// =============================================================================
// GET: Listar / Obtener por ID
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $result = $ctrl->obtenerUbicacion((int) $_GET['id']);
    } else {
        $offset  = isset($_GET['offset'])  ? (int) $_GET['offset']  : 0;
        $limit   = isset($_GET['limit'])   ? (int) $_GET['limit']   : 100;
        $area_id = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? (int) $_GET['area_id'] : null;
        $activo_raw = $_GET['activo'] ?? null;
        $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $result = $ctrl->listarUbicaciones($offset, $limit, $area_id, $activo);
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

    $result = $ctrl->crearUbicacion($input);

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

    $result = $ctrl->actualizarUbicacion($input);

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

    $result = $ctrl->eliminarUbicacion((int) $id);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
