<?php
/**
 * ============================================================================
 * API REST: REPORTE DE INVENTARIO
 * ============================================================================
 * 
 * Endpoint para obtener el reporte consolidado de inventario.
 * Combina oro, insumos y maquinaria en una sola vista.
 * 
 * Métodos soportados:
 * - GET: Obtener reporte de inventario actual
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 * 
 * Datos retornados:
 * - tipo: Tipo de inventario (oro, insumo, maquinaria)
 * - item_id: ID del item en su tabla correspondiente
 * - nombre: Nombre descriptivo del item
 * - cantidad: Cantidad actual en stock
 * - stock_minimo: Nivel mínimo configurado (para alertas)
 * - proveedor: Nombre del proveedor habitual
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

try {
    // Llamar función de reporte de inventario consolidado
    $stmt = $connLogic->prepare(
        'SELECT tipo, item_id, nombre, cantidad, stock_minimo, proveedor FROM fun_reporte_inventario()'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_inventario error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
