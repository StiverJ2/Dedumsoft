<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE INSUMOS
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
$pageData = $ctrl->pageDataInsumos($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(2);
render_view('pages/inventario_insumos', $pageData);
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._categoriaOptions = <?php echo json_encode(array_map(function($c) {
        return ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))];
    }, $pageData['categoria_options'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $pageData['proveedor_options'] ?? [])
    ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._insumoInventoryOptions = <?php echo json_encode(array_map(function($row) {
        $label = $row['nombre'] ?? ('Insumo #' . $row['id']);
        $label .= ' #' . $row['id'];
        return ['value' => $row['id'], 'label' => $label];
    }, $pageData['insumo_rows'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
