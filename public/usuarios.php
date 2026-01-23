<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE USUARIOS
 * ============================================================================
 * 
 * Página de administración de usuarios del sistema.
 * Permite visualizar y gestionar usuarios y sus roles.
 * 
 * Características:
 * - Tabla de usuarios con filtros (rol, estado activo)
 * - Visualización de roles con badges de color
 * - Filtrado por rol (Administrador, Operador, Lectura)
 * - Filtrado por estado activo/inactivo
 * - Acciones para activar/desactivar usuarios
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 5 (Usuarios)
 * 
 * Parámetros GET (solo legacy):
 * - rol: Filtrar por nombre de rol
 * - activo: Filtrar por estado (1=activo, 0=inactivo)
 * 
 * APIs utilizadas:
 * - GET /api/reportes_usuarios.php - Listar usuarios
 * - PATCH /api/usuarios.php - Activar/desactivar usuarios
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
require_menu_access(5); // Menú: Usuarios

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();
$current_user = get_session_user();
$current_user_id = (int) ($current_user['id_usuario'] ?? 0);

// Filtros de búsqueda (solo usados en modo legacy)
$rol_filter = $_GET['rol'] ?? '';
$activo_filter = $_GET['activo'] ?? '';
$usuarios_rows = [];

/**
 * Genera un badge HTML con color según el rol.
 * Cada rol tiene un color asociado para fácil identificación.
 * 
 * @param string $rol Nombre del rol
 * @return string HTML del badge
 */
