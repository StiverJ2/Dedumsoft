<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE PROVEEDORES
 * ============================================================================
 * 
 * Página de gestión de proveedores de materiales.
 * Permite visualizar, agregar, editar y eliminar proveedores.
 * 
 * Características:
 * - Tabla de proveedores con filtro por tipo
 * - Modal para crear/editar proveedores
 * - Clasificación por tipo (Oro, Insumos, Maquinaria)
 * - Datos de contacto (nombre, teléfono)
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 6 (Proveedores)
 * 
 * Parámetros GET (solo legacy):
 * - tipo: Filtrar por tipo de proveedor
 * 
 * APIs utilizadas:
 * - GET /api/proveedores.php - Listar proveedores
 * - POST /api/proveedores.php - Crear proveedor
 * - PATCH /api/proveedores.php - Actualizar proveedor
 * - DELETE /api/proveedores.php - Eliminar proveedor
 * - GET /api/opciones.php?tipo=tipos_proveedor - Tipos de proveedor
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
require_menu_access(6); // Menú: Proveedores

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();

// Filtros de búsqueda (solo usados en modo legacy)
$tipo = trim((string) ($_GET['tipo'] ?? ''));
$tipo_id = null;
$proveedores_rows = [];
$tipo_proveedor_options = [];

// Obtener tipos de proveedor desde tabla de catálogo
try {
    $stmt = $connLogic->prepare('SELECT id, nombre FROM tipos_proveedor WHERE activo = true ORDER BY nombre');
    $stmt->execute();
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tipo_proveedor_options = array_map(function ($t) {
        return ['value' => $t['id'], 'label' => $t['nombre']];
    }, $tipos);
} catch (PDOException $e) {
    error_log('tipos proveedor error: ' . $e->getMessage());
    // Fallback a valores por defecto
    $tipo_proveedor_options = [
        ['value' => 1, 'label' => 'Oro'],
        ['value' => 2, 'label' => 'Insumos'],
        ['value' => 3, 'label' => 'Maquinaria']
    ];
}

