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
        <h2>Proveedores</h2>
    </div>

    <div class="card">
        <strong>Listado de proveedores</strong>
        <div class="muted">Filtro por tipo</div>
        <select id="prov-tipo">
            <option value="">Todos</option>
            <option value="oro">Oro</option>
            <option value="insumos">Insumos</option>
            <option value="maquinaria">Maquinaria</option>
        </select>
        <button class="btn" onclick="loadProveedores()">Actualizar</button>
        <table id="proveedores-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Telefono</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
async function loadProveedores() {
    const tipo = document.getElementById('prov-tipo').value;
    const url = '../api/proveedores.php?limit=20&offset=0&tipo=' + encodeURIComponent(tipo);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#proveedores-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.nombre}</td><td>${row.tipo}</td><td>${row.contacto || ''}</td><td>${row.telefono || ''}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadProveedores();
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
