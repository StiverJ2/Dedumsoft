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
        <h1>Inventario</h1>
        <p>Control de metales, insumos y maquinaria</p>
    </div>

    <div class="card">
        <strong>Inventario de oro</strong>
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="oro-tipo">Tipo</label>
                <select id="oro-tipo" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="10k">10k</option>
                    <option value="14k">14k</option>
                    <option value="18k">18k</option>
                    <option value="22k">22k</option>
                    <option value="24k">24k</option>
                </select>
            </div>
            <button class="btn btn-sm" onclick="loadOro()">Actualizar</button>
        </div>
        <div class="table-responsive">
            <table id="oro-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Peso</th>
                    <th>Precio</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <strong>Insumos</strong>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label muted" for="insumo-categoria">Categoria</label>
                <input type="text" id="insumo-categoria" class="form-control form-control-sm ds-field" placeholder="categoria">
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo">
                <label class="form-check-label muted" for="insumo-stock-bajo">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" onclick="loadInsumos()">Actualizar</button>
        </div>
        <div class="table-responsive">
            <table id="insumos-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Cantidad</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <strong>Maquinaria</strong>
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="maq-estado">Estado</label>
                <select id="maq-estado" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="operativa">Operativa</option>
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="averiada">Averiada</option>
                    <option value="fuera_servicio">Fuera de servicio</option>
                </select>
            </div>
            <button class="btn btn-sm" onclick="loadMaquinaria()">Actualizar</button>
        </div>
        <div class="table-responsive">
            <table id="maq-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Ubicacion</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadOro() {
    const tipo = document.getElementById('oro-tipo').value;
    const url = '../api/inventario_oro.php?limit=20&offset=0&tipo=' + encodeURIComponent(tipo);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#oro-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.tipo_oro}</td><td>${row.peso_gramos}</td><td>${row.precio_gramo}</td><td>${row.proveedor_nombre || ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function loadInsumos() {
    const categoria = document.getElementById('insumo-categoria').value;
    const stockBajo = document.getElementById('insumo-stock-bajo').checked ? '1' : '0';
    const url = '../api/inventario_insumos.php?limit=20&offset=0&categoria=' + encodeURIComponent(categoria) + '&stock_bajo=' + stockBajo;
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#insumos-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.nombre}</td><td>${row.categoria}</td><td>${row.cantidad}</td><td>${row.proveedor_nombre || ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function loadMaquinaria() {
    const estado = document.getElementById('maq-estado').value;
    const url = '../api/inventario_maquinaria.php?limit=20&offset=0&estado=' + encodeURIComponent(estado);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#maq-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.nombre}</td><td>${row.tipo}</td><td>${row.estado}</td><td>${row.ubicacion || ''}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadOro();
    loadInsumos();
    loadMaquinaria();
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
