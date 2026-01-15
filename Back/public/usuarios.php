<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="header">
        <h2>Usuarios</h2>
    </div>

    <div class="card">
        <strong>Listado de usuarios</strong>
        <table id="usuarios-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Activo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

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
<?php include __DIR__ . '/partials/footer.php'; ?>
