<?php
/**
 * ============================================================================
 * API REST: REPORTE DE USO DE MATERIALES
 * ============================================================================
 * 
 * Endpoint para obtener el reporte de consumo de materiales.
 * Muestra qué materiales (oro, insumos) se han usado en producción.
 * 
 * Métodos soportados:
 * - GET: Obtener reporte de uso de materiales
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 * 
 * Parámetros:
 * - desde (date): Fecha inicial del reporte (default: primer día del mes)
 * - hasta (date): Fecha final del reporte (default: último día del mes)
 * 
 * Datos retornados:
 * - tipo_material: Tipo (oro o insumo)
 * - material_id: ID del material en su tabla
 * - material_nombre: Nombre descriptivo del material
 * - cantidad_total: Total consumido en el período
 * - costo_total: Valor monetario del consumo
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
    // Llamar función de reporte de uso de materiales
    $stmt = $connLogic->prepare(
        'SELECT tipo_material, material_id, material_nombre, cantidad_total, costo_total FROM fun_reporte_uso_materiales(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_materiales error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
