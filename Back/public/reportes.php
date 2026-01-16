<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$input_type = $legacy ? 'text' : 'date';

$rep_produccion = [];
$rep_inventario = [];
$rep_eficiencia = [];
$rep_materiales = [];
$rep_ventas = [];
$rep_compras = [];
$rep_usuarios = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT codigo_orden, producto, cantidad, artesano, estado FROM fun_reporte_produccion(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $rep_produccion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy produccion error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT tipo, item_id, nombre, cantidad, stock_minimo, proveedor FROM fun_reporte_inventario()'
        );
        $stmt->execute();
        $rep_inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy inventario error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT artesano, piezas, horas, promedio_horas FROM fun_reporte_eficiencia_artesanos(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $rep_eficiencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy eficiencia error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT tipo_material, material_id, material_nombre, cantidad_total, costo_total FROM fun_reporte_uso_materiales(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $rep_materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy materiales error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT codigo_pieza, producto_id, fecha_venta, precio_venta, utilidad FROM fun_reporte_ventas(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $rep_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy ventas error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT tipo_inventario, cantidad_total, movimientos FROM fun_reporte_compras(:desde, :hasta)'
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $rep_compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy compras error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id_usuario, username, nombre, rol, activo FROM seguridad.fun_reporte_usuarios()'
        );
        $stmt->execute();
        $rep_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('reportes legacy usuarios error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

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
        <?php if ($legacy): ?>
        <form method="get" action="reportes.php#rep-produccion-section" class="d-flex flex-wrap gap-2 align-items-end">
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2 align-items-end">
        <?php endif; ?>
            <div>
                <label class="form-label muted" for="desde">Desde</label>
                <input type="<?php echo $input_type; ?>" id="desde" name="desde" class="form-control form-control-sm ds-field" value="<?php echo htmlspecialchars($desde); ?>">
            </div>
            <div>
                <label class="form-label muted" for="hasta">Hasta</label>
                <input type="<?php echo $input_type; ?>" id="hasta" name="hasta" class="form-control form-control-sm ds-field" value="<?php echo htmlspecialchars($hasta); ?>">
            </div>
            <?php if ($legacy): ?>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <?php else: ?>
            <button class="btn btn-sm" type="button" onclick="loadAllReports()">Actualizar</button>
            <?php endif; ?>
        <?php if ($legacy): ?>
        </form>
        <?php else: ?>
        </div>
        <?php endif; ?>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_produccion)): ?>
                    <tr><td colspan="5">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_produccion as $row): ?>
                        <?php $estado_label = strtoupper(str_replace('_', ' ', (string)($row['estado'] ?? ''))); ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['codigo_orden']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['producto']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['artesano'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($estado_label); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_inventario)): ?>
                    <tr><td colspan="6">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_inventario as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['tipo']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['item_id']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['stock_minimo']); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['proveedor'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_eficiencia)): ?>
                    <tr><td colspan="4">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_eficiencia as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($row['artesano'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['piezas']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['horas']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['promedio_horas']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_materiales)): ?>
                    <tr><td colspan="5">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_materiales as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['tipo_material']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['material_id']); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['material_nombre'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['cantidad_total']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['costo_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_ventas)): ?>
                    <tr><td colspan="5">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_ventas as $row): ?>
                        <?php $fecha = $row['fecha_venta'] ? date('Y-m-d', strtotime((string)$row['fecha_venta'])) : ''; ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['codigo_pieza']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['producto_id']); ?></td>
                            <td><?php echo htmlspecialchars($fecha); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['precio_venta']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['utilidad']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_compras)): ?>
                    <tr><td colspan="3">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_compras as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['tipo_inventario']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['cantidad_total']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['movimientos']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
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
            <tbody>
            <?php if ($legacy): ?>
                <?php if (empty($rep_usuarios)): ?>
                    <tr><td colspan="5">Sin datos para el rango seleccionado</td></tr>
                <?php else: ?>
                    <?php foreach ($rep_usuarios as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['id_usuario']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['username']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['rol']); ?></td>
                            <td><?php echo !empty($row['activo']) ? 'Si' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!$legacy): ?>
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
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
