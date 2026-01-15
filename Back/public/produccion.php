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
        <h2>Produccion</h2>
    </div>

    <div class="card">
        <strong>Ordenes de produccion</strong>
        <div class="muted">Filtro por estado</div>
        <select id="orden-estado">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="en_proceso">En proceso</option>
            <option value="terminada">Terminada</option>
            <option value="cancelada">Cancelada</option>
            <option value="pausada">Pausada</option>
        </select>
        <button class="btn" onclick="loadOrdenes()">Actualizar</button>
        <table id="ordenes-table">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Producto</th>
                    <th>Artesano</th>
                    <th>Estado</th>
                    <th>Fecha inicio</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
async function loadOrdenes() {
    const estado = document.getElementById('orden-estado').value;
    const url = '../api/ordenes.php?limit=20&offset=0&estado=' + encodeURIComponent(estado);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#ordenes-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.codigo_orden}</td><td>${row.producto_nombre}</td><td>${row.artesano_nombre || ''}</td><td>${row.estado}</td><td>${row.fecha_inicio || ''}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrdenes();
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
