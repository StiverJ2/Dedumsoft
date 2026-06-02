<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: REPORTES GERENCIALES
 * ============================================================================
 *
 * Centro de reportes con múltiples vistas de datos consolidados.
 * Incluye gráficos interactivos (uPlot) en modo moderno.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/ReporteController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(4); // Menú: Reportes
$legacy = page_is_legacy();

$ctrl = new ReporteController($connLogic);
$pageData = $ctrl->pageData($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(4);
render_view('pages/reportes', $pageData);
page_render_end(function () use ($legacy) {
    ?>
    <script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
    <?php
});
