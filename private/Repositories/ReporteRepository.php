<?php
/**
 * ============================================================================
 * REPORTE REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL de reportes y dashboard.
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class ReporteRepository extends Repository
{
    /**
     * Reporte de produccion por periodo.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function produccion(string $desde, string $hasta): array
    {
        return $this->execSet(
            'SELECT id, producto, cantidad, artesano, estado FROM fun_reporte_produccion(:desde, :hasta)',
            [':desde' => $desde, ':hasta' => $hasta]
        );
    }

    /**
     * Reporte de inventario (stock bajo).
     *
     * @return array<int, array<string, mixed>>
     */
    public function inventario(): array
    {
        return $this->execSet(
            'SELECT tipo, item_id, nombre, cantidad, stock_minimo, proveedor FROM fun_reporte_inventario()'
        );
    }

    /**
     * Reporte de eficiencia de artesanos.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function eficienciaArtesanos(string $desde, string $hasta): array
    {
        return $this->execSet(
            'SELECT artesano, piezas, horas, promedio_horas FROM fun_reporte_eficiencia_artesanos(:desde, :hasta)',
            [':desde' => $desde, ':hasta' => $hasta]
        );
    }

    /**
     * Reporte de uso de materiales.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function usoMateriales(string $desde, string $hasta): array
    {
        return $this->execSet(
            'SELECT tipo_material, material_id, material_nombre, cantidad_total, costo_total FROM fun_reporte_uso_materiales(:desde, :hasta)',
            [':desde' => $desde, ':hasta' => $hasta]
        );
    }

    /**
     * Reporte de ventas.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function ventas(string $desde, string $hasta): array
    {
        return $this->execSet(
            'SELECT id, producto_id, fecha_venta, precio_venta, utilidad FROM fun_reporte_ventas(:desde, :hasta)',
            [':desde' => $desde, ':hasta' => $hasta]
        );
    }

    /**
     * Reporte de compras.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function compras(string $desde, string $hasta): array
    {
        return $this->execSet(
            'SELECT tipo_inventario, cantidad_total, movimientos FROM fun_reporte_compras(:desde, :hasta)',
            [':desde' => $desde, ':hasta' => $hasta]
        );
    }

    /**
     * Reporte de usuarios.
     *
     * @return array<int, array<string, mixed>>
     */
    public function usuarios(): array
    {
        return $this->execSet(
            'SELECT id_usuario, username, nombre, rol, activo FROM seguridad.fun_reporte_usuarios()'
        );
    }

    /**
     * Conteo total de inventario (oro + insumos + maquinaria).
     *
     * @return int
     */
    public function contarInventarioTotal(): int
    {
        return (int) $this->pdo
            ->query('SELECT (SELECT COUNT(1) FROM inventario_oro) + (SELECT COUNT(1) FROM inventario_insumos) + (SELECT COUNT(1) FROM inventario_maquinaria) AS total')
            ->fetchColumn();
    }

    /**
     * Conteo de items con stock bajo.
     *
     * @return int
     */
    public function contarStockBajo(): int
    {
        return (int) $this->pdo
            ->query('SELECT COUNT(1) FROM fun_reporte_inventario()')
            ->fetchColumn();
    }

    /**
     * Ventas del mes (suma total).
     *
     * @param string $desde
     * @param string $hasta
     * @return float
     */
    public function ventasMesTotal(string $desde, string $hasta): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(ct.precio_venta_real), 0) FROM creaciones_terminadas ct WHERE ct.vendida = TRUE AND ct.fecha_venta::date BETWEEN :desde AND :hasta'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * KPIs de ordenes del dashboard.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function ordenesDashboard(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ordenes_activas, ordenes_completadas FROM fun_reporte_ordenes_dashboard(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Datos para grafico de ventas (serie temporal).
     *
     * @param string $desde
     * @param string $hasta
     * @return array<int, array<string, mixed>>
     */
    public function ventasChart(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fecha_venta::date AS dia, COALESCE(SUM(precio_venta_real), 0) AS total FROM creaciones_terminadas WHERE vendida = TRUE AND fecha_venta::date BETWEEN :desde AND :hasta GROUP BY dia ORDER BY dia'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ordenes por estado para grafico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ordenesPorEstado(): array
    {
        return $this->pdo
            ->query('SELECT estado, total FROM fun_reporte_ordenes_estado()')
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
