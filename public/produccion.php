<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE PRODUCCIÓN
 * ============================================================================
 * 
 * Página de gestión de órdenes de producción.
 * Permite visualizar y asignar órdenes a artesanos.
 * 
 * Características:
 * - Tabla de órdenes con filtro por estado
 * - Asignación de artesanos a órdenes
 * - Seguimiento de estado de producción
 * - Fechas de inicio y fin estimada
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 3 (Producción)
 * 
 * Parámetros GET (solo legacy):
 * - estado: Filtrar por estado de la orden
 * 
 * APIs utilizadas:
 * - GET /api/ordenes.php - Listar órdenes
 * - POST /api/ordenes.php - Crear orden
 * - PATCH /api/ordenes.php - Asignar artesano a orden
 * - GET /api/opciones.php - Estados, prioridades, artesanos, productos
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';

// Verificar autenticación y autorización
require_login('/login.php');
require_menu_access(3); // Menú: Producción

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();
$current_user = get_session_user();
$is_operador = (int) ($current_user['rolid'] ?? 0) === 2;

// Filtros de búsqueda (solo usados en modo legacy)
$estado = trim((string) ($_GET['estado'] ?? ''));
$estado_filtrado = $estado;
$ordenes_rows = [];
$artesanos_options = [];
$productos_options = [];
$prioridades_options = [];
$estado_options = [];

