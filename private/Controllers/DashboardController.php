<?php
/**
 * ============================================================================
 * DASHBOARD CONTROLLER
 * ============================================================================
 *
 * Prepara los datos del dashboard principal.
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';
require_once PRIVATE_PATH . '/Repositories/ReporteRepository.php';

class DashboardController extends Controller
{
    /** @var OrdenRepository */
    private $ordenRepo;

    /** @var ReporteRepository */
    private $repRepo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->ordenRepo = new OrdenRepository($pdo);
        $this->repRepo = new ReporteRepository($pdo);
    }

    /**
     * Prepara los datos necesarios para renderizar el dashboard.
     *
     * @param array $get
     * @param bool $legacy
     * @return array<string, mixed>
     */
    public function pageData(array $get, bool $legacy): array
    {
        $cache_bust = trim((string) ($get['cb'] ?? ''));
        $chart_cb = $cache_bust !== '' ? '&cb=' . urlencode($cache_bust) : '';
        $chart_cb_q = $cache_bust !== '' ? '?cb=' . urlencode($cache_bust) : '';

        $ordenes = [];
        try {
            $ordenes = $this->ordenRepo->listar(0, 5, null);
        } catch (PDOException $e) {
            error_log('DashboardController::ordenes error: ' . $e->getMessage());
        }

        $inventario_total = 0;
        $inventario_stock_bajo = 0;
        $ventas_mes_total = 0.0;
        $ordenes_activas = 0;
        $ordenes_completadas_mes = 0;
        $ventas_chart = [];
        $ordenes_estado = [];

        $month_start = date('Y-m-01');
        $month_end = date('Y-m-t');

        try {
            $inventario_total = $this->repRepo->contarInventarioTotal();
            $inventario_stock_bajo = $this->repRepo->contarStockBajo();
            $ventas_mes_total = $this->repRepo->ventasMesTotal($month_start, $month_end);

            $ordenes_dashboard = $this->repRepo->ordenesDashboard($month_start, $month_end);
            $ordenes_activas = (int) ($ordenes_dashboard['ordenes_activas'] ?? 0);
            $ordenes_completadas_mes = (int) ($ordenes_dashboard['ordenes_completadas'] ?? 0);

            foreach ($this->repRepo->ventasChart($month_start, $month_end) as $row) {
                $ventas_chart[] = [strtotime((string) $row['dia']), (float) $row['total']];
            }

            $ordenes_estado = $this->repRepo->ordenesPorEstado();
        } catch (PDOException $e) {
            error_log('DashboardController::metricas error: ' . $e->getMessage());
        }

        return [
            'legacy' => $legacy,
            'ordenes' => $ordenes,
            'inventario_total' => $inventario_total,
            'inventario_stock_bajo' => $inventario_stock_bajo,
            'ventas_mes_total' => $ventas_mes_total,
            'ordenes_activas' => $ordenes_activas,
            'ordenes_completadas_mes' => $ordenes_completadas_mes,
            'ventas_chart' => $ventas_chart,
            'ordenes_estado' => $ordenes_estado,
            'month_start' => $month_start,
            'month_end' => $month_end,
            'chart_cb' => $chart_cb,
            'chart_cb_q' => $chart_cb_q,
        ];
    }
}
