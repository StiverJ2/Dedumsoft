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

require_once __DIR__ . '/../../../private/api_helper.php';

api_init(4, ['GET']);

try {
    // Llamar función de reporte de inventario consolidado
    $stmt = $connLogic->prepare(
        'SELECT tipo, item_id, nombre, cantidad, stock_minimo, proveedor FROM fun_reporte_inventario()'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    api_log_error('reportes_inventario', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
