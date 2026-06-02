<?php
/**
 * ============================================================================
 * API REST: USUARIOS
 * ============================================================================
 *
 * Delega toda la logica de negocio a UsuarioController.
 * Este archivo solo maneja HTTP: autenticacion, metodo y respuesta JSON.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/UsuarioController.php';

$ctrl = new UsuarioController($connLogic);
$method = api_init(5, ['GET', 'POST', 'PATCH']);

// =============================================================================
// GET: Listar / Obtener por ID
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $result = $ctrl->obtener((int) $_GET['id']);
    } else {
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 50;
        $activo_raw = $_GET['activo'] ?? null;
        $activo = ($activo_raw === null) ? true : filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $result = $ctrl->listar($offset, $limit, $activo);
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
// PATCH: Actualizar estado
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }

    $result = $ctrl->actualizarEstado($input);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
