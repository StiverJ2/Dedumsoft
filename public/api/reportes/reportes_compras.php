<?php
/**
 * ============================================================================
 * API REST: REPORTE DE COMPRAS
 * ============================================================================
 *
 * Endpoint para obtener el reporte de compras en un rango de fechas.
 * Muestra totales agrupados por tipo de inventario (oro, insumos, maquinaria).
 *
 * Métodos soportados:
 * - GET: Obtener reporte de compras
 *
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 *
 * Parámetros:
 * - desde (date): Fecha inicial del reporte (default: primer día del mes)
 * - hasta (date): Fecha final del reporte (default: último día del mes)
 *
 * Datos retornados:
 * - tipo_inventario: Categoría de compra (oro, insumo, maquinaria)
 * - cantidad_total: Suma de unidades/gramos comprados
 * - movimientos: Número de transacciones de compra
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
    // Llamar función de reporte de compras agrupado por tipo
    // COALESCE para evitar NULL en totales vacíos
    $stmt = $connLogic->prepare(
        'SELECT tipo_inventario, COALESCE(cantidad_total, 0) AS cantidad_total, COALESCE(movimientos, 0) AS movimientos FROM fun_reporte_compras(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    api_log_error('reportes_compras', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
