<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$load_uplot = !$legacy;
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$input_type = $legacy ? 'text' : 'date';
$chart_params = 'desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);

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
            'SELECT id, producto, cantidad, artesano, estado FROM fun_reporte_produccion(:desde, :hasta)'
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
            'SELECT id, producto_id, fecha_venta, precio_venta, utilidad FROM fun_reporte_ventas(:desde, :hasta)'
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-produccion"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=produccion&<?php echo $chart_params; ?>" alt="Grafico produccion">
        <?php endif; ?>
        <div class="table-responsive">
            <table id="rep-produccion" class="table table-sm">
            <thead>
                <tr>
                    <th>Id</th>
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
                            <td><?php echo htmlspecialchars((string) ($row['id'] ?? '')); ?></td>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-inventario"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=inventario&<?php echo $chart_params; ?>" alt="Grafico inventario">
        <?php endif; ?>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-eficiencia"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=eficiencia&<?php echo $chart_params; ?>" alt="Grafico eficiencia">
        <?php endif; ?>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-materiales"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=materiales&<?php echo $chart_params; ?>" alt="Grafico materiales">
        <?php endif; ?>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-ventas"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=ventas&<?php echo $chart_params; ?>" alt="Grafico ventas">
        <?php endif; ?>
        <div class="table-responsive">
            <table id="rep-ventas" class="table table-sm">
            <thead>
                <tr>
                    <th>Id</th>
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
                            <td><?php echo htmlspecialchars((string) ($row['id'] ?? '')); ?></td>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-compras"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=compras&<?php echo $chart_params; ?>" alt="Grafico compras">
        <?php endif; ?>
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
        <?php if (!$legacy): ?>
            <div class="ds-chart" id="chart-usuarios"></div>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <img class="ds-chart-img" src="legacy_chart.php?chart=usuarios&<?php echo $chart_params; ?>" alt="Grafico usuarios">
        <?php endif; ?>
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
const getDateParams = () => {
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    const params = new URLSearchParams();
    if (desde) params.set('desde', desde);
    if (hasta) params.set('hasta', hasta);
    return params.toString();
};

const formatDateTime = (value) => {
    if (!value) return '';
    let text = String(value).replace('T', ' ').replace('Z', '');
    return text.replace(/\.\d+/, '');
};

const formatNumber = (value) => {
    if (value === null || value === undefined || value === '') return '';
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    const truncated = Math.trunc(num * 100) / 100;
    return truncated.toFixed(2);
};

const formatStatus = (value) => {
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
};

const fetchReport = async (url) => {
    const params = getDateParams();
    const response = await axios.get(url, { params: new URLSearchParams(params) });
    return response.data.DATOS || [];
};

const loadReport = async (url, tableId, rowBuilder, columnCount, emptyMessage) => {
    const rows = await fetchReport(url);
    const tbody = document.querySelector(tableId + ' tbody');
    tbody.innerHTML = '';
    if (!rows.length) {
        const tr = document.createElement('tr');
        const message = emptyMessage || 'Sin datos para el rango seleccionado';
        tr.innerHTML = `<td colspan="${columnCount}">${message}</td>`;
        tbody.appendChild(tr);
        return rows;
    }
    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = rowBuilder(row);
        tbody.appendChild(tr);
    });
    return rows;
};

const chartCache = {};

const formatShortDate = (seconds) => {
    const d = new Date(seconds * 1000);
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return month + '-' + day;
};

const renderChart = (containerId, opts, data, emptyMessage) => {
    const container = document.querySelector(containerId);
    if (!container) {
        return;
    }
    if (!window.uPlot) {
        container.innerHTML = '<div class="ds-chart-empty">Graficos no disponibles.</div>';
        return;
    }
    if (!data.length || !data[0].length) {
        container.innerHTML = `<div class="ds-chart-empty">${emptyMessage || 'Sin datos para el rango seleccionado'}</div>`;
        if (chartCache[containerId]) {
            if (typeof chartCache[containerId].destroy === 'function') {
                chartCache[containerId].destroy();
            }
            delete chartCache[containerId];
        }
        return;
    }
    const width = container.clientWidth || 640;
    const height = 220;
    const finalOpts = Object.assign({}, opts, { width, height });
    if (chartCache[containerId]) {
        chartCache[containerId].setData(data);
        if (typeof chartCache[containerId].setSize === 'function') {
            chartCache[containerId].setSize({ width, height });
        }
        return;
    }
    container.innerHTML = '';
    chartCache[containerId] = new uPlot(finalOpts, data, container);
};

