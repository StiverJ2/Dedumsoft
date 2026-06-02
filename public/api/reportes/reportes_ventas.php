<?php
/**
 * ============================================================================
 * API REST: REPORTE DE VENTAS
 * ============================================================================
 *
 * Endpoint para obtener el reporte de ventas en un rango de fechas.
 * Incluye cálculo de costos, precios de venta y utilidad.
 *
 * Métodos soportados:
 * - GET: Obtener reporte de ventas
 *
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 *
 * Parámetros:
 * - desde (date): Fecha inicial del reporte (default: primer día del mes)
 * - hasta (date): Fecha final del reporte (default: último día del mes)
 *
 * Datos retornados:
 * - producto_id: ID del producto vendido
 * - fecha_venta: Fecha de la transacción
 * - precio_venta: Precio al cliente
 * - costo_total: Costo de producción
 * - utilidad: Ganancia (precio_venta - costo_total)
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
    // Llamar función de reporte de ventas con cálculo de utilidad
    $stmt = $connLogic->prepare(
        'SELECT id, producto_id, fecha_venta, precio_venta, costo_total, utilidad FROM fun_reporte_ventas(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    api_log_error('reportes_ventas', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
