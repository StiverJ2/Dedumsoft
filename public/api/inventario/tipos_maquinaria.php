<?php
/**
 * ============================================================================
 * API REST: TIPOS DE MAQUINARIA
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
$method = api_init(2, ['GET']);

// =============================================================================
// GET: Listar tipos de maquinaria
// =============================================================================
if ($method === 'GET') {
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    $result = $ctrl->listarTiposMaquinaria($activo);

    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
