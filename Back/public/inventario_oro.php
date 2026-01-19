<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$oro_tipo = $_GET['oro_tipo'] ?? '';
$oro_rows = [];
$proveedor_options = [];
$oro_tipo_options = [];

try {
    $stmt = $connLogic->query("SELECT id, nombre, tipo FROM proveedores WHERE activo = TRUE ORDER BY nombre");
    $proveedor_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario oro proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Obtener opciones de tipos de oro desde tabla de catálogo
if ($legacy) {
    try {
        $stmt = $connLogic->prepare('SELECT id, codigo, nombre FROM tipos_oro WHERE activo = true ORDER BY orden, kilates');
        $stmt->execute();
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $oro_tipo_options = array_map(function ($t) {
            return ['value' => $t['id'], 'label' => $t['nombre']];
        }, $tipos);
    } catch (PDOException $e) {
        error_log('tipos oro error: ' . $e->getMessage());
        // Fallback a valores por defecto
        $oro_tipo_options = [
            ['value' => 1, 'label' => '10k'],
            ['value' => 2, 'label' => '14k'],
            ['value' => 3, 'label' => '18k'],
            ['value' => 4, 'label' => '22k'],
            ['value' => 5, 'label' => '24k']
        ];
    }
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo_id, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $oro_tipo !== '' ? (int) $oro_tipo : null, $oro_tipo !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $oro_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
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
            </div>
        </div>
        <?php if ($legacy): ?>
            <form method="get" action="inventario_oro.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="oro-tipo">Tipo</label>
                    <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="10k" <?php echo $oro_tipo === '10k' ? 'selected' : ''; ?>>10k</option>
                        <option value="14k" <?php echo $oro_tipo === '14k' ? 'selected' : ''; ?>>14k</option>
                        <option value="18k" <?php echo $oro_tipo === '18k' ? 'selected' : ''; ?>>18k</option>
                        <option value="22k" <?php echo $oro_tipo === '22k' ? 'selected' : ''; ?>>22k</option>
                        <option value="24k" <?php echo $oro_tipo === '24k' ? 'selected' : ''; ?>>24k</option>
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
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['tipo_oro_nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['peso_gramos']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['precio_gramo']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['proveedor_nombre'] ?? '')); ?></td>
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
        $(function () {
            const dtLang = {
                url: 'assets/dataTables.es-ES.json'
            };
            let oroTable;
            let proveedoresCache = [];
            let oroTipoOptions = [];

            // Cargar opciones y proveedores en paralelo
            Promise.all([
                axios.get('../api/opciones.php?tipo=tipos_oro'),
                axios.get('../api/proveedores.php?limit=500')
            ]).then(([resOpciones, resProv]) => {
                oroTipoOptions = resOpciones.data.DATOS || [{
                    value: '10k',
                    label: '10k'
                },
                {
                    value: '14k',
                    label: '14k'
                },
                {
                    value: '18k',
                    label: '18k'
                },
                {
                    value: '22k',
                    label: '22k'
                },
                {
                    value: '24k',
                    label: '24k'
                }
                ];

                proveedoresCache = (resProv.data.DATOS || []).map(p => ({
                    value: p.id,
                    label: `${p.nombre} (${p.tipo})`
                }));

                initOroTable();
            }).catch(error => {
                console.error('Error cargando datos:', error);
                // Usar valores por defecto y continuar
                oroTipoOptions = [{
                    value: '10k',
                    label: '10k'
                },
                {
                    value: '14k',
                    label: '14k'
                },
                {
                    value: '18k',
                    label: '18k'
                },
                {
                    value: '22k',
                    label: '22k'
                },
                {
                    value: '24k',
                    label: '24k'
                }
                ];
                initOroTable();
            });

            const initOroTable = () => {
                data = data || {};
                var provOpts = [{
                    value: '',
                    label: '-- Sin proveedor --'
                }].concat(
                    proveedoresCache.filter(function (p) {
                        return p.label.indexOf('(oro)') > -1 || !data.id;
                    })
                );
                return DsCrud.field({
                    name: 'tipo_oro_id',
                    label: 'Tipo de Oro',
                    type: 'select',
                    value: data.tipo_oro_id,
                    options: oroTipoOptions,
                    required: true
                }) +
                    DsCrud.field({
                        name: 'peso_gramos',
                        label: 'Peso (gramos)',
                        type: 'number',
                        value: data.peso_gramos,
                        required: true,
                        attrs: 'step="0.01" min="0"'
                    }) +
                    DsCrud.field({
                        name: 'precio_gramo',
                        label: 'Precio/gramo',
                        type: 'number',
                        value: data.precio_gramo,
                        required: true,
                        attrs: 'step="0.01" min="0"'
                    }) +
                    DsCrud.field({
                        name: 'proveedor_id',
                        label: 'Proveedor',
                        type: 'select',
                        value: data.proveedor_id,
                        options: provOpts
                    });
            }

            function openOroCreate() {
                DsCrud.openModal({
                    title: 'Nuevo Inventario Oro',
                    body: '<form id="frm-oro">' + buildOroForm() + '</form>',
                    onSave: function (m) {
                        var f = m.querySelector('#frm-oro');
                        if (!f.checkValidity()) {
                            f.reportValidity();
                            return;
                        }
                        var fd = new FormData(f),
                            payload = {};
                        fd.forEach(function (v, k) {
                            payload[k] = v;
                        });
                        DsCrud.api('../api/inventario_oro.php', 'POST', payload, function () {
                            DsCrud.toast('Oro creado', 'success');
                            oroTable.ajax.reload();
                            DsCrud.closeModal();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            };

            const openOroEdit = (row) => {
                DsCrud.api('../api/inventario_oro.php?id=' + row.id, 'GET', null, function (res) {
                    var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
                    DsCrud.openModal({
                        title: 'Editar Oro #' + d.id,
                        body: '<form id="frm-oro">' + buildOroForm(d) + '</form>',
                        onSave: function (m) {
                            var f = m.querySelector('#frm-oro');
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
                            DsCrud.api('../api/inventario_oro.php', 'PUT', payload, function () {
                                DsCrud.toast('Oro actualizado', 'success');
                                oroTable.ajax.reload();
                                DsCrud.closeModal();
                            }, function (e) {
                                DsCrud.toast(e, 'error');
                            });
                        }
                    });
                });
            };

            const openOroDelete = (row) => {
                DsCrud.confirm('Eliminar registro de oro #' + row.id + '?', function () {
                    DsCrud.api('../api/inventario_oro.php', 'DELETE', {
                        id: row.id
                    }, function () {
                        DsCrud.toast('Oro eliminado', 'success');
                        oroTable.ajax.reload();
                    }, function (e) {
                        DsCrud.toast(e, 'error');
                    });
                });
            };

            oroTable = $('#oro-table').DataTable({
                ajax: {
                    url: '../api/inventario_oro.php?limit=500',
                    dataSrc: 'DATOS'
                },
                columns: [{
                    data: 'id'
                },
                {
                    data: 'tipo_oro_nombre'
                },
                {
                    data: 'peso_gramos'
                },
                {
                    data: 'precio_gramo'
                },
                {
                    data: 'proveedor_nombre',
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

            $('#btn-add-oro').on('click', openOroCreate);
            $('#oro-table').on('click', '.ds-action-btn[data-action="edit"]', function () {
                openOroEdit(oroTable.row($(this).closest('tr')).data());
            });
            $('#oro-table').on('click', '.ds-action-btn[data-action="delete"]', function () {
                openOroDelete(oroTable.row($(this).closest('tr')).data());
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) {
                DedumTableSort.init('oro-table');
            }

            var proveedorOptions = <?php echo json_encode(array_merge(
                [['value' => '', 'label' => '-- Sin proveedor --']],
                array_map(function ($p) {
                                return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
                            }, $proveedor_options)
            )); ?>;

            var oroTipoOptions = <?php echo json_encode($oro_tipo_options); ?>;

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

            function buildOroFormHtml(d) {
                d = d || {};
                var oroProveedores = [];
                for (var i = 0; i < proveedorOptions.length; i++) {
                    if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(oro)') > -1) {
                        oroProveedores.push(proveedorOptions[i]);
                    }
                }
                if (oroProveedores.length === 1) oroProveedores = proveedorOptions;

                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
                    selectHtml('tipo_oro_id', d.tipo_oro_id || 1, oroTipoOptions, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Peso (gramos) <span style="color:red">*</span></label><input type="text" name="peso_gramos" value="' +
                    esc(d.peso_gramos || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Precio/gramo <span style="color:red">*</span></label><input type="text" name="precio_gramo" value="' +
                    esc(d.precio_gramo || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
                    selectHtml('proveedor_id', d.proveedor_id || '', oroProveedores, false) + '</div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-oro'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nuevo Inventario Oro',
                    body: '<form id="frm-oro">' + buildOroFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        DsCrud.api('../api/inventario_oro.php', 'POST', data, function () {
                            DsCrud.toast('Oro creado', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.initLegacyTable('oro-table', {
                onEdit: function (id) {
                    DsCrud.api('../api/inventario_oro.php?id=' + id, 'GET', null, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar Oro #' + id,
                            body: '<form id="frm-oro">' + buildOroFormHtml(d) + '</form>',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.api('../api/inventario_oro.php', 'PUT', data,
                                    function () {
                                        DsCrud.toast('Oro actualizado', 'success');
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
                    DsCrud.confirm('Eliminar oro #' + id + '?', function () {
                        DsCrud.api('../api/inventario_oro.php', 'DELETE', {
                            id: id
                        }, function () {
                            DsCrud.toast('Oro eliminado', 'success');
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
