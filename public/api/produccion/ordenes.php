<?php
/**
 * ============================================================================
 * API REST: ORDENES DE PRODUCCION
 * ============================================================================
 *
 * Endpoint para gestion de ordenes de trabajo/produccion.
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/OrdenController.php';

$ctrl = new OrdenController($connLogic);

$session_user = get_session_user();
$rolid = (int) ($session_user['rolid'] ?? 0);
$is_operador = ($rolid === 2);

$method = api_init(3, ['GET', 'POST', 'PATCH']);

// =============================================================================
// GET: Listar ordenes de produccion
// =============================================================================
if ($method === 'GET') {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $result = $ctrl->obtenerOrden((int)$_GET['id']);
    } else {
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
        $estado = $_GET['estado'] ?? null;
        $estado = ($estado === '') ? null : $estado;
        $result = $ctrl->listarOrdenes($offset, $limit, $estado, $is_operador);
    }
    if (!$result['success']) api_error($result['code'], $result['message']);
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// POST: Crear orden de produccion
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    $result = $ctrl->crearOrden($input);
    if (!$result['success']) api_error($result['code'], $result['message']);
    api_ok($result['data'], $result['code'], $result['message']);
}

// =============================================================================
// PATCH: Asignar artesano a una orden
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    $result = $ctrl->asignarArtesano($input);
    if (!$result['success']) api_error($result['code'], $result['message']);
    api_ok($result['data'], $result['code'], $result['message']);
}
