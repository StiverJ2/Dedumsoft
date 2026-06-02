<?php
/**
 * ============================================================================
 * API REST: REPORTE DE USUARIOS
 * ============================================================================
 *
 * Endpoint para obtener el listado de usuarios del sistema.
 * Muestra información básica de la tabla seguridad.usuarios.
 *
 * Delega toda la lógica de negocio a ReporteController.
 *
 * @package Dedumsoft\API\Reportes
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/ReporteController.php';

$ctrl = new ReporteController($connLogic);

$method = api_init(4, ['GET']);
if ($method === 'GET') {
    $result = $ctrl->reporteUsuarios();
    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
