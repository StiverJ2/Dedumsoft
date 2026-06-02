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
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';

$repo = new OrdenRepository($connLogic);

$method = api_init(3, ['GET', 'POST', 'PATCH']);

// Determinar rol del usuario
$session_user = get_session_user();
$rolid = (int) ($session_user['rolid'] ?? 0);
$is_operador = ($rolid === 2);

// =============================================================================
// GET: Listar ordenes de produccion
// =============================================================================
if ($method === 'GET') {
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 50;
    $estado = $_GET['estado'] ?? null;
    $estado = ($estado === '') ? null : $estado;

    try {
        $rows = $repo->listar($offset, $limit, $estado);

        // Los operadores no ven ordenes terminadas
        if ($is_operador) {
            $rows = array_values(array_filter($rows, function ($row) {
                return strtolower($row['estado'] ?? '') !== 'terminada';
            }));
        }
    } catch (PDOException $e) {
        api_log_error('ordenes', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear orden de produccion
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    api_require_fields($input, ['producto_id', 'cantidad']);

    $producto_id   = (int) $input['producto_id'];
    $cantidad      = (int) $input['cantidad'];
    $artesano_id   = isset($input['artesano_id']) && $input['artesano_id'] > 0 ? (int) $input['artesano_id'] : null;
    $prioridad_id  = isset($input['prioridad_id']) && $input['prioridad_id'] > 0 ? (int) $input['prioridad_id'] : 2;
    $observaciones = isset($input['observaciones']) && $input['observaciones'] !== '' ? trim((string) $input['observaciones']) : null;

    try {
        $new_id = $repo->crear($producto_id, $cantidad, $artesano_id, $prioridad_id, $observaciones);
        api_ok(['id' => $new_id], 201, 'Orden creada.');
    } catch (PDOException $e) {
        api_log_error('ordenes', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error al crear la orden.');
    }
}

// =============================================================================
// PATCH: Asignar artesano a una orden
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null || !isset($input['id'])) {
        api_error(400, 'ID requerido.');
    }

    $id          = (int) $input['id'];
    $artesano_id = isset($input['artesano_id']) && $input['artesano_id'] > 0 ? (int) $input['artesano_id'] : 0;

    if ($artesano_id <= 0) {
        api_error(400, 'artesano_id es requerido.');
    }

    try {
        $ok = $repo->asignarArtesano($id, $artesano_id);

        if (!$ok) {
            api_error(422, 'No se pudo asignar el artesano.');
        }

        api_ok(null, 200, 'Orden actualizada.');
    } catch (PDOException $e) {
        api_log_error('ordenes', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        api_error($code, $code === 404 ? 'Orden no encontrada.' : 'Error al asignar artesano.');
    }
}
