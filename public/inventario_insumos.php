<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE INSUMOS
 * ============================================================================
 *
 * Página de gestión del inventario de insumos (materiales consumibles).
 * Permite visualizar, agregar, editar y eliminar registros de insumos.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/InventarioInsumosRepository.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(2); // Menú: Inventario
$legacy = page_is_legacy();

// =============================================================================
// DATA LAYER
// =============================================================================

$insRepo = new InventarioInsumosRepository($connLogic);
$provRepo = new ProveedorRepository($connLogic);

$insumo_categoria = trim((string) ($_GET['insumo_categoria'] ?? ''));
$insumo_stock_bajo = isset($_GET['insumo_stock_bajo']) && $_GET['insumo_stock_bajo'] !== '0';
$insumo_rows = [];
$categoria_options = [];
$proveedor_options = [];

// Cargar opciones de categorías (valores distintos en BD)
try {
    $categoria_options = $insRepo->obtenerCategorias();
} catch (PDOException $e) {
    error_log('inventario categorias error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Cargar proveedores para dropdown
try {
    $proveedor_options = $provRepo->listar(0, 1000, null, true);
} catch (PDOException $e) {
    error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $insumo_limit = ($insumo_categoria !== '' || $insumo_stock_bajo) ? 200 : 20;
        $insumo_rows = $insRepo->listar(0, $insumo_limit, $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_stock_bajo, true);

        if ($insumo_categoria !== '') {
            $insumo_rows = array_values(array_filter($insumo_rows, function ($row) use ($insumo_categoria) {
                return strcasecmp(trim((string) ($row['categoria'] ?? '')), $insumo_categoria) === 0;
            }));
        }
        if ($insumo_stock_bajo) {
            $insumo_rows = array_values(array_filter($insumo_rows, function ($row) {
                $cantidad = isset($row['cantidad']) ? (float) $row['cantidad'] : 0;
                $stock_minimo = isset($row['stock_minimo']) ? (float) $row['stock_minimo'] : 0;
                return $cantidad <= $stock_minimo;
            }));
        }
    } catch (PDOException $e) {
        error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(2);
?>
<div class="content">
    <div class="content-header">
        <h1>Insumos</h1>
        <p>Control de materiales y consumibles</p>
    </div>

    <div class="card" id="inv-insumos">
        <div class="ds-toolbar">
            <strong>Insumos</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-insumo">+ Nuevo Insumo</button>
                <button type="button" class="btn-add" id="btn-compra-insumo">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
        <form class="d-flex flex-wrap gap-3 align-items-end" id="insumo-filtros-modern">
            <div>
                <label class="form-label muted" for="insumo-categoria-modern">Categoria</label>
                <select id="insumo-categoria-modern" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                    <option value="<?php echo page_e((string) $categoria); ?>">
                        <?php echo page_e(ucwords(str_replace('_', ' ', (string) $categoria))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo-modern">
                <label class="form-check-label muted" for="insumo-stock-bajo-modern">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" type="button" id="insumo-filtrar-modern">Aplicar</button>
            <button class="btn btn-sm btn-secondary" type="button" id="insumo-limpiar-modern">Limpiar</button>
        </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
        <form method="get" action="inventario_insumos.php#inv-insumos" class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label muted" for="insumo-categoria">Categoria</label>
                <select id="insumo-categoria" name="insumo_categoria" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                    <option value="<?php echo page_e((string) $categoria); ?>"
                        <?php echo $insumo_categoria === $categoria ? 'selected' : ''; ?>>
                        <?php echo page_e((string) $categoria); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo" name="insumo_stock_bajo"
                    value="1" <?php echo $insumo_stock_bajo ? 'checked' : ''; ?>>
                <label class="form-check-label muted" for="insumo-stock-bajo">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario_insumos.php#inv-insumos" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="insumos-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoria</th>
                        <th>Cantidad</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($insumo_rows as $row): ?>
                    <tr>
                        <td><?php echo page_e((string) $row['id']); ?></td>
                        <td><?php echo page_e((string) $row['nombre']); ?></td>
                        <td><?php echo page_e((string) $row['categoria']); ?></td>
                        <td><?php echo page_e((string) $row['cantidad']); ?></td>
                        <td><?php echo page_e((string) ($row['proveedor_nombre'] ?? '')); ?></td>
                        <td class="ds-actions-col"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._categoriaOptions = <?php echo json_encode(array_map(function($c) {
        return ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))];
    }, $categoria_options)); ?>;
    window._proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $proveedor_options)
    )); ?>;
    window._insumoInventoryOptions = <?php echo json_encode(array_map(function($row) {
        $label = $row['nombre'] ?? ('Insumo #' . $row['id']);
        $label .= ' #' . $row['id'];
        return ['value' => $row['id'], 'label' => $label];
    }, $insumo_rows)); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