function format_rol_badge($rol)
{
    $rol = trim((string) $rol);
    if ($rol === '')
        return '';
    $key = strtolower($rol);
    $label = ucwords($rol);

    // Asignar color según nivel de acceso
    $cls = 'ds-badge--neutral';
    if ($key === 'administrador')
        $cls = 'ds-badge--danger';    // Rojo: acceso total
    elseif ($key === 'operador')
        $cls = 'ds-badge--info';      // Azul: acceso operativo
    elseif ($key === 'lectura')
        $cls = 'ds-badge--muted';     // Gris: solo lectura

    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

/**
 * Genera un badge de estado activo/inactivo.
 * 
 * @param bool $activo Estado de activación
 * @return string HTML del badge
 */
function format_activo_badge($activo)
{
    if ($activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
}

// Cargar datos para modo legacy
if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id_usuario, username, nombre, rol, activo FROM seguridad.fun_reporte_usuarios()'
        );
        $stmt->execute();
        $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filtrar en PHP para modo legacy (la función no acepta parámetros)
        foreach ($all_rows as $row) {
            if ($rol_filter !== '' && strtolower($row['rol']) !== strtolower($rol_filter)) {
                continue;
            }
            if ($activo_filter !== '') {
                $is_active = !empty($row['activo']);
                if ($activo_filter === '1' && !$is_active)
                    continue;
                if ($activo_filter === '0' && $is_active)
                    continue;
            }
            $usuarios_rows[] = $row;
        }
    } catch (PDOException $e) {
        error_log('usuarios legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Usuarios</h1>
        <p>Administracion de usuarios del sistema</p>
    </div>

    <div class="card">
        <strong>Listado de usuarios</strong>
        <?php if ($legacy): ?>
            <form method="get" action="usuarios.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="usuario-rol">Rol</label>
                    <select id="usuario-rol" name="rol" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="administrador" <?php echo $rol_filter === 'administrador' ? 'selected' : ''; ?>>
                            Administrador</option>
                        <option value="operador" <?php echo $rol_filter === 'operador' ? 'selected' : ''; ?>>Operador
                        </option>
                        <option value="lectura" <?php echo $rol_filter === 'lectura' ? 'selected' : ''; ?>>Lectura</option>
                    </select>
                </div>
                <div>
                    <label class="form-label muted" for="usuario-activo">Estado</label>
                    <select id="usuario-activo" name="activo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $activo_filter === '1' ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo $activo_filter === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="usuarios.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="usuarios-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php if (empty($usuarios_rows)): ?>
                            <tr>
                                <td colspan="6">Sin usuarios para los filtros seleccionados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios_rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['id_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['username']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                    <td><?php echo format_rol_badge($row['rol']); ?></td>
                                    <td><?php echo format_activo_badge($row['activo']); ?></td>
                                    <td class="ds-actions-col">
                                        <?php
                                        $is_active = !empty($row['activo']);
                                        $action_label = $is_active ? 'Desactivar' : 'Activar';
                                        $action_icon = $is_active ? 'cross.png' : 'arrow_refresh.png';
                                        $action_class = $is_active ? 'ds-action-btn--delete' : 'ds-action-btn--edit';
                                        $is_self = (int) $row['id_usuario'] === $current_user_id;
                                        ?>
                                        <div class="ds-actions-cell">
                                            <button type="button"
                                                class="ds-action-btn <?php echo $action_class; ?>"
                                                data-action="toggle"
                                                data-id="<?php echo htmlspecialchars((string) $row['id_usuario']); ?>"
                                                data-activo="<?php echo $is_active ? '1' : '0'; ?>"
                                                title="<?php echo $action_label; ?>"
                                                aria-label="<?php echo $action_label; ?>"
                                                <?php echo $is_self && $is_active ? 'disabled' : ''; ?>>
                                                <img src="assets/icons/fatcow/16/<?php echo $action_icon; ?>"
                                                    alt="<?php echo $action_label; ?>">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const formatRol = (rol) => {
            if (!rol) return '';
            const key = rol.toLowerCase();
            const label = rol.charAt(0).toUpperCase() + rol.slice(1);
            let cls = 'ds-badge--neutral';
            if (key === 'administrador') cls = 'ds-badge--danger';
            else if (key === 'operador') cls = 'ds-badge--info';
            else if (key === 'lectura') cls = 'ds-badge--muted';
            return `<span class="ds-badge ${cls}">${label}</span>`;
        };
        const formatActivo = (v) => {
            if (v) return '<span class="ds-badge ds-badge--success">Activo</span>';
            return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
        };
        const renderAcciones = (data, type, row) => {
            if (type !== 'display') return '';
            const isActive = !!row.activo;
            const label = isActive ? 'Desactivar' : 'Activar';
            const isSelf = Number(row.id_usuario) === Number(currentUserId);
            const btnClass = isActive ? 'ds-action-btn--delete' : 'ds-action-btn--edit';
            const disabledAttr = isSelf && isActive ? ' disabled aria-disabled="true"' : '';
            let icon = '';
            if (window.DEDUMSOFT_ICON_MODE === 'emoji') {
                icon = isActive ? '🚫' : '✅';
            } else {
                const img = isActive ? 'cross.png' : 'arrow_refresh.png';
                icon = `<img src="assets/icons/fatcow/16/${img}" alt="${label}">`;
            }
            return `<div class="ds-actions-cell">
                <button type="button" class="ds-action-btn ${btnClass}" data-action="toggle" data-id="${DsCrud.escapeHtml(row.id_usuario)}" data-activo="${isActive ? '1' : '0'}" title="${label}" aria-label="${label}"${disabledAttr}>${icon}</button>
            </div>`;
        };
        $(() => {
            const usuariosTable = $('#usuarios-table').DataTable({
                ajax: {
                    url: 'api/reportes_usuarios.php',
                    dataSrc: 'DATOS'
                },
                columns: [
                    { data: 'id_usuario' },
                    { data: 'username' },
                    { data: 'nombre' },
                    { data: 'rol', render: formatRol },
                    { data: 'activo', render: formatActivo },
                    { data: null, orderable: false, searchable: false, render: renderAcciones }
                ],
                language: { url: 'assets/dataTables.es-ES.json' }
            });

            const openToggleModal = (row) => {
                if (!row) return;
                const isActive = !!row.activo;
                if (Number(row.id_usuario) === Number(currentUserId) && isActive) {
                    DsCrud.toast('No puedes desactivar tu propio usuario.', 'error');
                    return;
                }
                const actionLabel = isActive ? 'Desactivar' : 'Activar';
                const username = row.username || '';
                DsCrud.openModal({
                    title: `${actionLabel} usuario`,
                    body: `<p>¿Desea ${actionLabel.toLowerCase()} al usuario <strong>${DsCrud.escapeHtml(username)}</strong>?</p>`,
                    saveText: actionLabel,
                    cancelText: 'Cancelar',
                    onSave: (modalEl, close) => {
                        DsCrud.api('api/usuarios.php', 'PATCH', {
                            id: row.id_usuario,
                            activo: !isActive
                        }, (success, resp) => {
                            if (success) {
                                DsCrud.toast(resp.MENSAJE || 'Usuario actualizado');
                                usuariosTable.ajax.reload(null, false);
                                close();
                            } else {
                                DsCrud.toast(resp.MENSAJE || 'Error al actualizar', 'error');
                            }
                        });
                    }
                });
            };

            $('#usuarios-table').on('click', '.ds-action-btn[data-action="toggle"]', function () {
                const row = usuariosTable.row($(this).closest('tr')).data();
                openToggleModal(row);
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('usuarios-table');

            var currentUserId = <?php echo $current_user_id; ?>;
            var table = DsCrud.getById('usuarios-table');
            if (!table) return;

            function getText(el) {
                if (!el) return '';
                return el.textContent !== undefined ? el.textContent : el.innerText;
            }

            function openToggleModal(id, username, isActive) {
                if (!id) return;
                var numericId = parseInt(id, 10);
                if (!isNaN(numericId) && numericId === currentUserId && isActive) {
                    DsCrud.toast('No puedes desactivar tu propio usuario.', 'error');
                    return;
                }
                var actionLabel = isActive ? 'Desactivar' : 'Activar';
                DsCrud.openModal({
                    title: actionLabel + ' usuario',
                    body: '<p>¿Desea ' + actionLabel.toLowerCase() + ' al usuario <strong>' +
                        DsCrud.escapeHtml(username || '') + '</strong>?</p>',
                    saveText: actionLabel,
                    cancelText: 'Cancelar',
                    onSave: function () {
                        DsCrud.apiLegacy('api/usuarios.php', 'PATCH', {
                            id: numericId,
                            activo: !isActive
                        }, function () {
                            DsCrud.toast('Usuario actualizado', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e || 'Error al actualizar', 'error');
                        });
                    }
                });
            }

            DsCrud.addEvent(table, 'click', function (e) {
                e = e || window.event;
                var target = e.target || e.srcElement;
                while (target && target !== table) {
                    if (target.tagName === 'BUTTON' && target.getAttribute('data-action') === 'toggle') {
                        var id = target.getAttribute('data-id');
                        var isActive = target.getAttribute('data-activo') === '1';
                        var tr = target;
                        while (tr && tr.tagName !== 'TR') {
                            tr = tr.parentNode;
                        }
                        var username = '';
                        if (tr) {
                            var cells = tr.getElementsByTagName('td');
                            if (cells.length > 1) {
                                username = getText(cells[1]).replace(/^\s+|\s+$/g, '');
                            }
                        }
                        openToggleModal(id, username, isActive);
                        break;
                    }
                    target = target.parentNode;
                }
            });
        })();
    </script>
<?php endif; ?>
</body>

</html>
