<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE MAQUINARIA
 * ============================================================================
 *
 * Página de gestión del inventario de maquinaria (equipos y herramientas).
 * Permite visualizar, agregar, editar y eliminar registros de maquinaria.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';
require_once PRIVATE_PATH . '/Repositories/UbicacionRepository.php';
require_once PRIVATE_PATH . '/Repositories/InventarioMaquinariaRepository.php';

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
$ubicRepo = new UbicacionRepository($connLogic);
$maqRepo = new InventarioMaquinariaRepository($connLogic);

$maq_estado_raw = trim((string) ($_GET['maq_estado_id'] ?? ''));
$maq_estado_id = null;
$maq_rows = [];
$proveedor_options = [];
$ubicacion_options = [];
$tipo_maquinaria_options = [];
$maq_estado_options = [];

// Cargar proveedores para dropdown
try {
    $proveedor_options = $provRepo->listar(0, 1000, null, true);
} catch (PDOException $e) {
    error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Cargar ubicaciones para dropdown
try {
    $ubicacion_options = $ubicRepo->listar(0, 1000, null, true);
} catch (PDOException $e) {
    error_log('inventario ubicaciones error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Cargar tipos de maquinaria
try {
    $tipo_maquinaria_options = $catRepo->obtenerTiposMaquinaria();
} catch (PDOException $e) {
    error_log('inventario tipos_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Obtener opciones de estados de maquinaria desde la BD
try {
    $maq_estado_options = $catRepo->obtenerOpciones('estados_maquinaria');
} catch (Exception $e) {
    error_log('inventario estados_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($maq_estado_raw !== '') {
    if (ctype_digit($maq_estado_raw) && (int) $maq_estado_raw > 0) {
        $maq_estado_id = (int) $maq_estado_raw;
    } else {
        foreach ($maq_estado_options as $opt) {
            if (strcasecmp((string) ($opt['label'] ?? ''), $maq_estado_raw) === 0) {
                $maq_estado_id = (int) ($opt['value'] ?? 0);
                break;
            }
        }
    }
}

if ($legacy) {
    try {
        $maq_limit = $maq_estado_id !== null ? 200 : 20;
        $maq_rows = $maqRepo->listar(0, $maq_limit, $maq_estado_id, true);

        if ($maq_estado_id !== null) {
            $maq_rows = array_values(array_filter($maq_rows, function ($row) use ($maq_estado_id) {
                return (int) ($row['estado_id'] ?? 0) === $maq_estado_id;
            }));
        }
    } catch (PDOException $e) {
        error_log('inventario legacy maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(2);
?>
<div class="content">
    <div class="content-header">
        <h1>Maquinaria</h1>
        <p>Control de equipos y mantenimiento</p>
    </div>

    <div class="card" id="inv-maquinaria">
        <div class="ds-toolbar">
            <strong>Maquinaria</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-maquinaria">+ Nueva Maquinaria</button>
                <button type="button" class="btn-add" id="btn-compra-maquinaria">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
        <form class="d-flex flex-wrap gap-2 align-items-end" id="maq-filtros-modern">
            <div>
                <label class="form-label muted" for="maq-estado-modern">Estado</label>
                <select id="maq-estado-modern" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($maq_estado_options as $est): ?>
                    <option value="<?php echo (int) $est['id']; ?>">
                        <?php echo page_e((string) $est['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-sm" type="button" id="maq-filtrar-modern">Aplicar</button>
            <button class="btn btn-sm btn-secondary" type="button" id="maq-limpiar-modern">Limpiar</button>
        </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
        <form method="get" action="inventario_maquinaria.php#inv-maquinaria"
            class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="maq-estado-id">Estado</label>
                <select id="maq-estado-id" name="maq_estado_id" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($maq_estado_options as $est): ?>
                    <option value="<?php echo (int) $est['id']; ?>"
                        <?php echo $maq_estado_id === (int) $est['id'] ? 'selected' : ''; ?>>
                        <?php echo page_e($est['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario_maquinaria.php#inv-maquinaria" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="maq-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Ubicacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($maq_rows as $row): ?>
                    <tr>
                        <td><?php echo page_e((string) $row['id']); ?></td>
                        <td><?php echo page_e((string) ($row['sku'] ?? '')); ?></td>
                        <td><?php echo page_e((string) $row['nombre']); ?></td>
                        <td><?php echo page_e((string) ($row['tipo_nombre'] ?? '')); ?></td>
                        <td><?php echo page_e((string) ($row['estado_nombre'] ?? '')); ?></td>
                        <td><?php echo page_e((string) ($row['ubicacion_nombre'] ?? '')); ?></td>
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
    window._proveedorOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin proveedor --']],
                array_map(function ($p) {
                                return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
                            }, $proveedor_options)
            )); ?>;
    window._ubicacionOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin ubicacion --']],
                array_map(function ($u) {
                    return ['value' => $u['id'], 'label' => $u['nombre']];
                }, $ubicacion_options)
            )); ?>;
    window._tipoMaquinariaOptions = <?php echo json_encode(array_map(function ($t) {
                return ['value' => $t['id'], 'label' => $t['nombre']];
            }, $tipo_maquinaria_options)); ?>;
    window._maqEstadoOptions = <?php echo json_encode(array_map(function ($e) {
                return ['value' => $e['id'], 'label' => $e['nombre']];
            }, $maq_estado_options)); ?>;
    window._maqInventoryOptions = <?php echo json_encode(array_map(function ($row) {
                $label = ($row['sku'] ?? '') !== '' ? $row['sku'] . ' - ' : '';
                $label .= ($row['nombre'] ?? 'Maquinaria') . ' #' . $row['id'];
                return ['value' => $row['id'], 'label' => $label];
            }, $maq_rows)); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
