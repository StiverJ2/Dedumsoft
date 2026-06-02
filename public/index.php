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
require_once PRIVATE_PATH . '/Controllers/DashboardController.php';

page_init(1); // Menu: Dashboard
$legacy = page_is_legacy();
$load_uplot = !$legacy;

$ctrl = new DashboardController($connLogic);
$pageData = $ctrl->pageData($_GET, $legacy);
$pageData['icon_inventory'] = page_icon('inventory', $legacy);
$pageData['icon_sales'] = page_icon('sales', $legacy);
$pageData['icon_orders'] = page_icon('orders', $legacy);
$pageData['icon_done'] = page_icon('done', $legacy);

page_render_start(1, $load_uplot ? '1' : null);
render_view('pages/index', $pageData);
page_render_end(function () use ($legacy, $pageData) {
    if ($legacy) {
        return;
    }
    ?>
    <script>
        window._ventasSeries = <?php echo json_encode($pageData['ventas_chart'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window._ordenesEstado = <?php echo json_encode($pageData['ordenes_estado'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/pages/index.js"></script>
    <?php
});
