<?php
/**
 * ============================================================================
 * API REST: REPORTE DE EFICIENCIA DE ARTESANOS
 * ============================================================================
 * 
 * Endpoint para obtener métricas de productividad por artesano.
 * Calcula piezas completadas, horas trabajadas y promedio por pieza.
 * 
 * Métodos soportados:
 * - GET: Obtener reporte de eficiencia
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 * 
 * Parámetros:
 * - desde (date): Fecha inicial del reporte (default: primer día del mes)
 * - hasta (date): Fecha final del reporte (default: último día del mes)
 * 
 * Datos retornados:
 * - artesano: Nombre completo del artesano
 * - piezas: Número de piezas completadas en el período
 * - horas: Total de horas trabajadas
 * - promedio_horas: Horas promedio por pieza
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
    // Llamar función de reporte de eficiencia de artesanos
    $stmt = $connLogic->prepare(
        'SELECT artesano, piezas, horas, promedio_horas FROM fun_reporte_eficiencia_artesanos(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_eficiencia error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
