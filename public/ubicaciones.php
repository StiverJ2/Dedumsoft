<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE UBICACIONES
 * ============================================================================
 *
 * Delega toda la lógica de negocio a CatalogoController y la vista a
 * views/pages/ubicaciones.php.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/CatalogoController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(7); // Menú: Configuración
$legacy = page_is_legacy();

$ctrl = new CatalogoController($connLogic);
$pageData = $ctrl->pageDataUbicaciones($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(7);
render_view('pages/ubicaciones', $pageData);
page_render_end(function () use ($legacy, $pageData) {
    if ($legacy) {
        ?>
        <script>
            window._areaOptions = <?php echo json_encode($pageData['area_options'] ?? []); ?>;
        </script>
        <?php
    }
    ?>
    <script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
    <?php
});
