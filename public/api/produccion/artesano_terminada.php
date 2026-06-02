<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE PIEZA TERMINADA (ARTESANO)
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

$method = api_init(3, ['POST']);

// =============================================================================
// POST: Registrar pieza terminada
// =============================================================================
$input = api_json_body();
if ($input === null) {
    api_error(400, 'Datos JSON invalidos.');
}

$result = $ctrl->registrarTerminada($input);

if (!$result['success']) {
    api_error($result['code'], $result['message']);
}

api_ok($result['data'], $result['code'], $result['message']);
