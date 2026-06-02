<?php
/**
 * ============================================================================
 * PAGINA PUBLICA: DASHBOARD PRINCIPAL
 * ============================================================================
 *
 * Orquesta datos del dashboard y delega el render a views/pages/index.php.
 *
 * @package Dedumsoft\Public
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';
require_once PRIVATE_PATH . '/Repositories/ReporteRepository.php';

page_init(1); // Menu: Dashboard
$legacy = page_is_legacy();
$load_uplot = !$legacy;

$cache_bust = trim((string) ($_GET['cb'] ?? ''));
$chart_cb = $cache_bust !== '' ? '&cb=' . urlencode($cache_bust) : '';
$chart_cb_q = $cache_bust !== '' ? '?cb=' . urlencode($cache_bust) : '';

$ordenRepo = new OrdenRepository($connLogic);
$repRepo = new ReporteRepository($connLogic);

$ordenes = [];
try {
    $ordenes = $ordenRepo->listar(0, 5, null);
} catch (PDOException $e) {
    error_log('dashboard ordenes error: ' . $e->getMessage());
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
    $inventario_total = $repRepo->contarInventarioTotal();
    $inventario_stock_bajo = $repRepo->contarStockBajo();
    $ventas_mes_total = $repRepo->ventasMesTotal($month_start, $month_end);

    $ordenes_dashboard = $repRepo->ordenesDashboard($month_start, $month_end);
    $ordenes_activas = (int) ($ordenes_dashboard['ordenes_activas'] ?? 0);
    $ordenes_completadas_mes = (int) ($ordenes_dashboard['ordenes_completadas'] ?? 0);

    foreach ($repRepo->ventasChart($month_start, $month_end) as $row) {
        $ventas_chart[] = [strtotime((string) $row['dia']), (float) $row['total']];
    }

    $ordenes_estado = $repRepo->ordenesPorEstado();
} catch (PDOException $e) {
    error_log('dashboard metrics error: ' . $e->getMessage());
}

$pageData = [
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
    'icon_inventory' => page_icon('inventory', $legacy),
    'icon_sales' => page_icon('sales', $legacy),
    'icon_orders' => page_icon('orders', $legacy),
    'icon_done' => page_icon('done', $legacy),
];

page_render_start(1, $load_uplot ? '1' : null);
render_view('pages/index', $pageData);
page_render_end(function () use ($legacy, $ventas_chart, $ordenes_estado) {
    if ($legacy) {
        return;
    }
    ?>
    <script>
        window._ventasSeries = <?php echo json_encode($ventas_chart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window._ordenesEstado = <?php echo json_encode($ordenes_estado, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/pages/index.js"></script>
    <?php
});
