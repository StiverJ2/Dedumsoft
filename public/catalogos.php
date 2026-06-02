<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: CATÁLOGOS MAESTROS
 * ============================================================================
 *
 * Delega toda la lógica de negocio a CatalogoController y la vista a
 * views/pages/catalogos.php.
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
$pageData = $ctrl->pageDataCatalogos($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(7);
render_view('pages/catalogos', $pageData);
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._catalogKey = '<?php echo page_e($pageData['selected_catalog'] ?? ''); ?>';
    window._catalogConfig = <?php echo json_encode($pageData['catalogs'][$pageData['selected_catalog']] ?? null); ?>;
</script>
<?php else: ?>
<script>
    window._catalogs = <?php echo json_encode($pageData['ui_catalogs'] ?? []); ?>;
    window._currentCatalogKey = '<?php echo page_e($pageData['selected_catalog'] ?? ''); ?>';
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
