<?php
/**
 * ============================================================================
 * API REST: REPORTE DE PRODUCCIÓN
 * ============================================================================
 *
 * Endpoint para obtener el reporte de producción en un rango de fechas.
 * Muestra las órdenes de trabajo con su estado, artesano y fechas.
 *
 * Métodos soportados:
 * - GET: Obtener reporte de producción
 *
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 *
 * Parámetros:
 * - desde (date): Fecha inicial del reporte (default: primer día del mes)
 * - hasta (date): Fecha final del reporte (default: último día del mes)
 *
 * @package Dedumsoft\API\Reportes
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';

api_init(4, ['GET']);

// Parsear parámetros de fecha (defaults al mes actual)
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');

try {
    // Llamar función de reporte de producción
    $stmt = $connLogic->prepare(
        'SELECT id, producto, cantidad, artesano, estado, fecha_inicio, fecha_fin_real FROM fun_reporte_produccion(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    api_log_error('reportes_produccion', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
