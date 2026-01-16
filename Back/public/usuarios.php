<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$usuarios_rows = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id_usuario, username, nombre, rol, activo FROM seguridad.fun_reporte_usuarios()'
        );
        $stmt->execute();
        $usuarios_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <?php foreach ($usuarios_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['id_usuario']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['username']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['rol']); ?></td>
                        <td><?php echo !empty($row['activo']) ? 'Si' : 'No'; ?></td>
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
async function loadUsuarios() {
    const res = await fetch('../api/reportes_usuarios.php');
    const data = await res.json();
    const tbody = document.querySelector('#usuarios-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id_usuario}</td><td>${row.username}</td><td>${row.nombre}</td><td>${row.rol}</td><td>${row.activo ? 'Si' : 'No'}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadUsuarios();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
