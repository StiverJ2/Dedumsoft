<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE PRODUCCIÓN
 * ============================================================================
 *
 * Página de gestión de órdenes de producción.
 * Permite visualizar y asignar órdenes a artesanos.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(3); // Menú: Producción
$legacy = page_is_legacy();
$current_user = get_session_user();
$is_operador = (int) ($current_user['rolid'] ?? 0) === 2;

// =============================================================================
// DATA LAYER
// =============================================================================

$catRepo = new CatalogoRepository($connLogic);
$ordenRepo = new OrdenRepository($connLogic);

$estado = trim((string) ($_GET['estado'] ?? ''));
$estado_filtrado = $estado;
$ordenes_rows = [];
$artesanos_options = [];
$productos_options = [];
$prioridades_options = [];
$estado_options = [];

// Cargar estados de orden (usado en filtros)
try {
    $estado_options = $catRepo->obtenerOpciones('estados_orden');
} catch (Exception $e) {
    error_log('estados orden error: ' . $e->getMessage());
}

// Cargar datos para modo legacy
if ($legacy) {
    try {
        $ordenes_limit = $estado_filtrado !== '' ? 200 : 20;
        $ordenes_rows = $ordenRepo->listar(0, $ordenes_limit, $estado_filtrado !== '' ? $estado_filtrado : null);

        if ($estado_filtrado !== '') {
            $ordenes_rows = array_values(array_filter($ordenes_rows, function ($row) use ($estado_filtrado) {
                return strcasecmp((string) ($row['estado'] ?? ''), $estado_filtrado) === 0;
            }));
        }
        if ($is_operador) {
            $ordenes_rows = array_values(array_filter($ordenes_rows, function ($row) {
                return strtolower((string) ($row['estado'] ?? '')) !== 'terminada';
            }));
        }
    } catch (PDOException $e) {
        error_log('produccion legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    // Cargar artesanos para dropdown de asignación
    try {
        $artesanos_options = $catRepo->obtenerOpciones('artesanos');
    } catch (Exception $e) {
        error_log('artesanos legacy error: ' . $e->getMessage());
    }

    if ($estado !== '') {
        if (ctype_digit($estado)) {
            foreach ($estado_options as $opt) {
                if ((int) ($opt['value'] ?? 0) === (int) $estado) {
                    $estado_filtrado = (string) ($opt['label'] ?? '');
                    break;
                }
            }
        } else {
            $estado_filtrado = $estado;
        }
    }

    // Cargar productos para creación de órdenes
    try {
        $productos_options = $catRepo->obtenerOpciones('productos');
    } catch (Exception $e) {
        error_log('productos legacy error: ' . $e->getMessage());
    }

    // Cargar prioridades para creación de órdenes
    try {
        $prioridades_options = $catRepo->obtenerOpciones('prioridades');
    } catch (Exception $e) {
        error_log('prioridades legacy error: ' . $e->getMessage());
    }
}

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(3);
?>
<div class="content">
    <div class="content-header">
        <h1>Produccion</h1>
        <p>Seguimiento de ordenes y estado de taller</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Ordenes de produccion</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-orden">+ Crear Orden</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="orden-filtros-modern">
                <div>
                    <label class="form-label muted" for="orden-estado-modern">Estado</label>
                    <select id="orden-estado-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($estado_options as $est): ?>
                            <?php
                            $nombre = (string) ($est['nombre'] ?? '');
                            $label = ucwords(str_replace('_', ' ', $nombre));
                            ?>
                            <option value="<?php echo page_e($nombre); ?>">
                                <?php echo page_e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="orden-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="orden-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="produccion.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="orden-estado">Estado</label>
                    <select id="orden-estado" name="estado" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php if (!empty($estado_options)): ?>
                            <?php foreach ($estado_options as $est): ?>
                                <?php
                                $nombre = (string) ($est['label'] ?? '');
                                $label = ucwords(str_replace('_', ' ', $nombre));
                                ?>
                                <option value="<?php echo page_e($nombre); ?>" <?php echo $estado_filtrado === $nombre ? 'selected' : ''; ?>>
                                    <?php echo page_e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="pendiente" <?php echo $estado_filtrado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_proceso" <?php echo $estado_filtrado === 'en_proceso' ? 'selected' : ''; ?>>En proceso</option>
                            <option value="terminada" <?php echo $estado_filtrado === 'terminada' ? 'selected' : ''; ?>>Terminada</option>
                            <option value="cancelada" <?php echo $estado_filtrado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="pausada" <?php echo $estado_filtrado === 'pausada' ? 'selected' : ''; ?>>Pausada</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="produccion.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="ordenes-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Producto</th>
                        <th>Artesano</th>
                        <th>Estado</th>
                        <th>Fecha inicio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($ordenes_rows as $row): ?>
                            <?php
                            $estado_raw = (string) ($row['estado'] ?? '');
                            $fecha_inicio = page_format_datetime($row['fecha_inicio'] ?? null);
                            $fecha_creacion = page_format_datetime($row['fecha_creacion'] ?? null);
                            $fecha_fin_estimada = page_format_datetime($row['fecha_fin_estimada'] ?? null);
                            $fecha_fin_real = page_format_datetime($row['fecha_fin_real'] ?? null);
                            $prioridad = (string) ($row['prioridad'] ?? '');
                            $observaciones = (string) ($row['observaciones'] ?? '');
                            $observaciones_terminada = (string) ($row['observaciones_terminada'] ?? '');
                            $observaciones_detalle = $observaciones_terminada !== '' ? $observaciones_terminada : $observaciones;
                            ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>"
                                data-artesano-id="<?php echo (int) ($row['artesano_id'] ?? 0); ?>"
                                data-producto="<?php echo page_e((string) ($row['producto_nombre'] ?? '')); ?>"
                                data-cantidad="<?php echo page_e((string) ($row['cantidad'] ?? '')); ?>"
                                data-estado="<?php echo page_e($estado_raw); ?>"
                                data-prioridad="<?php echo page_e($prioridad); ?>"
                                data-fecha-creacion="<?php echo page_e($fecha_creacion); ?>"
                                data-fecha-inicio="<?php echo page_e($fecha_inicio); ?>"
                                data-fecha-fin-estimada="<?php echo page_e($fecha_fin_estimada); ?>"
                                data-fecha-fin-real="<?php echo page_e($fecha_fin_real); ?>"
                                data-observaciones="<?php echo page_e($observaciones_detalle); ?>">
                                <td><?php echo page_e((string) ($row['id'] ?? '')); ?></td>
                                <td><?php echo page_e((string) $row['producto_nombre']); ?></td>
                                <td><?php echo page_e((string) ($row['artesano_nombre'] ?? '')); ?></td>
                                <td><?php echo page_status_badge($estado_raw); ?></td>
                                <td><?php echo page_e($fecha_inicio); ?></td>
                                <td class="ds-actions-col">
                                    <button type="button" class="ds-action-btn" data-action="detalles" title="Ver detalles">
                                        <img src="assets/icons/fatcow/16/information.png" alt="Detalles" class="ds-icon-img">
                                    </button>
                                    <button type="button" class="ds-action-btn" data-action="asignar" title="Asignar artesano">
                                        <img src="assets/icons/fatcow/16/user_add.png" alt="Asignar" class="ds-icon-img">
                                    </button>
                                </td>
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
    window._artesanosOptions = <?php echo json_encode($artesanos_options); ?>;
    window._productosOptions = <?php echo json_encode($productos_options); ?>;
    window._prioridadesOptions = <?php echo json_encode($prioridades_options); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