const toDateSeconds = (value) => {
    if (!value) return null;
    const text = String(value).replace(' ', 'T').replace('Z', '');
    const parsed = Date.parse(text);
    if (Number.isNaN(parsed)) return null;
    return Math.floor(parsed / 1000);
};

const countByKey = (rows, key) => {
    const map = {};
    rows.forEach(row => {
        const raw = String(row[key] || '').trim();
        if (!raw) return;
        map[raw] = (map[raw] || 0) + 1;
    });
    const labels = Object.keys(map);
    const values = labels.map(label => map[label]);
    return { labels, values };
};

const sumByKey = (rows, key, valueKey) => {
    const map = {};
    rows.forEach(row => {
        const raw = String(row[key] || '').trim();
        if (!raw) return;
        const value = Number(row[valueKey] || 0);
        map[raw] = (map[raw] || 0) + (Number.isNaN(value) ? 0 : value);
    });
    const labels = Object.keys(map);
    const values = labels.map(label => map[label]);
    return { labels, values };
};

const buildTimeSeries = (rows, dateKey, valueKey) => {
    const map = {};
    rows.forEach(row => {
        const ts = toDateSeconds(row[dateKey]);
        if (!ts) return;
        const value = Number(row[valueKey] || 0);
        map[ts] = (map[ts] || 0) + (Number.isNaN(value) ? 0 : value);
    });
    const keys = Object.keys(map).map(k => Number(k)).sort((a, b) => a - b);
    const series = keys.map(k => map[k]);
    return { x: keys, y: series };
};

const buildBarOptions = (labels, seriesLabel) => {
    const count = labels.length;
    const barOpts = {
        scales: { 
            x: { 
                time: false,
                auto: false,
                range: (u, min, max) => [-0.5, count - 0.5]
            },
            y: {
                auto: true,
                range: (u, min, max) => [0, max * 1.1]
            }
        },
        axes: [
            {
                size: 40,
                gap: 5,
                splits: (u) => labels.map((_, i) => i),
                values: (u, splits) => splits.map(i => labels[i] || ''),
                ticks: { show: false },
                grid: { show: false }
            },
            {
                size: 50,
                gap: 5,
                grid: { show: true, stroke: '#eee', width: 1 }
            }
        ],
        series: [
            {},
            {
                label: seriesLabel,
                stroke: '#b59d5d',
                fill: 'rgba(212, 175, 55, 0.6)',
                width: 0,
                points: { show: false }
            }
        ],
        padding: [10, 20, 0, 20]
    };
    if (window.uPlot && uPlot.paths && uPlot.paths.bars) {
        barOpts.series[1].paths = uPlot.paths.bars({ size: [0.65, 100] });
    }
    return barOpts;
};

const buildLineOptions = (seriesLabel) => {
    return {
        scales: { x: { time: true } },
        axes: [
            {
                size: 50,
                gap: 5,
                values: (u, ticks) => ticks.map(t => formatShortDate(t)),
                grid: { show: true, stroke: '#eee', width: 1 }
            },
            {
                size: 50,
                gap: 5,
                grid: { show: true, stroke: '#eee', width: 1 }
            }
        ],
        series: [
            {},
            {
                label: seriesLabel,
                stroke: '#d4af37',
                width: 2,
                fill: 'rgba(212, 175, 55, 0.15)',
                points: { show: true, size: 6, fill: '#d4af37' }
            }
        ],
        padding: [10, 10, 0, 0]
    };
};

