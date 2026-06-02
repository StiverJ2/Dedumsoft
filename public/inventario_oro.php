<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE ORO
 * ============================================================================
 *
 * Vista: delega toda la logica de negocio a InventarioController.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/InventarioController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(2); // Menú: Inventario
$legacy = page_is_legacy();

$ctrl = new InventarioController($connLogic);
$pageData = $ctrl->pageDataOro($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(2);
render_view('pages/inventario_oro', $pageData);
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._oroTipoOptions = <?php echo json_encode($pageData['oro_tipo_options'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function ($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $pageData['proveedor_options'] ?? [])
    ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._oroInventoryOptions = <?php echo json_encode(array_map(function ($row) {
        return [
            'value' => $row['id'],
            'label' => ($row['tipo_oro_nombre'] ?? 'Oro') . ' #' . $row['id']
        ];
    }, $pageData['oro_rows'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
