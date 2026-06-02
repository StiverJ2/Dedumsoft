<?php
/**
 * ============================================================================
 * REPORTE CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para reportes gerenciales.
 *
 * Unifica el comportamiento entre:
 * - public/reportes.php (vista HTML)
 * - public/api/reportes/*.php (APIs JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/ReporteRepository.php';

class ReporteController extends Controller
{
    /** @var ReporteRepository */
    private $repo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new ReporteRepository($pdo);
    }

    // ========================================================================
    // REPORTES (para APIs y vistas)
    // ========================================================================

    /**
     * Reporte de produccion por periodo.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteProduccion(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->produccion($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteProduccion error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de inventario consolidado.
     *
     * @return array<string, mixed>
     */
    public function reporteInventario(): array
    {
        try {
            $rows = $this->repo->inventario();
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteInventario error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de eficiencia de artesanos.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteEficiencia(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->eficienciaArtesanos($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteEficiencia error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de uso de materiales.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteMateriales(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->usoMateriales($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteMateriales error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de ventas.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteVentas(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->ventas($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteVentas error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de compras.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function reporteCompras(string $desde, string $hasta): array
    {
        try {
            $rows = $this->repo->compras($desde, $hasta);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteCompras error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Reporte de usuarios.
     *
     * @return array<string, mixed>
     */
    public function reporteUsuarios(): array
    {
        try {
            $rows = $this->repo->usuarios();
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ReporteController::reporteUsuarios error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Datos agregados para graficos PNG del modo legacy.
     *
     * @param string $chart
     * @param string $desde
     * @param string $hasta
     * @return array<string, mixed>
     */
    public function legacyChartData(string $chart, string $desde, string $hasta): array
    {
        try {
            switch ($chart) {
                case 'produccion':
                    return $this->success($this->countBy($this->repo->produccion($desde, $hasta), 'estado', 'total'), 'OK', 200);
                case 'inventario':
                    return $this->success($this->countBy($this->repo->inventario(), 'tipo', 'total'), 'OK', 200);
                case 'eficiencia':
                    return $this->success($this->repo->eficienciaArtesanos($desde, $hasta), 'OK', 200);
                case 'materiales':
                    return $this->success($this->sumBy($this->repo->usoMateriales($desde, $hasta), 'tipo_material', 'costo_total', 'total'), 'OK', 200);
                case 'ventas':
                    return $this->success($this->sumBy($this->repo->ventas($desde, $hasta), 'fecha_venta', 'precio_venta', 'total', 'dia'), 'OK', 200);
                case 'compras':
                    return $this->success($this->sumBy($this->repo->compras($desde, $hasta), 'tipo_inventario', 'cantidad_total', 'total'), 'OK', 200);
                case 'usuarios':
                    return $this->success($this->countBy($this->repo->usuarios(), 'rol', 'total'), 'OK', 200);
                case 'ventas_mes':
                    return $this->success($this->repo->ventasChart($desde, $hasta), 'OK', 200);
                case 'ordenes_estado':
                    return $this->success($this->repo->ordenesPorEstado(), 'OK', 200);
            }

            return $this->error('Tipo de grafico no soportado.', 400);
        } catch (PDOException $e) {
            error_log('ReporteController::legacyChartData error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Cuenta filas agrupadas por una columna.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string $groupKey
     * @param string $totalKey
     * @return array<int, array<string, mixed>>
     */
    private function countBy(array $rows, string $groupKey, string $totalKey): array
    {
        $totals = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row[$groupKey] ?? ''));
            if ($key === '') {
                $key = 'Sin datos';
            }
            $totals[$key] = ($totals[$key] ?? 0) + 1;
        }
        ksort($totals);

        $result = [];
        foreach ($totals as $key => $total) {
            $result[] = [$groupKey => $key, $totalKey => $total];
        }
        return $result;
    }

    /**
     * Suma valores agrupados por una columna.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string $groupKey
     * @param string $valueKey
     * @param string $totalKey
     * @param string|null $outputGroupKey
     * @return array<int, array<string, mixed>>
     */
    private function sumBy(array $rows, string $groupKey, string $valueKey, string $totalKey, ?string $outputGroupKey = null): array
    {
        $totals = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row[$groupKey] ?? ''));
            if ($key === '') {
                $key = 'Sin datos';
            }
            if ($groupKey === 'fecha_venta') {
                $key = substr($key, 0, 10);
            }
            $totals[$key] = ($totals[$key] ?? 0) + (float) ($row[$valueKey] ?? 0);
        }
        ksort($totals);

        $result = [];
        $groupOut = $outputGroupKey ?: $groupKey;
        foreach ($totals as $key => $total) {
            $result[] = [$groupOut => $key, $totalKey => $total];
        }
        return $result;
    }

    // ========================================================================
    // LEGACY PAGE HELPERS (para public/reportes.php)
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de reportes.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageData(array $get, bool $legacy): array
    {
        $desde = $this->toString($get['desde'] ?? date('Y-m-01'));
        $hasta = $this->toString($get['hasta'] ?? date('Y-m-t'));
        $input_type = $legacy ? 'text' : 'date';
        $cache_bust = $this->toString($get['cb'] ?? '');
        $chart_params = 'desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);
        if ($cache_bust !== '') {
            $chart_params .= '&cb=' . urlencode($cache_bust);
        }

        $rep_produccion = [];
        $rep_inventario = [];
        $rep_eficiencia = [];
        $rep_materiales = [];
        $rep_ventas = [];
        $rep_compras = [];
        $rep_usuarios = [];

        if ($legacy) {
            $result = $this->reporteProduccion($desde, $hasta);
            if ($result['success']) {
                $rep_produccion = $result['data'];
            }

            $result = $this->reporteInventario();
            if ($result['success']) {
                $rep_inventario = $result['data'];
            }

            $result = $this->reporteEficiencia($desde, $hasta);
            if ($result['success']) {
                $rep_eficiencia = $result['data'];
            }

            $result = $this->reporteMateriales($desde, $hasta);
            if ($result['success']) {
                $rep_materiales = $result['data'];
            }

            $result = $this->reporteVentas($desde, $hasta);
            if ($result['success']) {
                $rep_ventas = $result['data'];
            }

            $result = $this->reporteCompras($desde, $hasta);
            if ($result['success']) {
                $rep_compras = $result['data'];
            }

            $result = $this->reporteUsuarios();
            if ($result['success']) {
                $rep_usuarios = $result['data'];
            }
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'input_type' => $input_type,
            'chart_params' => $chart_params,
            'rep_produccion' => $rep_produccion,
            'rep_inventario' => $rep_inventario,
            'rep_eficiencia' => $rep_eficiencia,
            'rep_materiales' => $rep_materiales,
            'rep_ventas' => $rep_ventas,
            'rep_compras' => $rep_compras,
            'rep_usuarios' => $rep_usuarios,
            'legacy' => $legacy,
        ];
    }
}
