<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE MAQUINARIA
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
$pageData = $ctrl->pageDataMaquinaria($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(2);
render_view('pages/inventario_maquinaria', $pageData);
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._proveedorOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin proveedor --']],
                array_map(function ($p) {
                                return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
                            }, $pageData['proveedor_options'] ?? [])
            ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._ubicacionOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin ubicacion --']],
                array_map(function ($u) {
                    return ['value' => $u['id'], 'label' => $u['nombre']];
                }, $pageData['ubicacion_options'] ?? [])
            ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._tipoMaquinariaOptions = <?php echo json_encode(array_map(function ($t) {
                return ['value' => $t['id'], 'label' => $t['nombre']];
            }, $pageData['tipo_maquinaria_options'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._maqEstadoOptions = <?php echo json_encode(array_map(function ($e) {
                return ['value' => $e['id'], 'label' => $e['nombre']];
            }, $pageData['maq_estado_options'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    window._maqInventoryOptions = <?php echo json_encode(array_map(function ($row) {
                $label = ($row['sku'] ?? '') !== '' ? $row['sku'] . ' - ' : '';
                $label .= ($row['nombre'] ?? 'Maquinaria') . ' #' . $row['id'];
                return ['value' => $row['id'], 'label' => $label];
            }, $pageData['maq_rows'] ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
