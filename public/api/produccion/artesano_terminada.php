<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE PIEZA TERMINADA (ARTESANO)
 * ============================================================================
 *
 * Endpoint para registrar la finalizacion de una pieza de joyeria.
 *
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/ArtesanoRepository.php';

$repo = new ArtesanoRepository($connLogic);

api_init(3, ['POST']);

// =============================================================================
// POST: Registrar pieza terminada
// =============================================================================
$input = api_json_body();
if ($input === null) {
    api_error(400, 'Datos JSON invalidos.');
}

api_require_fields($input, ['orden_id', 'peso_final']);

$orden_id      = (int) $input['orden_id'];
$peso_final    = (float) $input['peso_final'];
$tiempo_real   = isset($input['tiempo_real']) && $input['tiempo_real'] !== '' ? (float) $input['tiempo_real'] : null;
$calidad_id    = isset($input['calidad_id']) && $input['calidad_id'] !== '' ? (int) $input['calidad_id'] : null;
$observaciones = $input['observaciones'] ?? null;
$observaciones = ($observaciones === '') ? null : $observaciones;

if ($peso_final <= 0) {
    api_error(400, 'peso_final debe ser mayor a 0.');
}

try {
    $result = $repo->marcarTerminada($orden_id, $peso_final, $tiempo_real, $calidad_id, $observaciones);

    if (!$result['success']) {
        api_error(400, $result['mensaje']);
    }

    api_ok(
        [
            'id'               => (int) $result['creacion_id'],
            'costo_materiales' => $result['costo_materiales'],
        ],
        201,
        $result['mensaje']
    );
} catch (PDOException $e) {
    api_log_error('artesano_terminada', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}
