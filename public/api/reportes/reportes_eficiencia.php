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

require_once __DIR__ . '/../../../private/api_helper.php';

api_init(4, ['GET']);

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
    api_log_error('reportes_eficiencia', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
