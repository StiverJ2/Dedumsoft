<?php
/**
 * ============================================================================
 * PAGINA PUBLICA: GESTION DE PRODUCCION
 * ============================================================================
 *
 * Pagina de gestion de ordenes de produccion.
 * Permite visualizar y asignar ordenes a artesanos.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/OrdenController.php';
require_once PRIVATE_PATH . '/view_helper.php';

// =============================================================================
// INICIALIZACION
// =============================================================================
page_init(3); // Menu: Produccion
$legacy = page_is_legacy();
$current_user = get_session_user();
$is_operador = (int) ($current_user['rolid'] ?? 0) === 2;

// =============================================================================
// DATA LAYER
// =============================================================================
$ctrl = new OrdenController($connLogic);
$pageData = $ctrl->pageDataOrdenes($_GET, $legacy, $is_operador);
$pageData['legacy'] = $legacy;

// =============================================================================
// RENDER
// =============================================================================
page_render_start(3);
render_view('pages/produccion', $pageData);
page_render_end(function () use ($legacy, $pageData) {
    if ($legacy) {
        ?>
        <script>
            window._artesanosOptions = <?php echo json_encode($pageData['artesanos_options'] ?? []); ?>;
            window._productosOptions = <?php echo json_encode($pageData['productos_options'] ?? []); ?>;
            window._prioridadesOptions = <?php echo json_encode($pageData['prioridades_options'] ?? []); ?>;
        </script>
        <?php
    }
    ?>
    <script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
    <?php
});
