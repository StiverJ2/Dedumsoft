<?php
/**
 * ============================================================================
 * API REST: ORDENES DEL ARTESANO
 * ============================================================================
 *
 * Endpoint para que los artesanos consulten sus ordenes asignadas.
 *
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/ArtesanoRepository.php';
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';

$artesanoRepo = new ArtesanoRepository($connLogic);
$ordenRepo    = new OrdenRepository($connLogic);

$method = api_init(3, ['GET', 'PATCH']);

// =============================================================================
// GET: Obtener ordenes asignadas a un artesano
// =============================================================================
if ($method === 'GET') {
    $artesano_id = isset($_GET['artesano_id']) ? (int) $_GET['artesano_id'] : 0;
    $offset      = isset($_GET['offset'])      ? (int) $_GET['offset']      : 0;
    $limit       = isset($_GET['limit'])       ? (int) $_GET['limit']       : 50;

    if ($artesano_id <= 0) {
        api_error(400, 'artesano_id requerido.');
    }

    try {
        $rows = $artesanoRepo->obtenerOrdenes($artesano_id, $offset, $limit);
    } catch (PDOException $e) {
        api_log_error('artesano_ordenes', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// PATCH: Actualizar estado de orden (artesano)
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['id', 'estado_id']);

    $id        = (int) $input['id'];
    $estado_id = (int) $input['estado_id'];

    try {
        $result = $ordenRepo->cambiarEstado($id, $estado_id);

        if (!$result['success']) {
            api_error(400, $result['mensaje']);
        }

        api_ok(null, 200, $result['mensaje']);
    } catch (PDOException $e) {
        api_log_error('artesano_ordenes', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }
}
