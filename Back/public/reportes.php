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
        <h2>Reportes</h2>
    </div>

    <div class="card">
        <strong>Rango de fechas</strong>
        <div>
            <label>Desde <input type="date" id="desde"></label>
            <label>Hasta <input type="date" id="hasta"></label>
            <button class="btn" onclick="loadAllReports()">Actualizar</button>
        </div>
    </div>

    <div class="card">
        <strong>Produccion por periodo</strong>
        <table id="rep-produccion">
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

    <div class="card">
        <strong>Inventario (stock bajo)</strong>
        <table id="rep-inventario">
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

    <div class="card">
        <strong>Eficiencia de artesanos</strong>
        <table id="rep-eficiencia">
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

    <div class="card">
        <strong>Uso de materiales</strong>
        <table id="rep-materiales">
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

    <div class="card">
        <strong>Ventas</strong>
        <table id="rep-ventas">
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

    <div class="card">
        <strong>Compras (entradas)</strong>
        <table id="rep-compras">
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

    <div class="card">
        <strong>Usuarios</strong>
        <table id="rep-usuarios">
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
function getDateParams() {
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    const params = new URLSearchParams();
    if (desde) params.set('desde', desde);
    if (hasta) params.set('hasta', hasta);
    return params.toString();
}

async function loadReport(url, tableId, rowBuilder) {
    const params = getDateParams();
    const res = await fetch(url + (params ? '?' + params : ''));
    const data = await res.json();
    const tbody = document.querySelector(tableId + ' tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = rowBuilder(row);
        tbody.appendChild(tr);
    });
}

function loadAllReports() {
    loadReport('../api/reportes_produccion.php', '#rep-produccion', row =>
        `<td>${row.codigo_orden}</td><td>${row.producto}</td><td>${row.cantidad}</td><td>${row.artesano || ''}</td><td>${row.estado}</td>`
    );
    loadReport('../api/reportes_inventario.php', '#rep-inventario', row =>
        `<td>${row.tipo}</td><td>${row.item_id}</td><td>${row.nombre}</td><td>${row.cantidad}</td><td>${row.stock_minimo}</td><td>${row.proveedor || ''}</td>`
    );
    loadReport('../api/reportes_eficiencia.php', '#rep-eficiencia', row =>
        `<td>${row.artesano || ''}</td><td>${row.piezas}</td><td>${row.horas}</td><td>${row.promedio_horas}</td>`
    );
    loadReport('../api/reportes_materiales.php', '#rep-materiales', row =>
        `<td>${row.tipo_material}</td><td>${row.material_id}</td><td>${row.material_nombre || ''}</td><td>${row.cantidad_total}</td><td>${row.costo_total}</td>`
    );
    loadReport('../api/reportes_ventas.php', '#rep-ventas', row =>
        `<td>${row.codigo_pieza}</td><td>${row.producto_id}</td><td>${row.fecha_venta || ''}</td><td>${row.precio_venta}</td><td>${row.utilidad}</td>`
    );
    loadReport('../api/reportes_compras.php', '#rep-compras', row =>
        `<td>${row.tipo_inventario}</td><td>${row.cantidad_total}</td><td>${row.movimientos}</td>`
    );
    loadReport('../api/reportes_usuarios.php', '#rep-usuarios', row =>
        `<td>${row.id_usuario}</td><td>${row.username}</td><td>${row.nombre}</td><td>${row.rol}</td><td>${row.activo ? 'Si' : 'No'}</td>`
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
