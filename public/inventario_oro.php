<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE ORO
 * ============================================================================
 * 
 * Página de gestión del inventario de oro (metales preciosos).
 * Permite visualizar, agregar, editar y eliminar registros de oro.
 * 
 * Características:
 * - Tabla de inventario con filtros (tipo de oro)
 * - Modal para crear/editar registros
 * - Modal para registrar compras
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 2 (Inventario)
 * 
 * Parámetros GET (solo legacy):
 * - oro_tipo: Filtrar por tipo de oro (10k, 14k, etc.)
 * 
 * APIs utilizadas:
 * - GET /api/inventario_oro.php - Listar oro
 * - POST /api/inventario_oro.php - Crear registro
 * - PATCH /api/inventario_oro.php - Actualizar registro
 * - DELETE /api/inventario_oro.php - Eliminar registro
 * - POST /api/compras.php - Registrar compra
 * - GET /api/opciones.php?tipo=tipos_oro - Tipos de oro
 * - GET /api/proveedores.php - Lista de proveedores
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
require_menu_access(2); // Menú: Inventario

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();

// Filtros de búsqueda (solo usados en modo legacy)
$oro_tipo = trim((string) ($_GET['oro_tipo'] ?? ''));
$oro_tipo_id = null;
$oro_rows = [];
$proveedor_options = [];
$oro_tipo_options = [];

// Cargar proveedores para dropdown
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

    if ($oro_tipo !== '') {
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
}

if ($legacy) {
    try {
        $oro_limit = $oro_tipo_id !== null ? 200 : 20;
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo_id, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $oro_limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $oro_tipo_id !== null ? $oro_tipo_id : null, $oro_tipo_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $oro_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
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
        <?php if ($legacy): ?>
            <form method="get" action="inventario_oro.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="oro-tipo">Tipo</label>
                    <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($oro_tipo_options as $tipo): ?>
                            <option value="<?php echo (int) $tipo['value']; ?>" <?php echo (string) $oro_tipo_value === (string) $tipo['value'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $tipo['label']); ?>
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
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
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
                axios.get('api/opciones.php?tipo=tipos_oro'),
                axios.get('api/proveedores.php?limit=500')
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
                    label: `${p.nombre} (${p.tipo_nombre})`
                }));
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
            });

            function buildOroForm(data) {
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
                        DsCrud.api('api/inventario_oro.php', 'POST', payload, function () {
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
                DsCrud.api('api/inventario_oro.php?id=' + row.id, 'GET', null, function (res) {
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
                            DsCrud.api('api/inventario_oro.php', 'PATCH', payload, function () {
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
                    DsCrud.api('api/inventario_oro.php', 'DELETE', {
                        id: row.id
                    }, function () {
                        DsCrud.toast('Oro eliminado', 'success');
                        oroTable.ajax.reload();
                    }, function (e) {
                        DsCrud.toast(e, 'error');
                    });
                });
            };

            function buildCompraOroForm(options, data) {
                data = data || {};
                return DsCrud.field({
                    name: 'item_id',
                    label: 'Inventario de oro',
                    type: 'select',
                    value: data.item_id,
                    options: options,
                    required: true
                }) +
                    DsCrud.field({
                        name: 'cantidad',
                        label: 'Cantidad (gramos)',
                        type: 'number',
                        value: data.cantidad,
                        required: true,
                        attrs: 'step="0.01" min="0"'
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
            }

            function openOroCompra() {
                axios.get('api/inventario_oro.php?limit=500').then(function (res) {
                    var items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
                    if (!items.length) {
                        DsCrud.toast('No hay inventario de oro disponible', 'warning');
                        return;
                    }
                    var options = [{
                        value: '',
                        label: '-- Seleccione --'
                    }].concat(items.map(function (it) {
                        var label = (it.tipo_oro_nombre || 'Oro') + ' #' + it.id;
                        return {
                            value: it.id,
                            label: label
                        };
                    }));

                    DsCrud.openModal({
                        title: 'Registrar compra de oro',
                        body: '<form id="frm-compra-oro">' + buildCompraOroForm(options) + '</form>',
                        onSave: function (m) {
                            var f = m.querySelector('#frm-compra-oro');
                            if (!f.checkValidity()) {
                                f.reportValidity();
                                return;
                            }
                            var fd = new FormData(f),
                                payload = {
                                    tipo_inventario: 'oro'
                                };
                            fd.forEach(function (v, k) {
                                payload[k] = v;
                            });
                            if (payload.fecha) {
                                payload.fecha = payload.fecha.replace('T', ' ');
                            }
                            DsCrud.api('api/compras.php', 'POST', payload, function () {
                                DsCrud.toast('Compra registrada', 'success');
                                oroTable.ajax.reload();
                                DsCrud.closeModal();
                            }, function (e) {
                                DsCrud.toast(e, 'error');
                            });
                        }
                    });
                }).catch(function () {
                    DsCrud.toast('Error cargando inventario', 'error');
                });
            }

            oroTable = $('#oro-table').DataTable({
                ajax: {
                    url: 'api/inventario_oro.php?limit=500',
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
            $('#btn-compra-oro').on('click', openOroCompra);
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
            var oroInventoryOptions = <?php echo json_encode(array_map(function ($row) {
                return [
                    'value' => $row['id'],
                    'label' => ($row['tipo_oro_nombre'] ?? 'Oro') . ' #' . $row['id']
                ];
            }, $oro_rows)); ?>;

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

            function buildCompraOroFormHtml(d) {
                d = d || {};
                var invOpts = [{ value: '', label: '-- Seleccione --' }].concat(oroInventoryOptions);
                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Inventario de oro <span style="color:red">*</span></label>' +
                    selectHtml('item_id', d.item_id || '', invOpts, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad (gramos) <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
                    esc(d.cantidad || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Motivo</label><input type="text" name="motivo" value="' +
                    esc(d.motivo || 'Compra proveedor') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Referencia</label><input type="text" name="referencia" value="' +
                    esc(d.referencia || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Fecha</label><input type="datetime-local" name="fecha" value="' +
                    esc(d.fecha || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-oro'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nuevo Inventario Oro',
                    body: '<form id="frm-oro">' + buildOroFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        DsCrud.apiLegacy('api/inventario_oro.php', 'POST', data, function () {
                            DsCrud.toast('Oro creado', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.addEvent(DsCrud.getById('btn-compra-oro'), 'click', function () {
                if (!oroInventoryOptions.length) {
                    DsCrud.toast('No hay inventario de oro disponible', 'error');
                    return;
                }
                DsCrud.openModal({
                    title: 'Registrar compra de oro',
                    body: '<form id="frm-compra-oro">' + buildCompraOroFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.tipo_inventario = 'oro';
                        if (data.fecha) {
                            data.fecha = data.fecha.replace('T', ' ');
                        }
                        DsCrud.apiLegacy('api/compras.php', 'POST', data, function () {
                            DsCrud.toast('Compra registrada', 'success');
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
                    DsCrud.apiLegacy('api/inventario_oro.php?id=' + id, 'GET', null, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar Oro #' + id,
                            body: '<form id="frm-oro">' + buildOroFormHtml(d) + '</form>',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.apiLegacy('api/inventario_oro.php', 'PATCH', data,
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
                        DsCrud.apiLegacy('api/inventario_oro.php', 'DELETE', {
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
