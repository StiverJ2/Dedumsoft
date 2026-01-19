<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');
require_menu_access(2);

$legacy = dedumsoft_is_legacy_browser();
$maq_estado_id = isset($_GET['maq_estado_id']) && $_GET['maq_estado_id'] !== '' ? (int) $_GET['maq_estado_id'] : null;
$maq_rows = [];
$proveedor_options = [];
$ubicacion_options = [];
$tipo_maquinaria_options = [];
$maq_estado_options = [];

function format_maq_tipo_badge($tipo)
{
    $tipo = trim((string) $tipo);
    if ($tipo === '')
        return '';
    // Normalizar: quitar acentos para comparación
    $acentos = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'];
    $sinAcentos = ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'];
    $key = strtolower(str_replace($acentos, $sinAcentos, $tipo));
    $label = ucwords(str_replace('_', ' ', $tipo));
    $cls = 'ds-badge--neutral';
    if (strpos($key, 'corte') !== false || strpos($key, 'sierra') !== false)
        $cls = 'ds-badge--danger';
    elseif (strpos($key, 'pulido') !== false || strpos($key, 'acabado') !== false)
        $cls = 'ds-badge--info';
    elseif (strpos($key, 'fundicion') !== false || strpos($key, 'horno') !== false)
        $cls = 'ds-badge--warning';
    elseif (strpos($key, 'soldadura') !== false)
        $cls = 'ds-badge--success';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function format_maq_estado_badge($estado_nombre, $estado_color = null)
{
    $estado = trim((string) $estado_nombre);
    if ($estado === '')
        return '';
    $key = strtolower($estado);
    $label = ucwords(str_replace('_', ' ', $estado));

    // Si tenemos color de la BD, usarlo
    if ($estado_color) {
        return '<span class="ds-badge" style="background-color:' . htmlspecialchars($estado_color) . '">' . htmlspecialchars($label) . '</span>';
    }

    // Fallback a clases CSS
    $cls = 'ds-badge--neutral';
    if ($key === 'operativa')
        $cls = 'ds-badge--success';
    elseif ($key === 'mantenimiento')
        $cls = 'ds-badge--warning';
    elseif ($key === 'averiada')
        $cls = 'ds-badge--danger';
    elseif ($key === 'fuera de servicio' || strpos($key, 'fuera') !== false)
        $cls = 'ds-badge--muted';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

try {
    $stmt = $connLogic->query("SELECT id, nombre, tipo FROM proveedores WHERE activo = TRUE ORDER BY nombre");
    $proveedor_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

try {
    $stmt = $connLogic->query("SELECT id, nombre FROM ubicaciones WHERE activo = TRUE ORDER BY nombre");
    $ubicacion_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario ubicaciones error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

try {
    $stmt = $connLogic->query("SELECT id, nombre FROM tipos_maquinaria WHERE activo = TRUE ORDER BY nombre");
    $tipo_maquinaria_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario tipos_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Obtener opciones de estados de maquinaria desde la BD
try {
    $stmt = $connLogic->query("SELECT id, nombre, color FROM estados_maquinaria WHERE activo = TRUE ORDER BY orden");
    $maq_estado_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario estados_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, sku, nombre, tipo_maquinaria_id, tipo_nombre, estado_id, estado_nombre, estado_color, ubicacion_id, ubicacion_nombre FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado_id, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $maq_estado_id, $maq_estado_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $maq_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
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
        <?php if ($legacy): ?>
            <form method="get" action="inventario_maquinaria.php#inv-maquinaria"
                class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="maq-estado-id">Estado</label>
                    <select id="maq-estado-id" name="maq_estado_id" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($maq_estado_options as $est): ?>
                            <option value="<?php echo (int) $est['id']; ?>" <?php echo $maq_estado_id === (int) $est['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($est['nombre']); ?>
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
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['sku'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo format_maq_tipo_badge($row['tipo_nombre'] ?? ''); ?></td>
                                <td><?php echo format_maq_estado_badge($row['estado_nombre'] ?? '', $row['estado_color'] ?? null); ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($row['ubicacion_nombre'] ?? '')); ?></td>
                                <td class="ds-actions-col"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!$legacy): ?>
    <script>
        window.DEDUMSOFT_ICON_MODE = 'emoji';
    </script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        function formatMaqTipo(tipo) {
            if (!tipo) return '';
            var key = tipo.toLowerCase();
            var label = tipo.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
            var cls = 'ds-badge--neutral';
            if (key.indexOf('corte') > -1 || key.indexOf('sierra') > -1) cls = 'ds-badge--danger';
            else if (key.indexOf('pulido') > -1 || key.indexOf('acabado') > -1) cls = 'ds-badge--info';
            else if (key.indexOf('fundicion') > -1 || key.indexOf('horno') > -1) cls = 'ds-badge--warning';
            else if (key.indexOf('soldadura') > -1) cls = 'ds-badge--success';
            return '<span class="ds-badge ' + cls + '">' + label + '</span>';
        }

        function formatMaqEstado(estadoNombre, estadoColor) {
            if (!estadoNombre) return '';
            var label = estadoNombre.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
            if (estadoColor) {
                return '<span class="ds-badge" style="background-color:' + estadoColor + '">' + label + '</span>';
            }
            var key = estadoNombre.toLowerCase();
            var cls = 'ds-badge--neutral';
            if (key === 'operativa') cls = 'ds-badge--success';
            else if (key === 'mantenimiento') cls = 'ds-badge--warning';
            else if (key === 'averiada') cls = 'ds-badge--danger';
            else if (key === 'fuera de servicio' || key.indexOf('fuera') > -1) cls = 'ds-badge--muted';
            return '<span class="ds-badge ' + cls + '">' + label + '</span>';
        }

        $(function () {
            const dtLang = {
                url: 'assets/dataTables.es-ES.json'
            };
            let maqTable;
            let proveedoresCache = [];
            let ubicacionesCache = [];
            let tipoMaquinariaCache = [];
            let maqEstadoOptions = [];

            // Cargar todas las opciones en paralelo
            Promise.all([
                axios.get('../api/opciones.php?tipo=estados_maquinaria'),
                axios.get('../api/proveedores.php?limit=500'),
                axios.get('../api/ubicaciones.php?limit=500'),
                axios.get('../api/tipos_maquinaria.php')
            ]).then(([resEstados, resProv, resUbi, resTipos]) => {
                maqEstadoOptions = (resEstados.data.DATOS || []).map(e => ({
                    value: e.value,
                    label: e.label,
                    color: e.color
                }));

                proveedoresCache = (resProv.data.DATOS || []).map(p => ({
                    value: p.id,
                    label: `${p.nombre} (${p.tipo_nombre || p.tipo || ''})`
                }));

                ubicacionesCache = (resUbi.data.DATOS || []).map(u => ({
                    value: u.id,
                    label: u.nombre
                }));

                tipoMaquinariaCache = (resTipos.data.DATOS || []).map(t => ({
                    value: t.id,
                    label: t.nombre
                }));

                initMaqTable();
            }).catch(error => {
                console.error('Error cargando datos:', error);
                initMaqTable();
            });

            const buildMaqForm = (data) => {
                data = data || {};
                const provOpts = [{
                    value: '',
                    label: '-- Sin proveedor --'
                }].concat(
                    proveedoresCache.filter(p => p.label.indexOf('(maquinaria)') > -1 || !data.id)
                );
                const ubOpts = [{
                    value: '',
                    label: '-- Sin ubicacion --'
                }].concat(ubicacionesCache);
                const tipoOpts = [{
                    value: '',
                    label: '-- Seleccione tipo --'
                }].concat(tipoMaquinariaCache);
                return DsCrud.field({
                    name: 'nombre',
                    label: 'Nombre',
                    value: data.nombre,
                    required: true
                }) +
                    DsCrud.field({
                        name: 'sku',
                        label: 'SKU / Serial',
                        value: data.sku,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'tipo_maquinaria_id',
                        label: 'Tipo',
                        type: 'select',
                        value: data.tipo_maquinaria_id,
                        options: tipoOpts,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'estado_id',
                        label: 'Estado',
                        type: 'select',
                        value: data.estado_id,
                        options: maqEstadoOptions,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'ubicacion_id',
                        label: 'Ubicacion',
                        type: 'select',
                        value: data.ubicacion_id,
                        options: ubOpts
                    }) +
                    DsCrud.field({
                        name: 'proveedor_id',
                        label: 'Proveedor',
                        type: 'select',
                        value: data.proveedor_id,
                        options: provOpts
                    });
            };

            const openMaqCreate = () => {
                var compraToggle = '<div class="ds-form-group"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
                DsCrud.openModal({
                    title: 'Nueva Maquinaria',
                    body: '<form id="frm-maq">' + buildMaqForm() + compraToggle + '</form>',
                    onSave: function (m) {
                        var f = m.querySelector('#frm-maq');
                        if (!f.checkValidity()) {
                            f.reportValidity();
                            return;
                        }
                        var fd = new FormData(f),
                            payload = {};
                        fd.forEach(function (v, k) {
                            payload[k] = v;
                        });
                        var registrarCompra = payload.registrar_compra === 'on';
                        delete payload.registrar_compra;

                        DsCrud.api('../api/inventario_maquinaria.php', 'POST', payload, function (success, resp) {
                            if (!registrarCompra) {
                                DsCrud.toast('Maquinaria creada', 'success');
                                maqTable.ajax.reload();
                                DsCrud.closeModal();
                                return;
                            }
                            var compraPayload = {
                                tipo_inventario: 'maquinaria',
                                item_id: resp.ID,
                                cantidad: 1
                            };
                            DsCrud.api('../api/compras.php', 'POST', compraPayload, function () {
                                DsCrud.toast('Maquinaria creada y compra registrada', 'success');
                                maqTable.ajax.reload();
                                DsCrud.closeModal();
                            }, function (e) {
                                DsCrud.toast('Maquinaria creada, pero no se pudo registrar la compra: ' + e, 'error');
                                maqTable.ajax.reload();
                                DsCrud.closeModal();
                            });
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            };

            const openMaqEdit = (row) => {
                DsCrud.api('../api/inventario_maquinaria.php?id=' + row.id, 'GET', null, function (res) {
                    var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
                    DsCrud.openModal({
                        title: 'Editar Maquinaria #' + d.id,
                        body: '<form id="frm-maq">' + buildMaqForm(d) + '</form>',
                        onSave: function (m) {
                            var f = m.querySelector('#frm-maq');
                            if (!f.checkValidity()) {
                                f.reportValidity();
                                return;
                            }
                            var fd = new FormData(f),
                                payload = {
                                    id: d.id
                                };
                            fd.forEach(function (v, k) {
                                payload[k] = v;
                            });
                            DsCrud.api('../api/inventario_maquinaria.php', 'PUT', payload,
                                function () {
                                    DsCrud.toast('Maquinaria actualizada', 'success');
                                    maqTable.ajax.reload();
                                    DsCrud.closeModal();
                                },
                                function (e) {
                                    DsCrud.toast(e, 'error');
                                });
                        }
                    });
                });
            };

            const openMaqDelete = (row) => {
                DsCrud.confirm('Eliminar maquinaria "' + row.nombre + '"?', function () {
                    DsCrud.api('../api/inventario_maquinaria.php', 'DELETE', {
                        id: row.id
                    }, function () {
                        DsCrud.toast('Maquinaria eliminada', 'success');
                        maqTable.ajax.reload();
                    }, function (e) {
                        DsCrud.toast(e, 'error');
                    });
                });
            };

            const buildCompraMaqForm = (options, data) => {
                data = data || {};
                return DsCrud.field({
                    name: 'item_id',
                    label: 'Maquinaria',
                    type: 'select',
                    value: data.item_id,
                    options: options,
                    required: true
                }) +
                    DsCrud.field({
                        name: 'cantidad',
                        label: 'Cantidad',
                        type: 'number',
                        value: data.cantidad || 1,
                        required: true,
                        attrs: 'step="1" min="1"'
                    }) +
                    DsCrud.field({
                        name: 'motivo',
                        label: 'Motivo',
                        value: data.motivo || 'Compra proveedor'
                    }) +
                    DsCrud.field({
                        name: 'referencia',
                        label: 'Referencia',
                        value: data.referencia
                    }) +
                    DsCrud.field({
                        name: 'fecha',
                        label: 'Fecha',
                        type: 'datetime-local',
                        value: data.fecha
                    });
            };

            const openMaqCompra = () => {
                axios.get('../api/inventario_maquinaria.php?limit=500').then(function (res) {
                    var items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
                    if (!items.length) {
                        DsCrud.toast('No hay maquinaria disponible', 'warning');
                        return;
                    }
                    var options = [{ value: '', label: '-- Seleccione --' }].concat(items.map(function (it) {
                        var label = (it.sku ? it.sku + ' - ' : '') + (it.nombre || 'Maquinaria');
                        label += ' #' + it.id;
                        return { value: it.id, label: label };
                    }));

                    DsCrud.openModal({
                        title: 'Registrar compra de maquinaria',
                        body: '<form id="frm-compra-maq">' + buildCompraMaqForm(options) + '</form>',
                        onSave: function (m) {
                            var f = m.querySelector('#frm-compra-maq');
                            if (!f.checkValidity()) {
                                f.reportValidity();
                                return;
                            }
                            var fd = new FormData(f),
                                payload = { tipo_inventario: 'maquinaria' };
                            fd.forEach(function (v, k) {
                                payload[k] = v;
                            });
                            if (payload.fecha) {
                                payload.fecha = payload.fecha.replace('T', ' ');
                            }
                            DsCrud.api('../api/compras.php', 'POST', payload, function () {
                                DsCrud.toast('Compra registrada', 'success');
                                maqTable.ajax.reload();
                                DsCrud.closeModal();
                            }, function (e) {
                                DsCrud.toast(e, 'error');
                            });
                        }
                    });
                }).catch(function () {
                    DsCrud.toast('Error cargando inventario', 'error');
                });
            };

            const initMaqTable = () => {
                maqTable = $('#maq-table').DataTable({
                    ajax: {
                        url: '../api/inventario_maquinaria.php?limit=500',
                        dataSrc: 'DATOS'
                    },
                    columns: [{
                        data: 'id'
                    },
                    {
                        data: 'sku',
                        defaultContent: ''
                    },
                    {
                        data: 'nombre'
                    },
                    {
                        data: 'tipo_nombre',
                        render: formatMaqTipo
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            return formatMaqEstado(row.estado_nombre, row.estado_color);
                        }
                    },
                    {
                        data: 'ubicacion_nombre',
                        defaultContent: ''
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            if (type !== 'display') return '';
                            return DsCrud.actionButtons(row.id);
                        }
                    }
                    ],
                    language: dtLang
                });

                $('#btn-add-maquinaria').on('click', openMaqCreate);
                $('#btn-compra-maquinaria').on('click', openMaqCompra);
                $('#maq-table').on('click', '.ds-action-btn[data-action="edit"]', function () {
                    openMaqEdit(maqTable.row($(this).closest('tr')).data());
                });
                $('#maq-table').on('click', '.ds-action-btn[data-action="delete"]', function () {
                    openMaqDelete(maqTable.row($(this).closest('tr')).data());
                });
            };
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) {
                DedumTableSort.init('maq-table');
            }

            var proveedorOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin proveedor --']],
                array_map(function ($p) {
                                return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
                            }, $proveedor_options)
            )); ?>;

            var ubicacionOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin ubicacion --']],
                array_map(function ($u) {
                    return ['value' => $u['id'], 'label' => $u['nombre']];
                }, $ubicacion_options)
            )); ?>;

            var tipoMaquinariaOptions = <?php echo json_encode(array_map(function ($t) {
                return ['value' => $t['id'], 'label' => $t['nombre']];
            }, $tipo_maquinaria_options)); ?>;

            var maqEstadoOptions = <?php echo json_encode(array_map(function ($e) {
                return ['value' => $e['id'], 'label' => $e['nombre']];
            }, $maq_estado_options)); ?>;

            var maqInventoryOptions = <?php echo json_encode(array_map(function ($row) {
                $label = ($row['sku'] ?? '') !== '' ? $row['sku'] . ' - ' : '';
                $label .= ($row['nombre'] ?? 'Maquinaria') . ' #' . $row['id'];
                return ['value' => $row['id'], 'label' => $label];
            }, $maq_rows)); ?>;

            function esc(s) {
                if (s === null || s === undefined) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(String(s)));
                return div.innerHTML;
            }

            function selectHtml(name, value, options, req) {
                var h = '<select name="' + name + '" id="field-' + name +
                    '" style="width:100%;padding:6px;font-size:14px;"' + (req ? ' required' : '') + '>';
                for (var i = 0; i < options.length; i++) {
                    var sel = (String(options[i].value) == String(value)) ? ' selected' : '';
                    h += '<option value="' + esc(options[i].value) + '"' + sel + '>' + esc(options[i].label) + '</option>';
                }
                h += '</select>';
                return h;
            }

            function buildMaqFormHtml(d, showCompra) {
                d = d || {};
                var maqProveedores = [];
                for (var i = 0; i < proveedorOptions.length; i++) {
                    if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(maquinaria)') > -1) {
                        maqProveedores.push(proveedorOptions[i]);
                    }
                }
                if (maqProveedores.length === 1) maqProveedores = proveedorOptions;

                var html = '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
                    esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">SKU / Serial <span style="color:red">*</span></label><input type="text" name="sku" value="' +
                    esc(d.sku || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
                    selectHtml('tipo_maquinaria_id', d.tipo_maquinaria_id || '', tipoMaquinariaOptions, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Estado <span style="color:red">*</span></label>' +
                    selectHtml('estado_id', d.estado_id || 1, maqEstadoOptions, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Ubicacion</label>' +
                    selectHtml('ubicacion_id', d.ubicacion_id || '', ubicacionOptions, false) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
                    selectHtml('proveedor_id', d.proveedor_id || '', maqProveedores, false) + '</div>';
                if (showCompra) {
                    html += '<div style="margin-bottom:12px;"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
                }
                return html;
            }

            function buildCompraMaqFormHtml(d) {
                d = d || {};
                var invOpts = [{ value: '', label: '-- Seleccione --' }].concat(maqInventoryOptions);
                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Maquinaria <span style="color:red">*</span></label>' +
                    selectHtml('item_id', d.item_id || '', invOpts, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
                    esc(d.cantidad || '1') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Motivo</label><input type="text" name="motivo" value="' +
                    esc(d.motivo || 'Compra proveedor') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Referencia</label><input type="text" name="referencia" value="' +
                    esc(d.referencia || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Fecha</label><input type="datetime-local" name="fecha" value="' +
                    esc(d.fecha || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-maquinaria'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nueva Maquinaria',
                    body: '<form id="frm-maq">' + buildMaqFormHtml({}, true) + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        var registrarCompra = data.registrar_compra === true;
                        delete data.registrar_compra;

                        DsCrud.api('../api/inventario_maquinaria.php', 'POST', data, function (res) {
                            if (!registrarCompra) {
                                DsCrud.toast('Maquinaria creada', 'success');
                                DsCrud.closeModal();
                                location.reload();
                                return;
                            }
                            var compraPayload = {
                                tipo_inventario: 'maquinaria',
                                item_id: res.ID,
                                cantidad: 1
                            };
                            DsCrud.api('../api/compras.php', 'POST', compraPayload, function () {
                                DsCrud.toast('Maquinaria creada y compra registrada', 'success');
                                DsCrud.closeModal();
                                location.reload();
                            }, function (e) {
                                DsCrud.toast('Maquinaria creada, pero no se pudo registrar la compra: ' + e, 'error');
                                DsCrud.closeModal();
                                location.reload();
                            });
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.addEvent(DsCrud.getById('btn-compra-maquinaria'), 'click', function () {
                if (!maqInventoryOptions.length) {
                    DsCrud.toast('No hay maquinaria disponible', 'error');
                    return;
                }
                DsCrud.openModal({
                    title: 'Registrar compra de maquinaria',
                    body: '<form id="frm-compra-maq">' + buildCompraMaqFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.tipo_inventario = 'maquinaria';
                        if (data.fecha) {
                            data.fecha = data.fecha.replace('T', ' ');
                        }
                        DsCrud.api('../api/compras.php', 'POST', data, function () {
                            DsCrud.toast('Compra registrada', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.initLegacyTable('maq-table', {
                onEdit: function (id) {
                    DsCrud.api('../api/inventario_maquinaria.php?id=' + id, 'GET', null, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar Maquinaria #' + id,
                            body: '<form id="frm-maq">' + buildMaqFormHtml(d, false) + '</form>',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.api('../api/inventario_maquinaria.php', 'PUT', data,
                                    function () {
                                        DsCrud.toast('Maquinaria actualizada',
                                            'success');
                                        DsCrud.closeModal();
                                        location.reload();
                                    },
                                    function (e) {
                                        DsCrud.toast(e, 'error');
                                    });
                            }
                        });
                    }, function (e) {
                        DsCrud.toast('Error: ' + e, 'error');
                    });
                },
                onDelete: function (id) {
                    DsCrud.confirm('Eliminar maquinaria #' + id + '?', function () {
                        DsCrud.api('../api/inventario_maquinaria.php', 'DELETE', {
                            id: id
                        }, function () {
                            DsCrud.toast('Maquinaria eliminada', 'success');
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    });
                }
            });
        })();
    </script>
<?php endif; ?>
</body>

</html>
