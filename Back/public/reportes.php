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
        <h1>Reportes</h1>
        <p>Indicadores operativos y financieros</p>
    </div>

    <div class="card">
        <strong>Rango de fechas</strong>
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="desde">Desde</label>
                <input type="date" id="desde" class="form-control form-control-sm ds-field">
            </div>
            <div>
                <label class="form-label muted" for="hasta">Hasta</label>
                <input type="date" id="hasta" class="form-control form-control-sm ds-field">
            </div>
            <button class="btn btn-sm" onclick="loadAllReports()">Actualizar</button>
        </div>
    </div>

    <div class="card" id="rep-produccion-section">
        <strong>Produccion por periodo</strong>
        <div class="table-responsive">
            <table id="rep-produccion" class="table table-sm">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Artesano</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-inventario-section">
        <strong>Inventario (stock bajo)</strong>
        <div class="table-responsive">
            <table id="rep-inventario" class="table table-sm">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Item</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Stock minimo</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-eficiencia-section">
        <strong>Eficiencia de artesanos</strong>
        <div class="table-responsive">
            <table id="rep-eficiencia" class="table table-sm">
            <thead>
                <tr>
                    <th>Artesano</th>
                    <th>Piezas</th>
                    <th>Horas</th>
                    <th>Promedio</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-materiales-section">
        <strong>Uso de materiales</strong>
        <div class="table-responsive">
            <table id="rep-materiales" class="table table-sm">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>ID material</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Costo</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-ventas-section">
        <strong>Ventas</strong>
        <div class="table-responsive">
            <table id="rep-ventas" class="table table-sm">
            <thead>
                <tr>
                    <th>Pieza</th>
                    <th>Producto</th>
                    <th>Fecha</th>
                    <th>Precio</th>
                    <th>Utilidad</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-compras-section">
        <strong>Compras (entradas)</strong>
        <div class="table-responsive">
            <table id="rep-compras" class="table table-sm">
            <thead>
                <tr>
                    <th>Tipo inventario</th>
                    <th>Cantidad total</th>
                    <th>Movimientos</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card" id="rep-usuarios-section">
        <strong>Usuarios</strong>
        <div class="table-responsive">
            <table id="rep-usuarios" class="table table-sm">
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
</div>

<script>
function getDateParams() {
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    const params = new URLSearchParams();
    if (desde) params.set('desde', desde);
    if (hasta) params.set('hasta', hasta);
    return params.toString();
}

function formatDateTime(value) {
    if (!value) return '';
    let text = String(value).replace('T', ' ').replace('Z', '');
    return text.replace(/\.\d+/, '');
}

function formatNumber(value) {
    if (value === null || value === undefined || value === '') return '';
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    const truncated = Math.trunc(num * 100) / 100;
    return truncated.toFixed(2);
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

async function loadReport(url, tableId, rowBuilder, columnCount, emptyMessage) {
    const params = getDateParams();
    const res = await fetch(url + (params ? '?' + params : ''));
    const data = await res.json();
    const tbody = document.querySelector(tableId + ' tbody');
    tbody.innerHTML = '';
    const rows = data.DATOS || [];
    if (!rows.length) {
        const tr = document.createElement('tr');
        const message = emptyMessage || 'Sin datos para el rango seleccionado';
        tr.innerHTML = `<td colspan="${columnCount}">${message}</td>`;
        tbody.appendChild(tr);
        return;
    }
    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = rowBuilder(row);
        tbody.appendChild(tr);
    });
}

function loadAllReports() {
    loadReport('../api/reportes_produccion.php', '#rep-produccion', row =>
        `<td>${row.codigo_orden}</td><td>${row.producto}</td><td>${row.cantidad}</td><td>${row.artesano || ''}</td><td>${formatStatus(row.estado)}</td>`,
        5
    );
    loadReport('../api/reportes_inventario.php', '#rep-inventario', row =>
        `<td>${row.tipo}</td><td>${row.item_id}</td><td>${row.nombre}</td><td>${formatNumber(row.cantidad)}</td><td>${formatNumber(row.stock_minimo)}</td><td>${row.proveedor || ''}</td>`,
        6
    );
    loadReport('../api/reportes_eficiencia.php', '#rep-eficiencia', row =>
        `<td>${row.artesano || ''}</td><td>${row.piezas}</td><td>${formatNumber(row.horas)}</td><td>${formatNumber(row.promedio_horas)}</td>`,
        4
    );
    loadReport('../api/reportes_materiales.php', '#rep-materiales', row =>
        `<td>${row.tipo_material}</td><td>${row.material_id}</td><td>${row.material_nombre || ''}</td><td>${formatNumber(row.cantidad_total)}</td><td>${formatNumber(row.costo_total)}</td>`,
        5
    );
    loadReport('../api/reportes_ventas.php', '#rep-ventas', row =>
        `<td>${row.codigo_pieza}</td><td>${row.producto_id}</td><td>${formatDateTime(row.fecha_venta)}</td><td>${formatNumber(row.precio_venta)}</td><td>${formatNumber(row.utilidad)}</td>`,
        5
    );
    loadReport('../api/reportes_compras.php', '#rep-compras', row =>
        `<td>${row.tipo_inventario}</td><td>${formatNumber(row.cantidad_total)}</td><td>${row.movimientos}</td>`,
        3
    );
    loadReport('../api/reportes_usuarios.php', '#rep-usuarios', row =>
        `<td>${row.id_usuario}</td><td>${row.username}</td><td>${row.nombre}</td><td>${row.rol}</td><td>${row.activo ? 'Si' : 'No'}</td>`,
        5
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const today = new Date();
    const first = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('desde').value = first.toISOString().slice(0, 10);
    document.getElementById('hasta').value = today.toISOString().slice(0, 10);
    loadAllReports();
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
