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

define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

// Solo aceptar GET
if (!validateHttpMethod('GET')) {
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(4); // Menú: Reportes

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
    error_log('reportes_ventas error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
