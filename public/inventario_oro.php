<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE ORO
 * ============================================================================
 *
 * Página de gestión del inventario de oro (metales preciosos).
 * Permite visualizar, agregar, editar y eliminar registros de oro.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';
require_once PRIVATE_PATH . '/Repositories/InventarioOroRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(2); // Menú: Inventario
$legacy = page_is_legacy();

// =============================================================================
// DATA LAYER
// =============================================================================

$catRepo = new CatalogoRepository($connLogic);
$provRepo = new ProveedorRepository($connLogic);
$oroRepo = new InventarioOroRepository($connLogic);

$oro_tipo = trim((string) ($_GET['oro_tipo'] ?? ''));
$oro_tipo_id = null;
$oro_rows = [];
$proveedor_options = [];
$oro_tipo_options = [];

// Cargar proveedores para dropdown
try {
    $proveedor_options = $provRepo->listar(0, 1000, null, true);
} catch (PDOException $e) {
    error_log('inventario oro proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Obtener opciones de tipos de oro desde catálogo
try {
    $oro_tipo_options = $catRepo->obtenerOpciones('tipos_oro');
} catch (Exception $e) {
    error_log('tipos oro error: ' . $e->getMessage());
    $oro_tipo_options = [
        ['value' => 1, 'label' => '10k'],
        ['value' => 2, 'label' => '14k'],
        ['value' => 3, 'label' => '18k'],
        ['value' => 4, 'label' => '22k'],
        ['value' => 5, 'label' => '24k']
    ];
}

if ($legacy && $oro_tipo !== '') {
    if (ctype_digit($oro_tipo) && (int) $oro_tipo > 0) {
        $oro_tipo_id = (int) $oro_tipo;
    } else {
        foreach ($oro_tipo_options as $opt) {
            if (strcasecmp((string) ($opt['label'] ?? ''), $oro_tipo) === 0) {
                $oro_tipo_id = (int) ($opt['value'] ?? 0);
                break;
            }
        }
    }
}

if ($legacy) {
    try {
        $oro_limit = $oro_tipo_id !== null ? 200 : 20;
        $oro_rows = $oroRepo->listar(0, $oro_limit, $oro_tipo_id, true);

        if ($oro_tipo_id !== null) {
            $oro_rows = array_values(array_filter($oro_rows, function ($row) use ($oro_tipo_id) {
                return (int) ($row['tipo_oro_id'] ?? 0) === $oro_tipo_id;
            }));
        }
    } catch (PDOException $e) {
        error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

$oro_tipo_value = $oro_tipo_id !== null ? (string) $oro_tipo_id : $oro_tipo;

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(2);
?>
<div class="content">
    <div class="content-header">
        <h1>Inventario de oro</h1>
        <p>Control de metales</p>
    </div>

    <div class="card" id="inv-oro">
        <div class="ds-toolbar">
            <strong>Inventario de oro</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-oro">+ Nuevo Oro</button>
                <button type="button" class="btn-add" id="btn-compra-oro">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="oro-filtros-modern">
                <div>
                    <label class="form-label muted" for="oro-tipo-modern">Tipo</label>
                    <select id="oro-tipo-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($oro_tipo_options as $tipo): ?>
                            <option value="<?php echo (int) $tipo['value']; ?>">
                                <?php echo page_e((string) $tipo['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="oro-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="oro-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="inventario_oro.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="oro-tipo">Tipo</label>
                    <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($oro_tipo_options as $tipo): ?>
                            <option value="<?php echo (int) $tipo['value']; ?>" <?php echo (string) $oro_tipo_value === (string) $tipo['value'] ? 'selected' : ''; ?>>
                                <?php echo page_e((string) $tipo['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="inventario_oro.php#inv-oro" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="oro-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Peso</th>
                        <th>Precio</th>
                        <th>Proveedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($oro_rows as $row): ?>
                            <tr>
                                <td><?php echo page_e((string) $row['id']); ?></td>
                                <td><?php echo page_e((string) $row['tipo_oro_nombre']); ?></td>
                                <td><?php echo page_e((string) $row['peso_gramos']); ?></td>
                                <td><?php echo page_e((string) $row['precio_gramo']); ?></td>
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
    window._oroTipoOptions = <?php echo json_encode($oro_tipo_options); ?>;
    window._proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function ($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $proveedor_options)
    )); ?>;
    window._oroInventoryOptions = <?php echo json_encode(array_map(function ($row) {
        return [
            'value' => $row['id'],
            'label' => ($row['tipo_oro_nombre'] ?? 'Oro') . ' #' . $row['id']
        ];
    }, $oro_rows)); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
