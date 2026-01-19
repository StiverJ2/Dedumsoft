<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$area = $_GET['area'] ?? '';
$ubicaciones_rows = [];
$area_options = [];

// Obtener áreas desde tabla de catálogo
if ($legacy) {
    try {
        $stmt = $connLogic->prepare('SELECT id, codigo, nombre FROM areas WHERE activo = true ORDER BY orden, nombre');
        $stmt->execute();
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $area_options = array_map(function ($a) {
            return ['value' => $a['id'], 'label' => $a['nombre']];
        }, $areas);
    } catch (PDOException $e) {
        error_log('areas error: ' . $e->getMessage());
        // Fallback a valores por defecto
        $area_options = [
            ['value' => 1, 'label' => 'General'],
            ['value' => 2, 'label' => 'Producción'],
            ['value' => 3, 'label' => 'Almacén'],
            ['value' => 4, 'label' => 'Ventas'],
            ['value' => 5, 'label' => 'Oficina'],
            ['value' => 6, 'label' => 'Taller']
        ];
    }
}

function format_area_badge($area)
{
    $a = strtolower($area);
    $badge = 'neutral';
    switch ($a) {
        case 'produccion':
        case 'producción':
            $badge = 'warning';
            break;
        case 'almacen':
        case 'almacén':
            $badge = 'info';
            break;
        case 'ventas':
            $badge = 'success';
            break;
        case 'oficina':
            $badge = 'muted';
            break;
        case 'taller':
            $badge = 'danger';
            break;
    }
    $display = ucfirst($a);
    return '<span class="ds-badge ds-badge--' . $badge . '">' . htmlspecialchars($display) . '</span>';
}

