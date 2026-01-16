<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Produccion</h1>
        <p>Seguimiento de ordenes y estado de taller</p>
    </div>

    <div class="card">
        <strong>Ordenes de produccion</strong>
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="orden-estado">Estado</label>
                <select id="orden-estado" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="terminada">Terminada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="pausada">Pausada</option>
                </select>
            </div>
            <button class="btn btn-sm" onclick="loadOrdenes()">Actualizar</button>
        </div>
        <div class="table-responsive">
            <table id="ordenes-table" class="table table-sm">
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
</div>

<script>
function formatDateTime(value) {
    if (!value) return '';
    let text = String(value).replace('T', ' ').replace('Z', '');
    return text.replace(/\.\d+/, '');
}

function formatStatus(value) {
    const raw = (value || '').toString();
    const label = raw.replace(/_/g, ' ').toUpperCase();
    const key = raw.toLowerCase();
    let cls = 'ds-badge--neutral';
    if (key === 'pendiente') cls = 'ds-badge--warning';
    else if (key === 'en_proceso') cls = 'ds-badge--info';
    else if (key === 'terminada') cls = 'ds-badge--success';
    else if (key === 'cancelada') cls = 'ds-badge--danger';
    else if (key === 'pausada') cls = 'ds-badge--muted';
    return `<span class="ds-badge ${cls}">${label}</span>`;
}

async function loadOrdenes() {
    const estado = document.getElementById('orden-estado').value;
    const url = '../api/ordenes.php?limit=20&offset=0&estado=' + encodeURIComponent(estado);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#ordenes-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.codigo_orden}</td><td>${row.producto_nombre}</td><td>${row.artesano_nombre || ''}</td><td>${formatStatus(row.estado)}</td><td>${formatDateTime(row.fecha_inicio)}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrdenes();
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
