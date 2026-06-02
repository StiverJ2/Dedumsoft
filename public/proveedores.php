<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE PROVEEDORES
 * ============================================================================
 *
 * Delega toda la lógica de negocio a ProveedorController y la vista a
 * views/pages/proveedores.php.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/ProveedorController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(6); // Menú: Proveedores
$legacy = page_is_legacy();

$ctrl = new ProveedorController($connLogic);
$pageData = $ctrl->pageData($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(6);
render_view('pages/proveedores', $pageData);
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._tipoProveedorOptions = <?php echo json_encode($pageData['tipo_proveedor_options'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/proveedores<?php echo $legacy ? '-legacy' : ''; ?>.js"></script>