function format_activo_badge($activo)
{
    if ($activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, descripcion, area_id, area_nombre, activo FROM fun_obtener_ubicaciones(:offset, :limit, :area_id, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 50, PDO::PARAM_INT);
        $stmt->bindValue(':area_id', $area !== '' ? (int) $area : null, $area !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $ubicaciones_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ubicaciones legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
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
        <?php if ($legacy): ?>
            <form method="get" action="ubicaciones.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="ubic-area">Área</label>
                    <select id="ubic-area" name="area" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <option value="Produccion" <?php echo $area === 'Produccion' ? 'selected' : ''; ?>>Producción
                        </option>
                        <option value="Almacen" <?php echo $area === 'Almacen' ? 'selected' : ''; ?>>Almacén</option>
                        <option value="Ventas" <?php echo $area === 'Ventas' ? 'selected' : ''; ?>>Ventas</option>
                        <option value="Oficina" <?php echo $area === 'Oficina' ? 'selected' : ''; ?>>Oficina</option>
                        <option value="Taller" <?php echo $area === 'Taller' ? 'selected' : ''; ?>>Taller</option>
                        <option value="General" <?php echo $area === 'General' ? 'selected' : ''; ?>>General</option>
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
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['descripcion'] ?? '')); ?></td>
                                <td><?php echo format_area_badge($row['area_nombre'] ?? 'General'); ?></td>
                                <td><?php echo format_activo_badge($row['activo']); ?></td>
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
    <!-- Load scripts before inline JS -->
    <script src="assets/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="assets/jquery.dataTables.min.js"></script>
    <script src="assets/dataTables.bootstrap5.min.js"></script>
    <script src="../bootstrap/popper.min.js"></script>
    <script src="../bootstrap/bootstrap.min.js"></script>
    <script>window.DEDUMSOFT_ICON_MODE = 'emoji';</script>
    <script src="assets/crud.js"></script>
    <script>
        let ubicacionesTable = null;
        let areaOptions = [];

        const formatAreaBadge = (area) => {
            const a = (area || 'General').toLowerCase();
            let badge = 'neutral';
            switch (a) {
                case 'produccion':
                case 'producción':
                    badge = 'warning';
                    break;
                case 'almacen':
                case 'almacén':
                    badge = 'info';
                    break;
                case 'ventas':
                    badge = 'success';
                    break;
                case 'oficina':
                    badge = 'muted';
                    break;
                case 'taller':
                    badge = 'danger';
                    break;
            }
            const display = a.charAt(0).toUpperCase() + a.slice(1);
            return `<span class="ds-badge ds-badge--${badge}">${display}</span>`;
        };

        const formatActivoBadge = (activo) => {
            if (activo) {
                return '<span class="ds-badge ds-badge--success">Activo</span>';
            }
            return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
        };

        const buildUbicacionForm = (data) => {
            data = data || {};
            return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre || '',
                required: true,
                placeholder: 'Ej: Bodega Principal'
            }) +
                DsCrud.field({
                    name: 'descripcion',
                    label: 'Descripción',
                    type: 'textarea',
                    value: data.descripcion || '',
                    placeholder: 'Descripción opcional...'
                }) +
                DsCrud.field({
                    name: 'area_id',
                    label: 'Área',
                    type: 'select',
                    value: data.area_id || 1,
                    options: areaOptions
                });
        };

        const reloadTable = () => {
            if (ubicacionesTable) {
                ubicacionesTable.ajax.reload(null, false);
            }
        };

        const openCreateModal = () => {
            DsCrud.openModal({
                title: 'Nueva Ubicación',
                body: `<form id="frm-ubicacion">${buildUbicacionForm()}</form>`,
                saveText: 'Crear',
                onSave: (modalEl, close) => {
                    const form = modalEl.querySelector('#frm-ubicacion');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    const fd = new FormData(form);
                    const payload = {};
                    fd.forEach((value, key) => {
                        payload[key] = value;
                    });
                    DsCrud.api('../api/ubicaciones.php', 'POST', payload, (success, resp) => {
                        if (success) {
                            DsCrud.toast('Ubicación creada');
                            reloadTable();
                            close();
                        } else {
                            DsCrud.toast(resp.MENSAJE || 'Error al crear', 'error');
                        }
                    });
                }
            });
        };

        const openEditModal = (row) => {
            DsCrud.openModal({
                title: 'Editar Ubicación',
                body: `<form id="frm-ubicacion">${buildUbicacionForm(row)}</form>`,
                saveText: 'Guardar',
                onSave: (modalEl, close) => {
                    const form = modalEl.querySelector('#frm-ubicacion');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    const fd = new FormData(form);
                    const payload = {
                        id: row.id,
                        ...Object.fromEntries(fd)
                    };
                    DsCrud.api('../api/ubicaciones.php', 'PUT', payload, (success, resp) => {
                        if (success) {
                            DsCrud.toast('Ubicación actualizada');
                            reloadTable();
                            close();
                        } else {
                            DsCrud.toast(resp.MENSAJE || 'Error al actualizar', 'error');
                        }
                    });
                }
            });
        };

        const openDeleteConfirm = (row) => {
            DsCrud.confirm({
                title: 'Eliminar Ubicación',
                message: `¿Desea eliminar la ubicación "${row.nombre}"?`,
                warning: 'Esta acción desactivará la ubicación.',
                confirmText: 'Eliminar',
                onConfirm: () => {
                    DsCrud.api('../api/ubicaciones.php', 'DELETE', {
                        id: row.id
                    }, (success, resp) => {
                        if (success) {
                            DsCrud.toast('Ubicación eliminada');
                            reloadTable();
                        } else {
                            DsCrud.toast(resp.MENSAJE || 'Error al eliminar', 'error');
                        }
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', async () => {
            // Cargar opciones desde la API
            try {
                const response = await axios.get('../api/opciones.php?tipo=areas');
                areaOptions = response.data.DATOS || [];
            } catch (error) {
                console.error('Error cargando opciones:', error);
                // Fallback a valores por defecto
                areaOptions = [{
                    value: 'General',
                    label: 'General'
                },
                {
                    value: 'Produccion',
                    label: 'Producción'
                },
                {
                    value: 'Almacen',
                    label: 'Almacén'
                },
                {
                    value: 'Ventas',
                    label: 'Ventas'
                },
                {
                    value: 'Oficina',
                    label: 'Oficina'
                },
                {
                    value: 'Taller',
                    label: 'Taller'
                }
                ];
            }

            ubicacionesTable = $('#ubicaciones-table').DataTable({
                ajax: {
                    url: '../api/ubicaciones.php?limit=500&offset=0',
                    dataSrc: 'DATOS'
                },
                columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'descripcion',
                    defaultContent: ''
                },
                {
                    data: 'area_nombre',
                    render: (data) => formatAreaBadge(data || 'General')
                },
                {
                    data: 'activo',
                    render: (data) => formatActivoBadge(data)
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => type === 'display' ? DsCrud.actionButtons(row.id) :
                        ''
                }
                ],
                language: {
                    url: 'assets/dataTables.es-ES.json'
                }
            });

            // Add button
            document.getElementById('btn-add-ubicacion').addEventListener('click', openCreateModal);

            // Edit/Delete buttons (delegated)
            document.getElementById('ubicaciones-table').addEventListener('click', (e) => {
                const editBtn = e.target.closest('.ds-action-btn[data-action="edit"]');
                if (editBtn) {
                    const row = ubicacionesTable.row(editBtn.closest('tr')).data();
                    openEditModal(row);
                    return;
                }

                const deleteBtn = e.target.closest('.ds-action-btn[data-action="delete"]');
                if (deleteBtn) {
                    const row = ubicacionesTable.row(deleteBtn.closest('tr')).data();
                    openDeleteConfirm(row);
                }
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script src="assets/table-sort.js"></script>
    <script src="assets/crud-legacy.js"></script>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('ubicaciones-table');

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

            function buildUbicacionFormHtml(d) {
                d = d || {};
                var areaOpts = <?php echo json_encode($area_options); ?>;
                return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
                    esc(d.nombre || '') +
                    '" placeholder="Ej: Bodega Principal" style="width:100%;padding:6px;font-size:14px;" required></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Descripción</label><textarea name="descripcion" placeholder="Descripción opcional..." style="width:100%;padding:6px;font-size:14px;min-height:60px;">' +
                    esc(d.descripcion || '') + '</textarea></div>' +
                    '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Área</label>' +
                    selectHtml('area_id', d.area_id || 1, areaOpts, false) + '</div>';
            }

            DsCrud.addEvent(DsCrud.getById('btn-add-ubicacion'), 'click', function () {
                DsCrud.openModal({
                    title: 'Nueva Ubicación',
                    body: '<form id="frm-ubicacion">' + buildUbicacionFormHtml() + '</form>',
                    saveText: 'Crear',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        DsCrud.api('../api/ubicaciones.php', 'POST', data, function () {
                            DsCrud.toast('Ubicación creada', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            });

            DsCrud.initLegacyTable('ubicaciones-table', {
                onEdit: function (id) {
                    DsCrud.api('../api/ubicaciones.php?id=' + id, 'GET', null, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar Ubicación #' + id,
                            body: '<form id="frm-ubicacion">' + buildUbicacionFormHtml(d) +
                                '</form>',
                            saveText: 'Guardar',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.api('../api/ubicaciones.php', 'PUT', data,
                                    function () {
                                        DsCrud.toast('Ubicación actualizada',
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
                    DsCrud.confirm('¿Eliminar ubicación #' + id + '?', function () {
                        DsCrud.api('../api/ubicaciones.php', 'DELETE', {
                            id: id
                        }, function () {
                            DsCrud.toast('Ubicación eliminada', 'success');
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
