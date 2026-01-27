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

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

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
    // Llamar función de reporte de producción
    $stmt = $connLogic->prepare(
        'SELECT id, producto, cantidad, artesano, estado, fecha_inicio, fecha_fin_real FROM fun_reporte_produccion(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_produccion error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
