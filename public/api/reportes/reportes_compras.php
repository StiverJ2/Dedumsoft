<?php
/**
 * ============================================================================
 * API REST: REPORTE DE COMPRAS
 * ============================================================================
 *
 * Endpoint para obtener el reporte de compras en un rango de fechas.
 * Muestra totales agrupados por tipo de inventario (oro, insumos, maquinaria).
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
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-t');

    $result = $ctrl->reporteCompras($desde, $hasta);
    if (!$result['success']) {
        api_error($result['code'], $result['message']);
    }
    api_ok($result['data'], $result['code'], $result['message']);
}
