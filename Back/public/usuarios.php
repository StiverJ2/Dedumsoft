<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$rol_filter = $_GET['rol'] ?? '';
$activo_filter = $_GET['activo'] ?? '';
$usuarios_rows = [];

function format_rol_badge($rol)
{
    $rol = trim((string) $rol);
    if ($rol === '')
        return '';
    $key = strtolower($rol);
    $label = ucwords($rol);
    $cls = 'ds-badge--neutral';
    if ($key === 'administrador')
        $cls = 'ds-badge--danger';
    elseif ($key === 'operador')
        $cls = 'ds-badge--info';
    elseif ($key === 'lectura')
        $cls = 'ds-badge--muted';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
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
            'SELECT id_usuario, username, nombre, rol, activo FROM seguridad.fun_reporte_usuarios()'
        );
        $stmt->execute();
        $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter in PHP for legacy mode
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

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
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
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php if (empty($usuarios_rows)): ?>
                            <tr>
                                <td colspan="5">Sin usuarios para los filtros seleccionados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios_rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['id_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['username']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                    <td><?php echo format_rol_badge($row['rol']); ?></td>
                                    <td><?php echo format_activo_badge($row['activo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
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
        $(() => {
            $.getJSON('../api/reportes_usuarios.php', (data) => {
                $('#usuarios-table').DataTable({
                    data: data.DATOS || [],
                    columns: [
                        { data: 'id_usuario' },
                        { data: 'username' },
                        { data: 'nombre' },
                        { data: 'rol', render: formatRol },
                        { data: 'activo', render: formatActivo }
                    ],
                    language: { url: 'assets/dataTables.es-ES.json' }
                });
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>if (window.DedumTableSort) DedumTableSort.init('usuarios-table');</script>
<?php endif; ?>
</body>

</html>