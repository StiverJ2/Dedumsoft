<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE UBICACIONES
 * ============================================================================
 *
 * Página de gestión de ubicaciones físicas del almacén.
 * Permite visualizar, agregar, editar y eliminar ubicaciones.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/UbicacionRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(7); // Menú: Configuración
$legacy = page_is_legacy();

// =============================================================================
// DATA LAYER
// =============================================================================

$catRepo = new CatalogoRepository($connLogic);
$ubicRepo = new UbicacionRepository($connLogic);

$area = trim((string) ($_GET['area'] ?? ''));
$area_id = null;
$ubicaciones_rows = [];
$area_options = [];

// Obtener áreas desde catálogo
try {
    $area_options = $catRepo->obtenerOpciones('areas');
} catch (Exception $e) {
    error_log('areas error: ' . $e->getMessage());
    $area_options = [
        ['value' => 1, 'label' => 'General'],
        ['value' => 2, 'label' => 'Producción'],
        ['value' => 3, 'label' => 'Almacén'],
        ['value' => 4, 'label' => 'Ventas'],
        ['value' => 5, 'label' => 'Oficina'],
        ['value' => 6, 'label' => 'Taller']
    ];
}

if ($legacy && $area !== '') {
    if (ctype_digit($area) && (int) $area > 0) {
        $area_id = (int) $area;
    } else {
        foreach ($area_options as $opt) {
            if (strcasecmp((string) ($opt['label'] ?? ''), $area) === 0) {
                $area_id = (int) ($opt['value'] ?? 0);
                break;
            }
        }
    }
}

// Cargar datos para modo legacy
if ($legacy) {
    try {
        $ubic_limit = $area_id !== null ? 200 : 50;
        $ubicaciones_rows = $ubicRepo->listar(0, $ubic_limit, $area_id, true);

        if ($area_id !== null) {
            $ubicaciones_rows = array_values(array_filter($ubicaciones_rows, function ($row) use ($area_id) {
                return (int) ($row['area_id'] ?? 0) === $area_id;
            }));
        }
    } catch (PDOException $e) {
        error_log('ubicaciones legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

$area_value = $area_id !== null ? (string) $area_id : $area;

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(7);
?>
<div class="content">
    <div class="content-header">
        <h1>Ubicaciones</h1>
        <p>Gestión de ubicaciones físicas de maquinaria e insumos</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Listado de ubicaciones</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-ubicacion">+ Nueva Ubicación</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="ubic-filtros-modern">
                <div>
                    <label class="form-label muted" for="ubic-area-modern">Área</label>
                    <select id="ubic-area-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <?php foreach ($area_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>">
                                <?php echo page_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="ubic-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="ubic-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="ubicaciones.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="ubic-area">Área</label>
                    <select id="ubic-area" name="area" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <?php foreach ($area_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>" <?php echo (string) $area_value === (string) $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo page_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="ubicaciones.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="ubicaciones-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($ubicaciones_rows as $row): ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>">
                                <td><?php echo page_e((string) $row['id']); ?></td>
                                <td><?php echo page_e((string) $row['nombre']); ?></td>
                                <td><?php echo page_e((string) ($row['descripcion'] ?? '')); ?></td>
                                <td><?php echo page_e((string) ($row['area_nombre'] ?? 'General')); ?></td>
                                <td><?php echo !empty($row['activo']) ? 'Activo' : 'Inactivo'; ?></td>
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
    window._areaOptions = <?php echo json_encode($area_options); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
