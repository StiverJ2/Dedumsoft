<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$maq_estado = $_GET['maq_estado'] ?? '';
$maq_rows = [];
$proveedor_options = [];
$ubicacion_options = [];
$tipo_maquinaria_options = [];

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

function format_maq_estado_badge($estado)
{
    $estado = trim((string) $estado);
    if ($estado === '')
        return '';
    $key = strtolower($estado);
    $label = ucwords(str_replace('_', ' ', $estado));
    $cls = 'ds-badge--neutral';
    if ($key === 'operativa')
        $cls = 'ds-badge--success';
    elseif ($key === 'mantenimiento')
        $cls = 'ds-badge--warning';
    elseif ($key === 'averiada')
        $cls = 'ds-badge--danger';
    elseif ($key === 'fuera_servicio' || strpos($key, 'fuera') !== false)
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

// Obtener opciones de estados de maquinaria (valores por defecto)
$maq_estado_options = [];
if ($legacy) {
    $maq_estado_options = [
        ['value' => 'operativa', 'label' => 'Operativa'],
        ['value' => 'mantenimiento', 'label' => 'Mantenimiento'],
        ['value' => 'averiada', 'label' => 'Averiada'],
        ['value' => 'fuera_servicio', 'label' => 'Fuera de servicio']
    ];
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo_maquinaria_id, tipo_nombre, estado, ubicacion_id, ubicacion_nombre FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $maq_estado !== '' ? $maq_estado : null, $maq_estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
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
            </div>
        </div>
        <?php if ($legacy): ?>
            <form method="get" action="inventario_maquinaria.php#inv-maquinaria"
                class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="maq-estado">Estado</label>
                    <select id="maq-estado" name="maq_estado" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="operativa" <?php echo $maq_estado === 'operativa' ? 'selected' : ''; ?>>Operativa
                        </option>
                        <option value="mantenimiento" <?php echo $maq_estado === 'mantenimiento' ? 'selected' : ''; ?>>
                            Mantenimiento</option>
                        <option value="averiada" <?php echo $maq_estado === 'averiada' ? 'selected' : ''; ?>>Averiada
                        </option>
                        <option value="fuera_servicio" <?php echo $maq_estado === 'fuera_servicio' ? 'selected' : ''; ?>>
                            Fuera de servicio</option>
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
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo format_maq_tipo_badge($row['tipo_nombre'] ?? ''); ?></td>
                                <td><?php echo format_maq_estado_badge($row['estado']); ?></td>
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

        function formatMaqEstado(estado) {
            if (!estado) return '';
            var key = estado.toLowerCase();
            var label = estado.replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                return c.toUpperCase();
            });
            var cls = 'ds-badge--neutral';
            if (key === 'operativa') cls = 'ds-badge--success';
            else if (key === 'mantenimiento') cls = 'ds-badge--warning';
            else if (key === 'averiada') cls = 'ds-badge--danger';
            else if (key === 'fuera_servicio' || key.indexOf('fuera') > -1) cls = 'ds-badge--muted';
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
                maqEstadoOptions = resEstados.data.DATOS || [{
                    value: 'operativa',
                    label: 'Operativa'
                },
                {
                    value: 'mantenimiento',
                    label: 'Mantenimiento'
                },
                {
                    value: 'averiada',
                    label: 'Averiada'
                },
                {
                    value: 'fuera_servicio',
                    label: 'Fuera de servicio'
                }
                ];

                proveedoresCache = (resProv.data.DATOS || []).map(p => ({
                    value: p.id,
                    label: `${p.nombre} (${p.tipo})`
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
                // Usar valores por defecto y continuar
                maqEstadoOptions = [{
                    value: 'operativa',
                    label: 'Operativa'
                },
                {
                    value: 'mantenimiento',
                    label: 'Mantenimiento'
                },
                {
                    value: 'averiada',
                    label: 'Averiada'
                },
                {
                    value: 'fuera_servicio',
                    label: 'Fuera de servicio'
                }
                ];
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
                        name: 'tipo_maquinaria_id',
                        label: 'Tipo',
                        type: 'select',
                        value: data.tipo_maquinaria_id,
                        options: tipoOpts,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'estado',
                        label: 'Estado',
                        type: 'select',
                        value: data.estado,
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
                DsCrud.openModal({
                    title: 'Nueva Maquinaria',
                    body: '<form id="frm-maq">' + buildMaqForm() + '</form>',
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
                        DsCrud.api('../api/inventario_maquinaria.php', 'POST', payload, function () {
                            DsCrud.toast('Maquinaria creada', 'success');
                            maqTable.ajax.reload();
                            DsCrud.closeModal();
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
                        data: 'nombre'
                    },
                    {
                        data: 'tipo_nombre',
                        render: formatMaqTipo
                    },
                    {
                        data: 'estado',
                        render: formatMaqEstado
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

            var maqEstadoOptions = <?php echo json_encode($maq_estado_options); ?>;

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

            function buildMaqFormHtml(d) {
                d = d || {};
                var maqProveedores = [];
                for (var i = 0; i < proveedorOptions.length; i++) {
                    if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(maquinaria)') > -1) {
                        maqProveedores.push(proveedorOptions[i]);
                    }
                }
                if (maqProveedores.length === 1) maqProveedores = proveedorOptions;

                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
                    esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
                    selectHtml('tipo_maquinaria_id', d.tipo_maquinaria_id || '', tipoMaquinariaOptions, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Estado <span style="color:red">*</span></label>' +
                    selectHtml('estado', d.estado || 'operativa', maqEstadoOptions, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Ubicacion</label>' +
                    selectHtml('ubicacion_id', d.ubicacion_id || '', ubicacionOptions, false) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
                    selectHtml('proveedor_id', d.proveedor_id || '', maqProveedores, false) + '</div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-maquinaria'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nueva Maquinaria',
                    body: '<form id="frm-maq">' + buildMaqFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        DsCrud.api('../api/inventario_maquinaria.php', 'POST', data, function () {
                            DsCrud.toast('Maquinaria creada', 'success');
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
                            body: '<form id="frm-maq">' + buildMaqFormHtml(d) + '</form>',
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