const loadAllReports = async () => {
    const produccionRows = await loadReport('../api/reportes_produccion.php', '#rep-produccion', row =>
        `<td>${row.id}</td><td>${row.producto}</td><td>${row.cantidad}</td><td>${row.artesano || ''}</td><td>${formatStatus(row.estado)}</td>`,
        5
    );
    const inventarioRows = await loadReport('../api/reportes_inventario.php', '#rep-inventario', row =>
        `<td>${row.tipo}</td><td>${row.item_id}</td><td>${row.nombre}</td><td>${formatNumber(row.cantidad)}</td><td>${formatNumber(row.stock_minimo)}</td><td>${row.proveedor || ''}</td>`,
        6
    );
    const eficienciaRows = await loadReport('../api/reportes_eficiencia.php', '#rep-eficiencia', row =>
        `<td>${row.artesano || ''}</td><td>${row.piezas}</td><td>${formatNumber(row.horas)}</td><td>${formatNumber(row.promedio_horas)}</td>`,
        4
    );
    const materialesRows = await loadReport('../api/reportes_materiales.php', '#rep-materiales', row =>
        `<td>${row.tipo_material}</td><td>${row.material_id}</td><td>${row.material_nombre || ''}</td><td>${formatNumber(row.cantidad_total)}</td><td>${formatNumber(row.costo_total)}</td>`,
        5
    );
    const ventasRows = await loadReport('../api/reportes_ventas.php', '#rep-ventas', row =>
        `<td>${row.id}</td><td>${row.producto_id}</td><td>${formatDateTime(row.fecha_venta)}</td><td>${formatNumber(row.precio_venta)}</td><td>${formatNumber(row.utilidad)}</td>`,
        5
    );
    const comprasRows = await loadReport('../api/reportes_compras.php', '#rep-compras', row =>
        `<td>${row.tipo_inventario}</td><td>${formatNumber(row.cantidad_total)}</td><td>${row.movimientos}</td>`,
        3
    );
    const usuariosRows = await loadReport('../api/reportes_usuarios.php', '#rep-usuarios', row =>
        `<td>${row.id_usuario}</td><td>${row.username}</td><td>${row.nombre}</td><td>${row.rol}</td><td>${row.activo ? 'Si' : 'No'}</td>`,
        5
    );

    const prodCounts = countByKey(produccionRows || [], 'estado');
    renderChart('#chart-produccion', buildBarOptions(prodCounts.labels, 'Ordenes'), [prodCounts.labels.map((_, idx) => idx), prodCounts.values]);

    const invCounts = countByKey(inventarioRows || [], 'tipo');
    renderChart('#chart-inventario', buildBarOptions(invCounts.labels, 'Items'), [invCounts.labels.map((_, idx) => idx), invCounts.values]);

    const effCounts = sumByKey(eficienciaRows || [], 'artesano', 'piezas');
    renderChart('#chart-eficiencia', buildBarOptions(effCounts.labels, 'Piezas'), [effCounts.labels.map((_, idx) => idx), effCounts.values]);

    const matTotals = sumByKey(materialesRows || [], 'tipo_material', 'costo_total');
    renderChart('#chart-materiales', buildBarOptions(matTotals.labels, 'Costo'), [matTotals.labels.map((_, idx) => idx), matTotals.values]);

    const ventasSerie = buildTimeSeries(ventasRows || [], 'fecha_venta', 'precio_venta');
    renderChart('#chart-ventas', buildLineOptions('Ventas'), [ventasSerie.x, ventasSerie.y]);

    const comprasTotals = sumByKey(comprasRows || [], 'tipo_inventario', 'cantidad_total');
    renderChart('#chart-compras', buildBarOptions(comprasTotals.labels, 'Compras'), [comprasTotals.labels.map((_, idx) => idx), comprasTotals.values]);

    const usuariosCounts = countByKey(usuariosRows || [], 'rol');
    renderChart('#chart-usuarios', buildBarOptions(usuariosCounts.labels, 'Usuarios'), [usuariosCounts.labels.map((_, idx) => idx), usuariosCounts.values]);
};

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
</body>
</html>
