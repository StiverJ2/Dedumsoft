<?php
/**
 * ============================================================================
 * API REST: ORDENES DEL ARTESANO
 * ============================================================================
 *
 * Delega toda la logica de negocio a OrdenController.
 * Este archivo solo maneja HTTP: autenticacion, metodo y respuesta JSON.
 *
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/OrdenController.php';

$ctrl = new OrdenController($connLogic);

$method = api_init(3, ['GET', 'PATCH']);

// =============================================================================
// GET: Obtener ordenes asignadas a un artesano
// =============================================================================
if ($method === 'GET') {
    $artesano_id = isset($_GET['artesano_id']) ? (int) $_GET['artesano_id'] : 0;
    $offset      = isset($_GET['offset'])      ? (int) $_GET['offset']      : 0;
    $limit       = isset($_GET['limit'])       ? (int) $_GET['limit']       : 50;

    $result = $ctrl->listarArtesanoOrdenes($artesano_id, $offset, $limit);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// PATCH: Actualizar estado de orden (artesano)
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }

    $result = $ctrl->cambiarEstadoOrden($input);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
