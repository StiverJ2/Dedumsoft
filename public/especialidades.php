<?php
/**
 * =========================================================================
 * PÁGINA PÚBLICA: ESPECIALIDADES DE ARTESANOS
 * =========================================================================
 * 
 * Gestión del catálogo de especialidades usadas por artesanos.
 * Permite crear, editar y desactivar especialidades.
 * 
 * Características:
 * - Tabla con DataTables (moderno) y HTML (legacy)
 * - Modales CRUD con DsCrud
 * - Soft-delete (activo = false)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 8 (Especialidades)
 * 
 * APIs utilizadas:
 * - GET /api/catalogos/especialidades.php - Listar especialidades
 * - POST /api/catalogos/especialidades.php - Crear especialidad
 * - PATCH /api/catalogos/especialidades.php - Actualizar especialidad
 * - DELETE /api/catalogos/especialidades.php - Eliminar especialidad
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';

require_login('login.php');
require_menu_access(8); // Menú: Especialidades

$legacy = dedumsoft_is_legacy_browser();
$especialidades_rows = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, descripcion, activo FROM fun_obtener_especialidades(:offset, :limit, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 200, PDO::PARAM_INT);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $especialidades_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('especialidades legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

function format_activo_badge($activo)
{
    if ($activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Especialidades</h1>
        <p>Catálogo de especialidades para artesanos</p>
    </div>
    <div class="card">
        <div class="ds-toolbar">
            <strong>Listado de especialidades</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="especialidades-create-btn">+ Nueva especialidad</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="especialidades-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($especialidades_rows as $row): ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>">
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['descripcion'] ?? '')); ?></td>
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
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        let especialidadesTable;

        const formatActivo = (v) => {
            if (v) return '<span class="ds-badge ds-badge--success">Activo</span>';
            return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
        };

        const renderAcciones = (data, type, row) => {
            if (type !== 'display') return '';
            return DsCrud.actionButtons(row.id);
        };

        const buildEspecialidadForm = (data = {}) => {
            return [
                DsCrud.field({ name: 'nombre', label: 'Nombre', required: true, placeholder: 'Ej: Engaste', value: data.nombre || '' }),
                DsCrud.field({ name: 'descripcion', label: 'Descripción', type: 'textarea', placeholder: 'Opcional', value: data.descripcion || '' })
            ].join('');
        };

        const openCreateModal = () => {
            DsCrud.openModal({
                title: 'Nueva especialidad',
                body: buildEspecialidadForm(),
                saveText: 'Crear',
                cancelText: 'Cancelar',
                onSave: (modal, close, formData) => {
                    const nombre = (formData.nombre || '').trim();
                    const descripcion = (formData.descripcion || '').trim();

                    if (!nombre) {
                        DsCrud.toast('Nombre es requerido.', 'error');
                        return;
                    }

                    DsCrud.api('api/catalogos/especialidades.php', 'POST', {
                        nombre,
                        descripcion: descripcion || null
                    }, (success, resp) => {
                        if (success) {
                            DsCrud.toast(resp.MENSAJE || 'Especialidad creada');
                            if (especialidadesTable) {
                                especialidadesTable.ajax.reload(null, false);
                            }
                            close();
                        } else {
                            DsCrud.toast(resp.MENSAJE || 'Error al crear especialidad', 'error');
                        }
                    });
                }
            });
        };

        const openEditModal = (row) => {
            if (!row) return;
            DsCrud.openModal({
                title: `Editar especialidad #${DsCrud.escapeHtml(row.id)}`,
                body: buildEspecialidadForm(row),
                saveText: 'Guardar',
                cancelText: 'Cancelar',
                onSave: (modal, close, formData) => {
                    const nombre = (formData.nombre || '').trim();
                    const descripcion = (formData.descripcion || '').trim();

                    if (!nombre) {
                        DsCrud.toast('Nombre es requerido.', 'error');
                        return;
                    }

                    DsCrud.api('api/catalogos/especialidades.php', 'PATCH', {
                        id: row.id,
                        nombre,
                        descripcion: descripcion || null
                    }, (success, resp) => {
                        if (success) {
                            DsCrud.toast(resp.MENSAJE || 'Especialidad actualizada');
                            if (especialidadesTable) {
                                especialidadesTable.ajax.reload(null, false);
                            }
                            close();
                        } else {
                            DsCrud.toast(resp.MENSAJE || 'Error al actualizar especialidad', 'error');
                        }
                    });
                }
            });
        };

        const handleDelete = (row) => {
            if (!row) return;
            DsCrud.confirm(`¿Eliminar especialidad "${row.nombre}"?`, () => {
                DsCrud.api('api/catalogos/especialidades.php', 'DELETE', { id: row.id }, (success, resp) => {
                    if (success) {
                        DsCrud.toast(resp.MENSAJE || 'Especialidad eliminada');
                        if (especialidadesTable) {
                            especialidadesTable.ajax.reload(null, false);
                        }
                    } else {
                        DsCrud.toast(resp.MENSAJE || 'Error al eliminar especialidad', 'error');
                    }
                });
            });
        };

        $(() => {
            especialidadesTable = $('#especialidades-table').DataTable({
                ajax: {
                    url: 'api/catalogos/especialidades.php?limit=500&offset=0',
                    dataSrc: 'DATOS'
                },
                columns: [
                    { data: 'id' },
                    { data: 'nombre' },
                    { data: 'descripcion' },
                    { data: 'activo', render: formatActivo },
                    { data: null, orderable: false, searchable: false, render: renderAcciones }
                ],
                language: { url: 'assets/dataTables.es-ES.json' }
            });

            $('#especialidades-create-btn').on('click', () => {
                openCreateModal();
            });

            $('#especialidades-table').on('click', '.ds-action-btn[data-action="edit"]', function () {
                const row = especialidadesTable.row($(this).closest('tr')).data();
                openEditModal(row);
            });

            $('#especialidades-table').on('click', '.ds-action-btn[data-action="delete"]', function () {
                const row = especialidadesTable.row($(this).closest('tr')).data();
                handleDelete(row);
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('especialidades-table');

            function buildEspecialidadFormHtml(d) {
                d = d || {};
                var html = '';
                html += DsCrud.field({ name: 'nombre', label: 'Nombre', required: true, placeholder: 'Ej: Engaste', value: d.nombre || '' });
                html += DsCrud.field({ name: 'descripcion', label: 'Descripcion', type: 'textarea', placeholder: 'Opcional', value: d.descripcion || '' });
                return html;
            }

            var createBtn = DsCrud.getById('especialidades-create-btn');
            if (createBtn) {
                DsCrud.addEvent(createBtn, 'click', function () {
                    DsCrud.openModal({
                        title: 'Nueva especialidad',
                        body: '<form id="frm-especialidad">' + buildEspecialidadFormHtml() + '</form>',
                        saveText: 'Crear',
                        onSave: function (modal) {
                            if (!DsCrud.validateForm(modal)) return;
                            var data = DsCrud.getFormData(modal);
                            DsCrud.apiLegacy('api/catalogos/especialidades.php', 'POST', data, function () {
                                DsCrud.toast('Especialidad creada', 'success');
                                DsCrud.closeModal();
                                location.reload();
                            }, function (e) {
                                DsCrud.toast(e || 'Error al crear', 'error');
                            });
                        }
                    });
                });
            }

            DsCrud.initLegacyTable('especialidades-table', {
                onEdit: function (id) {
                    DsCrud.apiLegacy('api/catalogos/especialidades.php', 'GET', { id: id }, function (res) {
                        var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                        DsCrud.openModal({
                            title: 'Editar especialidad #' + id,
                            body: '<form id="frm-especialidad">' + buildEspecialidadFormHtml(d) + '</form>',
                            saveText: 'Guardar',
                            onSave: function (modal) {
                                if (!DsCrud.validateForm(modal)) return;
                                var data = DsCrud.getFormData(modal);
                                data.id = id;
                                DsCrud.apiLegacy('api/catalogos/especialidades.php', 'PATCH', data, function () {
                                    DsCrud.toast('Especialidad actualizada', 'success');
                                    DsCrud.closeModal();
                                    location.reload();
                                }, function (e) {
                                    DsCrud.toast(e || 'Error al actualizar', 'error');
                                });
                            }
                        });
                    }, function (e) {
                        DsCrud.toast(e || 'Error al cargar', 'error');
                    });
                },
                onDelete: function (id) {
                    DsCrud.confirm('¿Eliminar especialidad #' + id + '?', function () {
                        DsCrud.apiLegacy('api/catalogos/especialidades.php', 'DELETE', { id: id }, function () {
                            DsCrud.toast('Especialidad eliminada', 'success');
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e || 'Error al eliminar', 'error');
                        });
                    });
                }
            });
        })();
    </script>
<?php endif; ?>
</body>

</html>