// Cargar estados de orden (usado en filtros)
try {
    $stmt = $connLogic->prepare('SELECT id, nombre FROM estados_orden WHERE activo = TRUE ORDER BY orden');
    $stmt->execute();
    $estado_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('estados orden error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Cargar datos para modo legacy
if ($legacy) {
    try {
        $ordenes_limit = $estado_filtrado !== '' ? 200 : 20;
        $sql = 'SELECT id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones, observaciones_terminada FROM fun_obtener_ordenes(:offset, :limit, :estado)';
        if ($is_operador) {
            $sql .= " WHERE LOWER(estado) <> 'terminada'";
        }
        $stmt = $connLogic->prepare($sql);
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $ordenes_limit, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado_filtrado !== '' ? $estado_filtrado : null, $estado_filtrado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $ordenes_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, apellido, especialidades FROM fun_obtener_artesanos(:activo)'
        );
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $artesanos_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('artesanos legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    if ($estado !== '') {
        if (ctype_digit($estado)) {
            foreach ($estado_options as $opt) {
                if ((int) ($opt['id'] ?? 0) === (int) $estado) {
                    $estado_filtrado = (string) ($opt['nombre'] ?? '');
                    break;
                }
            }
        } else {
            $estado_filtrado = $estado;
        }
    }

    // Cargar productos para creación de órdenes
    try {
        $stmt = $connLogic->prepare('SELECT id, nombre, tipo FROM productos WHERE activo = TRUE ORDER BY nombre');
        $stmt->execute();
        $productos_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('productos legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    // Cargar prioridades para creación de órdenes
    try {
        $stmt = $connLogic->prepare('SELECT id, nombre FROM prioridades WHERE activo = TRUE ORDER BY orden DESC');
        $stmt->execute();
        $prioridades_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('prioridades legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
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
                            <option value="<?php echo htmlspecialchars($nombre); ?>">
                                <?php echo htmlspecialchars($label); ?>
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
                                $nombre = (string) ($est['nombre'] ?? '');
                                $label = ucwords(str_replace('_', ' ', $nombre));
                                ?>
                                <option value="<?php echo htmlspecialchars($nombre); ?>" <?php echo $estado_filtrado === $nombre ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
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
                            $estado_label = strtoupper(str_replace('_', ' ', $estado_raw));
                            $fecha_inicio = $row['fecha_inicio'] ? date('Y-m-d H:i', strtotime((string) $row['fecha_inicio'])) : '';
                            $fecha_creacion = $row['fecha_creacion'] ? date('Y-m-d H:i', strtotime((string) $row['fecha_creacion'])) : '';
                            $fecha_fin_estimada = $row['fecha_fin_estimada'] ? date('Y-m-d H:i', strtotime((string) $row['fecha_fin_estimada'])) : '';
                            $fecha_fin_real = $row['fecha_fin_real'] ? date('Y-m-d H:i', strtotime((string) $row['fecha_fin_real'])) : '';
                            $prioridad = (string) ($row['prioridad'] ?? '');
                            $observaciones = (string) ($row['observaciones'] ?? '');
                            $observaciones_terminada = (string) ($row['observaciones_terminada'] ?? '');
                            $observaciones_detalle = $observaciones_terminada !== '' ? $observaciones_terminada : $observaciones;
                            ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>"
                                data-artesano-id="<?php echo (int) ($row['artesano_id'] ?? 0); ?>"
                                data-producto="<?php echo htmlspecialchars((string) ($row['producto_nombre'] ?? '')); ?>"
                                data-cantidad="<?php echo htmlspecialchars((string) ($row['cantidad'] ?? '')); ?>"
                                data-estado="<?php echo htmlspecialchars($estado_label); ?>"
                                data-prioridad="<?php echo htmlspecialchars($prioridad); ?>"
                                data-fecha-creacion="<?php echo htmlspecialchars($fecha_creacion); ?>"
                                data-fecha-inicio="<?php echo htmlspecialchars($fecha_inicio); ?>"
                                data-fecha-fin-estimada="<?php echo htmlspecialchars($fecha_fin_estimada); ?>"
                                data-fecha-fin-real="<?php echo htmlspecialchars($fecha_fin_real); ?>"
                                data-observaciones="<?php echo htmlspecialchars($observaciones_detalle); ?>">
                                <td><?php echo htmlspecialchars((string) ($row['id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['producto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['artesano_nombre'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($estado_label); ?></td>
                                <td><?php echo htmlspecialchars($fecha_inicio); ?></td>
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

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        function formatStatus(v) {
            var raw = (v || '').toString();
            var label = raw.replace(/_/g, ' ').toUpperCase();
            var key = raw.toLowerCase();
            var cls = 'ds-badge--neutral';
            if (key === 'pendiente') cls = 'ds-badge--warning';
            else if (key === 'en_proceso') cls = 'ds-badge--info';
            else if (key === 'terminada') cls = 'ds-badge--success';
            else if (key === 'cancelada') cls = 'ds-badge--danger';
            else if (key === 'pausada') cls = 'ds-badge--muted';
            return '<span class="ds-badge ' + cls + '">' + label + '</span>';
        }
        function formatFecha(v) {
            if (!v) return '';
            return v.replace('T', ' ').split('.')[0];
        }
        $(function () {
            var ordenesTable;
            var artesanosCache = [];
            var productosCache = [];
            var prioridadesCache = [];
            var estadoFilter = '';

            function applyOrdenFilters() {
                var estadoEl = $('#orden-estado-modern');
                estadoFilter = estadoEl.length ? estadoEl.val() : '';
                if (ordenesTable) {
                    ordenesTable.ajax.reload();
                }
            }

            axios.get('api/opciones.php?tipo=artesanos').then(function (res) {
                artesanosCache = (res.data.DATOS || []).map(function (a) {
                    var label = a.label || '';
                    if (!label) {
                        var fullName = ((a.nombre || '') + ' ' + (a.apellido || '')).replace(/\s+/g, ' ').trim();
                        label = fullName || (a.nombre || '');
                        if (a.especialidades) {
                            label += ' - ' + a.especialidades;
                        }
                    }
                    return {
                        value: a.value || a.id,
                        label: label
                    };
                });
            }).catch(function () {
                artesanosCache = [];
            });

            axios.get('api/opciones.php?tipo=productos').then(function (res) {
                productosCache = (res.data.DATOS || []).map(function (p) {
                    return {
                        value: p.value || p.id,
                        label: p.label || p.nombre || ''
                    };
                });
            }).catch(function () {
                productosCache = [];
            });

            axios.get('api/opciones.php?tipo=prioridades').then(function (res) {
                prioridadesCache = (res.data.DATOS || []).map(function (p) {
                    return {
                        value: p.value || p.id,
                        label: p.label || p.nombre || ''
                    };
                });
            }).catch(function () {
                prioridadesCache = [];
            });

            ordenesTable = $('#ordenes-table').DataTable({
                ajax: {
                    url: 'api/ordenes.php',
                    data: function (d) {
                        d.limit = 100;
                        d.offset = 0;
                        if (estadoFilter) d.estado = estadoFilter;
                    },
                    dataSrc: 'DATOS'
                },
                columns: [
                    { data: 'id' },
                    { data: 'producto_nombre' },
                    { data: 'artesano_nombre', defaultContent: '' },
                    { data: 'estado', render: formatStatus },
                    { data: 'fecha_inicio', render: formatFecha },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type) {
                            if (type !== 'display') return '';
                            return '<button type="button" class="ds-action-btn" data-action="detalles" title="Ver detalles">ℹ️</button>' +
                                '<button type="button" class="ds-action-btn" data-action="asignar" title="Asignar artesano">👤➕</button>';
                        }
                    }
                ],
                language: { url: 'assets/dataTables.es-ES.json' }
            });

            function openDetalles(row) {
                if (!row) return;
                var producto = DsCrud.escapeHtml(row.producto_nombre || '');
                var artesano = DsCrud.escapeHtml(row.artesano_nombre || '');
                var estado = DsCrud.escapeHtml((row.estado || '').toString().replace(/_/g, ' ').toUpperCase());
                var prioridad = DsCrud.escapeHtml(row.prioridad || '');
                var observaciones = DsCrud.escapeHtml(row.observaciones_terminada || row.observaciones || '');
                var body = '<div class="ds-detail-list">' +
                    '<p><strong>Producto:</strong> ' + producto + '</p>' +
                    '<p><strong>Cantidad:</strong> ' + DsCrud.escapeHtml(row.cantidad || '') + '</p>' +
                    '<p><strong>Artesano:</strong> ' + artesano + '</p>' +
                    '<p><strong>Estado:</strong> ' + estado + '</p>' +
                    '<p><strong>Prioridad:</strong> ' + prioridad + '</p>' +
                    '<p><strong>Fecha creación:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_creacion)) + '</p>' +
                    '<p><strong>Fecha inicio:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_inicio)) + '</p>' +
                    '<p><strong>Fecha fin estimada:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_fin_estimada)) + '</p>' +
                    '<p><strong>Fecha fin real:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_fin_real)) + '</p>' +
                    '<p><strong>Observaciones:</strong> ' + (observaciones || '-') + '</p>' +
                    '</div>';
                DsCrud.openModal({
                    title: 'Detalles de orden #' + row.id,
                    body: body,
                    saveText: 'Cerrar',
                    cancelText: 'Cerrar',
                    onSave: function () {
                        DsCrud.closeModal();
                    }
                });
            }

            function getDefaultPrioridad() {
                var valor = '';
                for (var i = 0; i < prioridadesCache.length; i++) {
                    if (String(prioridadesCache[i].value) === '2') {
                        valor = prioridadesCache[i].value;
                        break;
                    }
                }
                if (!valor && prioridadesCache.length) {
                    valor = prioridadesCache[0].value;
                }
                return valor;
            }

            function openCrear() {
                if (!productosCache.length) {
                    DsCrud.toast('No hay productos disponibles', 'error');
                    return;
                }
                var productoOpts = [{ value: '', label: '-- Seleccione --' }].concat(productosCache);
                var prioridadOpts = [{ value: '', label: '-- Seleccione --' }].concat(prioridadesCache);
                var artesanoOpts = [{ value: '', label: '-- Sin asignar --' }].concat(artesanosCache);
                var prioridadDefault = getDefaultPrioridad();

                var body = '<form id="frm-crear-orden">' +
                    DsCrud.field({
                        name: 'producto_id',
                        label: 'Producto',
                        type: 'select',
                        options: productoOpts,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'cantidad',
                        label: 'Cantidad',
                        type: 'number',
                        value: 1,
                        required: true,
                        attrs: 'min="1" step="1"'
                    }) +
                    DsCrud.field({
                        name: 'prioridad_id',
                        label: 'Prioridad',
                        type: 'select',
                        value: prioridadDefault,
                        options: prioridadOpts
                    }) +
                    DsCrud.field({
                        name: 'artesano_id',
                        label: 'Asignar artesano',
                        type: 'select',
                        options: artesanoOpts
                    }) +
                    DsCrud.field({
                        name: 'observaciones',
                        label: 'Observaciones',
                        type: 'textarea',
                        placeholder: 'Opcional'
                    }) +
                    '</form>';

                DsCrud.openModal({
                    title: 'Crear orden',
                    body: body,
                    saveText: 'Crear',
                    cancelText: 'Cancelar',
                    onSave: function (m) {
                        var f = m.querySelector('#frm-crear-orden');
                        if (!f.checkValidity()) {
                            f.reportValidity();
                            return;
                        }
                        var fd = new FormData(f);
                        var productoId = parseInt(fd.get('producto_id'), 10) || 0;
                        var cantidad = parseInt(fd.get('cantidad'), 10) || 0;
                        var prioridadId = parseInt(fd.get('prioridad_id'), 10) || 0;
                        var artesanoId = parseInt(fd.get('artesano_id'), 10) || 0;
                        var observaciones = (fd.get('observaciones') || '').toString().trim();

                        if (productoId <= 0 || cantidad <= 0) {
                            DsCrud.toast('Producto y cantidad son requeridos', 'error');
                            return;
                        }

                        var payload = {
                            producto_id: productoId,
                            cantidad: cantidad,
                            prioridad_id: prioridadId > 0 ? prioridadId : null,
                            artesano_id: artesanoId > 0 ? artesanoId : null,
                            observaciones: observaciones ? observaciones : null
                        };

                        DsCrud.api('api/ordenes.php', 'POST', payload, function () {
                            DsCrud.toast('Orden creada', 'success');
                            ordenesTable.ajax.reload();
                            DsCrud.closeModal();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            function openAsignar(row) {
                if (!artesanosCache.length) {
                    DsCrud.toast('No hay artesanos disponibles', 'error');
                    return;
                }
                var opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosCache);
                var body = '<form id="frm-asignar">' +
                    DsCrud.field({
                        name: 'artesano_id',
                        label: 'Artesano',
                        type: 'select',
                        value: row.artesano_id || '',
                        options: opts,
                        required: true
                    }) +
                    '</form>';
                DsCrud.openModal({
                    title: 'Asignar artesano - Orden #' + row.id,
                    body: body,
                    onSave: function (m) {
                        var f = m.querySelector('#frm-asignar');
                        if (!f.checkValidity()) {
                            f.reportValidity();
                            return;
                        }
                        var fd = new FormData(f);
                        var payload = {
                            id: row.id,
                            artesano_id: fd.get('artesano_id')
                        };
                        DsCrud.api('api/ordenes.php', 'PATCH', payload, function () {
                            DsCrud.toast('Orden actualizada', 'success');
                            ordenesTable.ajax.reload();
                            DsCrud.closeModal();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            $('#btn-add-orden').on('click', function () {
                openCrear();
            });
            $('#orden-filtrar-modern').on('click', applyOrdenFilters);
            $('#orden-limpiar-modern').on('click', function () {
                var estadoEl = $('#orden-estado-modern');
                if (estadoEl.length) estadoEl.val('');
                applyOrdenFilters();
            });
            $('#orden-estado-modern').on('change', applyOrdenFilters);

            $('#ordenes-table').on('click', '.ds-action-btn[data-action="asignar"]', function () {
                var row = ordenesTable.row($(this).closest('tr')).data();
                if (row) {
                    openAsignar(row);
                }
            });

            $('#ordenes-table').on('click', '.ds-action-btn[data-action="detalles"]', function () {
                var row = ordenesTable.row($(this).closest('tr')).data();
                if (row) {
                    openDetalles(row);
                }
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('ordenes-table');

            var artesanosOptions = <?php echo json_encode(array_map(function ($a) {
                $label = trim($a['nombre'] . ' ' . $a['apellido']);
                $especialidades = $a['especialidades'] ?? '';
                if ($especialidades !== null && $especialidades !== '') {
                    $label .= ' - ' . $especialidades;
                }
                return [
                    'value' => $a['id'],
                    'label' => $label
                ];
            }, $artesanos_options)); ?>;

            var productosOptions = <?php echo json_encode(array_map(function ($p) {
                $label = (string) ($p['nombre'] ?? '');
                $tipo = (string) ($p['tipo'] ?? '');
                if ($tipo !== '') {
                    $label .= ' (' . $tipo . ')';
                }
                return [
                    'value' => $p['id'],
                    'label' => $label
                ];
            }, $productos_options)); ?>;

            var prioridadesOptions = <?php echo json_encode(array_map(function ($p) {
                return [
                    'value' => $p['id'],
                    'label' => $p['nombre']
                ];
            }, $prioridades_options)); ?>;

            function esc(s) {
                if (s === null || s === undefined) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(String(s)));
                return div.innerHTML;
            }

            function selectHtml(name, value, options, req) {
                var h = '<select name="' + esc(name) + '" class="ds-field ds-field--select"' + (req ? ' required' : '') + '>';
                for (var i = 0; i < options.length; i++) {
                    var opt = options[i];
                    var sel = (String(opt.value) === String(value)) ? ' selected' : '';
                    h += '<option value="' + esc(opt.value) + '"' + sel + '>' + esc(opt.label) + '</option>';
                }
                h += '</select>';
                return h;
            }

            function getDefaultPrioridadValue() {
                for (var i = 0; i < prioridadesOptions.length; i++) {
                    if (String(prioridadesOptions[i].value) === '2') {
                        return prioridadesOptions[i].value;
                    }
                }
                if (prioridadesOptions.length) {
                    return prioridadesOptions[0].value;
                }
                return '';
            }

            function buildCrearFormHtml(defaultPrioridad) {
                var prodOpts = [{ value: '', label: '-- Seleccione --' }].concat(productosOptions);
                var artOpts = [{ value: '', label: '-- Sin asignar --' }].concat(artesanosOptions);
                var prioOpts = [{ value: '', label: '-- Seleccione --' }].concat(prioridadesOptions);
                var html = '<div class="ds-form-group"><label>Producto</label>' +
                    selectHtml('producto_id', '', prodOpts, true) + '</div>' +
                    '<div class="ds-form-group"><label>Cantidad</label><input type="text" name="cantidad" value="1" class="ds-field" required></div>' +
                    '<div class="ds-form-group"><label>Prioridad</label>' +
                    selectHtml('prioridad_id', defaultPrioridad || '', prioOpts, false) + '</div>' +
                    '<div class="ds-form-group"><label>Asignar artesano</label>' +
                    selectHtml('artesano_id', '', artOpts, false) + '</div>' +
                    '<div class="ds-form-group"><label>Observaciones</label>' +
                    '<textarea name="observaciones" class="ds-field" rows="3"></textarea></div>';
                return html;
            }

            function openCrear() {
                if (!productosOptions.length) {
                    DsCrud.toast('No hay productos disponibles', 'error');
                    return;
                }
                var prioridadDefault = getDefaultPrioridadValue();
                DsCrud.openModal({
                    title: 'Crear orden',
                    body: '<form id="frm-crear-orden">' + buildCrearFormHtml(prioridadDefault) + '</form>',
                    saveText: 'Crear',
                    cancelText: 'Cancelar',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        var productoId = parseInt(data.producto_id || 0, 10) || 0;
                        var cantidad = parseInt(data.cantidad || 0, 10) || 0;
                        var prioridadId = parseInt(data.prioridad_id || 0, 10) || 0;
                        var artesanoId = parseInt(data.artesano_id || 0, 10) || 0;
                        var observaciones = data.observaciones ? String(data.observaciones).replace(/^\s+|\s+$/g, '') : '';

                        if (productoId <= 0 || cantidad <= 0) {
                            DsCrud.toast('Producto y cantidad son requeridos', 'error');
                            return;
                        }

                        var payload = {
                            producto_id: productoId,
                            cantidad: cantidad,
                            prioridad_id: prioridadId > 0 ? prioridadId : null,
                            artesano_id: artesanoId > 0 ? artesanoId : null,
                            observaciones: observaciones ? observaciones : null
                        };

                        DsCrud.apiLegacy('api/ordenes.php', 'POST', payload, function () {
                            DsCrud.toast('Orden creada', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            function openAsignar(ordenId, artesanoId) {
                if (!artesanosOptions.length) {
                    alert('No hay artesanos disponibles');
                    return;
                }
                var opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosOptions);
                var body = '<form id="frm-asignar">' +
                    '<div class="ds-form-group"><label>Artesano</label>' +
                    selectHtml('artesano_id', artesanoId || '', opts, true) + '</div>' +
                    '</form>';
                DsCrud.openModal({
                    title: 'Asignar artesano - Orden #' + ordenId,
                    body: body,
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = ordenId;
                        DsCrud.api('api/ordenes.php', 'PATCH', data, function () {
                            DsCrud.toast('Orden actualizada', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            function openDetalles(row) {
                if (!row) return;
                var producto = row.getAttribute('data-producto') || '';
                var cantidad = row.getAttribute('data-cantidad') || '';
                var artesano = '';
                var cells = row.getElementsByTagName('td');
                if (cells.length > 2) {
                    artesano = cells[2].innerText || cells[2].textContent || '';
                }
                var estado = row.getAttribute('data-estado') || '';
                var prioridad = row.getAttribute('data-prioridad') || '';
                var fechaCreacion = row.getAttribute('data-fecha-creacion') || '';
                var fechaInicio = row.getAttribute('data-fecha-inicio') || '';
                var fechaFinEst = row.getAttribute('data-fecha-fin-estimada') || '';
                var fechaFinReal = row.getAttribute('data-fecha-fin-real') || '';
                var observaciones = row.getAttribute('data-observaciones') || '';
                if (!observaciones) observaciones = '-';
                var body = '<div class="ds-detail-list">' +
                    '<p><strong>Producto:</strong> ' + esc(producto) + '</p>' +
                    '<p><strong>Cantidad:</strong> ' + esc(cantidad) + '</p>' +
                    '<p><strong>Artesano:</strong> ' + esc(artesano) + '</p>' +
                    '<p><strong>Estado:</strong> ' + esc(estado) + '</p>' +
                    '<p><strong>Prioridad:</strong> ' + esc(prioridad) + '</p>' +
                    '<p><strong>Fecha creacion:</strong> ' + esc(fechaCreacion) + '</p>' +
                    '<p><strong>Fecha inicio:</strong> ' + esc(fechaInicio) + '</p>' +
                    '<p><strong>Fecha fin estimada:</strong> ' + esc(fechaFinEst) + '</p>' +
                    '<p><strong>Fecha fin real:</strong> ' + esc(fechaFinReal) + '</p>' +
                    '<p><strong>Observaciones:</strong> ' + esc(observaciones) + '</p>' +
                    '</div>';
                DsCrud.openModal({
                    title: 'Detalles de orden #' + row.getAttribute('data-id'),
                    body: body,
                    saveText: 'Cerrar',
                    cancelText: 'Cerrar',
                    onSave: function () {
                        DsCrud.closeModal();
                    }
                });
            }

            function findActionButton(target) {
                while (target && target !== document) {
                    if (target.getAttribute && target.getAttribute('data-action')) {
                        return target;
                    }
                    target = target.parentNode;
                }
                return null;
            }

            var table = document.getElementById('ordenes-table');
            if (table) {
                var btnCrear = document.getElementById('btn-add-orden');
                if (btnCrear) {
                    DsCrud.addEvent(btnCrear, 'click', function () {
                        openCrear();
                    });
                }
                DsCrud.addEvent(table, 'click', function (e) {
                    e = e || window.event;
                    var target = e.target || e.srcElement;
                    var btn = findActionButton(target);
                    if (!btn) return;

                    var row = btn;
                    while (row && row.tagName && row.tagName.toLowerCase() !== 'tr') {
                        row = row.parentNode;
                    }
                    if (!row) return;

                    var action = btn.getAttribute('data-action');
                    if (action === 'detalles') {
                        openDetalles(row);
                        return;
                    }
                    var ordenId = row.getAttribute('data-id');
                    var artesanoId = row.getAttribute('data-artesano-id');
                    openAsignar(ordenId, artesanoId);
                });
            }
        })();
    </script>
<?php endif; ?>
</body>

</html>
