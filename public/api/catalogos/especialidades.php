<?php
/**
 * ============================================================================
 * API REST: ESPECIALIDADES DE ARTESANOS
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
        $result = $ctrl->obtenerEspecialidad((int) $_GET['id']);
    } else {
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 100;
        if ($limit <= 0) $limit = 100;
        if ($limit > 500) $limit = 500;

        $activo_param = isset($_GET['activo']) ? trim((string) $_GET['activo']) : null;
        $use_activo_filter = true;
        $activo = true;
        if ($activo_param !== null && $activo_param !== '') {
            $activo_param = strtolower($activo_param);
            if ($activo_param === 'all') {
                $use_activo_filter = false;
                $activo = null;
            } else {
                $activo = ($activo_param === '1' || $activo_param === 'true' || $activo_param === 't');
            }
        }

        $result = $ctrl->listarEspecialidades($offset, $limit, $use_activo_filter ? $activo : null);
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

    $result = $ctrl->crearEspecialidad($input);

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

    $result = $ctrl->actualizarEspecialidad($input);

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
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    $result = $ctrl->eliminarEspecialidad((int) $input['id']);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
