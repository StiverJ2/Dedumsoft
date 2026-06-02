<?php
/**
 * ============================================================================
 * API REST: CATALOGOS MAESTROS
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

$catalog = $_GET['catalog'] ?? '';
if ($catalog === '') {
    api_error(400, 'Catalogo requerido.');
}

// =============================================================================
// GET: Listar / Obtener por ID
// =============================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;
    $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $result = $ctrl->listarMaestros($catalog, $id, $offset, $limit, $_GET['activo'] ?? null);

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

    $result = $ctrl->crearMaestro($catalog, $input);

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

    $result = $ctrl->actualizarMaestro($catalog, $input);

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
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    if (!isset($input['id']) || (int) $input['id'] <= 0) {
        api_error(400, 'ID requerido.');
    }

    $result = $ctrl->eliminarMaestro($catalog, (int) $input['id']);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
