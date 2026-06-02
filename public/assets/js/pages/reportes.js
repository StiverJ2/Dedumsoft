/**
 * ============================================================================
 * REPORTES — JavaScript Moderno (uPlot + Axios)
 * ============================================================================
 */

const esc = (value) => {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(value === null || value === undefined ? '' : String(value)));
    return div.innerHTML;
};

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
    const label = esc(raw.replace(/_/g, ' ').toUpperCase());
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
    const produccionRows = await loadReport('api/reportes_produccion.php', '#rep-produccion', row =>
        `<td>${esc(row.id)}</td><td>${esc(row.producto)}</td><td>${esc(row.cantidad)}</td><td>${esc(row.artesano || '')}</td><td>${formatStatus(row.estado)}</td>`,
        5
    );
    const inventarioRows = await loadReport('api/reportes_inventario.php', '#rep-inventario', row =>
        `<td>${esc(row.tipo)}</td><td>${esc(row.item_id)}</td><td>${esc(row.nombre)}</td><td>${formatNumber(row.cantidad)}</td><td>${formatNumber(row.stock_minimo)}</td><td>${esc(row.proveedor || '')}</td>`,
        6
    );
    const eficienciaRows = await loadReport('api/reportes_eficiencia.php', '#rep-eficiencia', row =>
        `<td>${esc(row.artesano || '')}</td><td>${esc(row.piezas)}</td><td>${formatNumber(row.horas)}</td><td>${formatNumber(row.promedio_horas)}</td>`,
        4
    );
    const materialesRows = await loadReport('api/reportes_materiales.php', '#rep-materiales', row =>
        `<td>${esc(row.tipo_material)}</td><td>${esc(row.material_id)}</td><td>${esc(row.material_nombre || '')}</td><td>${formatNumber(row.cantidad_total)}</td><td>${formatNumber(row.costo_total)}</td>`,
        5
    );
    const ventasRows = await loadReport('api/reportes_ventas.php', '#rep-ventas', row =>
        `<td>${esc(row.id)}</td><td>${esc(row.producto_id)}</td><td>${esc(formatDateTime(row.fecha_venta))}</td><td>${formatNumber(row.precio_venta)}</td><td>${formatNumber(row.utilidad)}</td>`,
        5
    );
    const comprasRows = await loadReport('api/reportes_compras.php', '#rep-compras', row =>
        `<td>${esc(row.tipo_inventario)}</td><td>${formatNumber(row.cantidad_total)}</td><td>${esc(row.movimientos)}</td>`,
        3
    );
    const usuariosRows = await loadReport('api/reportes_usuarios.php', '#rep-usuarios', row =>
        `<td>${esc(row.id_usuario)}</td><td>${esc(row.username)}</td><td>${esc(row.nombre)}</td><td>${esc(row.rol)}</td><td>${row.activo ? 'Si' : 'No'}</td>`,
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