if ($legacy && $tipo !== '') {
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

// Cargar datos para modo legacy
if ($legacy) {
    try {
        $prov_limit = $tipo_id !== null ? 200 : 20;
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo_proveedor_id, tipo_nombre, contacto, telefono FROM fun_obtener_proveedores(:offset, :limit, :tipo_id, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $prov_limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $tipo_id !== null ? $tipo_id : null, $tipo_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $proveedores_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
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
                                <?php echo htmlspecialchars((string) $opt['label']); ?>
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
                                <?php echo htmlspecialchars((string) $opt['label']); ?>
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
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['tipo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['contacto'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['telefono'] ?? '')); ?></td>
                                <td class="ds-actions-col"></td>
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
        document.addEventListener('DOMContentLoaded', async () => {
            let proveedoresTable;
            let tipoOptions = [];
            let tipoFilter = '';

            const applyProveedorFilters = () => {
                const tipoEl = document.getElementById('prov-tipo-modern');
                tipoFilter = tipoEl ? (tipoEl.value || '') : '';
                if (proveedoresTable) {
                    proveedoresTable.ajax.reload();
                }
            };

            // Cargar opciones desde la API
            try {
                const response = await axios.get('api/opciones.php?tipo=tipos_proveedor');
                tipoOptions = response.data.DATOS || [];
            } catch (error) {
                console.error('Error cargando opciones:', error);
                // Fallback a valores por defecto
                tipoOptions = [{
                    value: 'oro',
                    label: 'Oro'
                },
                {
                    value: 'insumos',
                    label: 'Insumos'
                },
                {
                    value: 'maquinaria',
                    label: 'Maquinaria'
                }
                ];
            }

            const buildProveedorForm = (data) => {
                data = data || {};
                return DsCrud.field({
                    name: 'nombre',
                    label: 'Nombre',
                    value: data.nombre,
                    required: true
                }) +
                    DsCrud.field({
                        name: 'tipo_proveedor_id',
                        label: 'Tipo',
                        type: 'select',
                        value: data.tipo_proveedor_id,
                        options: tipoOptions,
                        required: true
                    }) +
                    DsCrud.field({
                        name: 'contacto',
                        label: 'Contacto',
                        value: data.contacto
                    }) +
                    DsCrud.field({
                        name: 'telefono',
                        label: 'Teléfono',
                        value: data.telefono
                    }) +
                    DsCrud.field({
                        name: 'email',
                        label: 'Email',
                        type: 'email',
                        value: data.email
                    }) +
                    DsCrud.field({
                        name: 'direccion',
                        label: 'Dirección',
                        value: data.direccion
                    });
            };

            const openCreateModal = () => {
                DsCrud.openModal({
                    title: 'Nuevo Proveedor',
                    body: `<form id="frm-proveedor">${buildProveedorForm()}</form>`,
                    onSave: (modalEl) => {
                        const form = modalEl.querySelector('#frm-proveedor');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return;
                        }
                        const fd = new FormData(form);
                        const payload = Object.fromEntries(fd);
                        DsCrud.api('api/proveedores.php', 'POST', payload, (res) => {
                            DsCrud.toast('Proveedor creado', 'success');
                            proveedoresTable.ajax.reload();
                            DsCrud.closeModal();
                        }, (err) => {
                            DsCrud.toast(err, 'error');
                        });
                    }
                });
            };

            const openEditModal = (row) => {
                DsCrud.api(`api/proveedores.php?id=${row.id}`, 'GET', null, (res) => {
                    const prov = res.DATOS?.[0] ?? row;
                    DsCrud.openModal({
                        title: `Editar Proveedor #${prov.id}`,
                        body: `<form id="frm-proveedor">${buildProveedorForm(prov)}</form>`,
                        onSave: (modalEl) => {
                            const form = modalEl.querySelector('#frm-proveedor');
                            if (!form.checkValidity()) {
                                form.reportValidity();
                                return;
                            }
                            const fd = new FormData(form);
                            const payload = {
                                id: prov.id,
                                ...Object.fromEntries(fd)
                            };
                            DsCrud.api('api/proveedores.php', 'PATCH', payload, (res) => {
                                DsCrud.toast('Proveedor actualizado', 'success');
                                proveedoresTable.ajax.reload();
                                DsCrud.closeModal();
                            }, (err) => {
                                DsCrud.toast(err, 'error');
                            });
                        }
                    });
                });
            };

            const openDeleteConfirm = (row) => {
                DsCrud.confirm(`¿Eliminar proveedor "${row.nombre}"?`, () => {
                    DsCrud.api('api/proveedores.php', 'DELETE', {
                        id: row.id
                    }, (res) => {
                        DsCrud.toast('Proveedor eliminado', 'success');
                        proveedoresTable.ajax.reload();
                    }, (err) => {
                        DsCrud.toast(err, 'error');
                    });
                });
            };

            proveedoresTable = $('#proveedores-table').DataTable({
                ajax: {
                    url: 'api/proveedores.php',
                    data: (d) => {
                        d.limit = 500;
                        d.offset = 0;
                        if (tipoFilter) d.tipo_id = tipoFilter;
                    },
                    dataSrc: 'DATOS'
                },
                columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'tipo_nombre'
                },
                {
                    data: 'contacto',
                    defaultContent: ''
                },
                {
                    data: 'telefono',
                    defaultContent: ''
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return '';
                        return DsCrud.actionButtons(row.id);
                    }
                }
                ],
                language: {
                    url: 'assets/dataTables.es-ES.json'
                }
            });

            document.getElementById('btn-add-proveedor').addEventListener('click', openCreateModal);
            const tipoFilterBtn = document.getElementById('prov-filtrar-modern');
            const tipoClearBtn = document.getElementById('prov-limpiar-modern');
            const tipoSelect = document.getElementById('prov-tipo-modern');
            if (tipoFilterBtn) {
                tipoFilterBtn.addEventListener('click', applyProveedorFilters);
            }
            if (tipoClearBtn) {
                tipoClearBtn.addEventListener('click', () => {
                    if (tipoSelect) tipoSelect.value = '';
                    applyProveedorFilters();
                });
            }
            if (tipoSelect) {
                tipoSelect.addEventListener('change', applyProveedorFilters);
            }

            document.getElementById('proveedores-table').addEventListener('click', (e) => {
                const editBtn = e.target.closest('.ds-action-btn[data-action="edit"]');
                if (editBtn) {
                    const row = proveedoresTable.row(editBtn.closest('tr')).data();
                    openEditModal(row);
                    return;
                }

                const deleteBtn = e.target.closest('.ds-action-btn[data-action="delete"]');
                if (deleteBtn) {
                    const row = proveedoresTable.row(deleteBtn.closest('tr')).data();
                    openDeleteConfirm(row);
                }
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('proveedores-table');

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

            function buildProveedorFormHtml(d) {
                d = d || {};
                var tipoOpts = <?php echo json_encode($tipo_proveedor_options); ?>;
                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
                    esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
                    selectHtml('tipo_proveedor_id', d.tipo_proveedor_id || 1, tipoOpts, true) + '</div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Contacto</label><input type="text" name="contacto" value="' +
                    esc(d.contacto || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Telefono</label><input type="text" name="telefono" value="' +
                    esc(d.telefono || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Email</label><input type="text" name="email" value="' +
                    esc(d.email || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Direccion</label><input type="text" name="direccion" value="' +
                    esc(d.direccion || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-proveedor'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nuevo Proveedor',
                    body: '<form id="frm-proveedor">' + buildProveedorFormHtml() + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        DsCrud.apiLegacy('api/proveedores.php', 'POST', data, function () {
                            DsCrud.toast('Proveedor creado', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.initLegacyTable('proveedores-table', {
                onEdit: function (id) {
                    DsCrud.apiLegacy('api/proveedores.php?id=' + id, 'GET', null, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar Proveedor #' + id,
                            body: '<form id="frm-proveedor">' + buildProveedorFormHtml(d) +
                                '</form>',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.apiLegacy('api/proveedores.php', 'PATCH', data,
                                    function () {
                                        DsCrud.toast('Proveedor actualizado',
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
                    DsCrud.confirm('¿Eliminar proveedor #' + id + '?', function () {
                        DsCrud.apiLegacy('api/proveedores.php', 'DELETE', {
                            id: id
                        }, function () {
                            DsCrud.toast('Proveedor eliminado', 'success');
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
