<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE PROVEEDORES
 * ============================================================================
 * 
 * Página de gestión de proveedores de materiales.
 * Permite visualizar, agregar, editar y eliminar proveedores.
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(6); // Menú: Proveedores
$legacy = page_is_legacy();

$repo    = new ProveedorRepository($connLogic);
$catRepo = new CatalogoRepository($connLogic);

// =============================================================================
// DATA LAYER
// =============================================================================

$tipo = trim((string) ($_GET['tipo'] ?? ''));
$tipo_id = null;
$proveedores_rows = [];
$tipo_proveedor_options = [];

if ($legacy) {
    // Obtener tipos de proveedor desde catálogo
    try {
        $tipo_proveedor_options = $catRepo->obtenerOpciones('tipos_proveedor');
    } catch (Exception $e) {
        error_log('tipos proveedor error: ' . $e->getMessage());
        $tipo_proveedor_options = [
            ['value' => 1, 'label' => 'Oro'],
            ['value' => 2, 'label' => 'Insumos'],
            ['value' => 3, 'label' => 'Maquinaria']
        ];
    }

    if ($tipo !== '') {
        if (ctype_digit($tipo) && (int) $tipo > 0) {
            $tipo_id = (int) $tipo;
        } else {
            foreach ($tipo_proveedor_options as $opt) {
                if (strcasecmp((string) ($opt['label'] ?? ''), $tipo) === 0) {
                    $tipo_id = (int) ($opt['value'] ?? 0);
                    break;
                }
            }
        }
    }

    // Cargar datos para modo legacy (server-side rendering)
    try {
        $prov_limit = $tipo_id !== null ? 200 : 20;
        $proveedores_rows = $repo->listar(0, $prov_limit, $tipo_id, true);

        if ($tipo_id !== null) {
            $proveedores_rows = array_values(array_filter($proveedores_rows, function ($row) use ($tipo_id) {
                return (int) ($row['tipo_proveedor_id'] ?? 0) === $tipo_id;
            }));
        }
    } catch (PDOException $e) {
        error_log('proveedores legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

$tipo_value = $tipo_id !== null ? (string) $tipo_id : $tipo;

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(6);
?>
<div class="content">
    <div class="content-header">
        <h1>Proveedores</h1>
        <p>Gestion de proveedores de materiales</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Listado de proveedores</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-proveedor">+ Nuevo Proveedor</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="prov-filtros-modern">
                <div>
                    <label class="form-label muted" for="prov-tipo-modern">Tipo</label>
                    <select id="prov-tipo-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($tipo_proveedor_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>">
                                <?php echo page_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="prov-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="prov-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="proveedores.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="prov-tipo">Tipo</label>
                    <select id="prov-tipo" name="tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($tipo_proveedor_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>" <?php echo (string) $tipo_value === (string) $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo page_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="proveedores.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="proveedores-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Contacto</th>
                        <th>Telefono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($proveedores_rows as $row): ?>
                            <tr>
                                <td><?php echo page_e((string) $row['id']); ?></td>
                                <td><?php echo page_e((string) $row['nombre']); ?></td>
                                <td><?php echo page_e((string) $row['tipo_nombre']); ?></td>
                                <td><?php echo page_e((string) ($row['contacto'] ?? '')); ?></td>
                                <td><?php echo page_e((string) ($row['telefono'] ?? '')); ?></td>
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

// =============================================================================
// SCRIPTS (externalizados)
// =============================================================================
?>
<?php if ($legacy): ?>
<script>
    window._tipoProveedorOptions = <?php echo json_encode($tipo_proveedor_options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/proveedores<?php echo $legacy ? '-legacy' : ''; ?>.js"></script>
