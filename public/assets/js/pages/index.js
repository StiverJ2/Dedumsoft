const ventasSeries = window._ventasSeries || [];
const ordenesEstado = window._ordenesEstado || [];

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
        container.innerHTML =
            `<div class="ds-chart-empty">${emptyMessage || 'Sin datos para el rango seleccionado'}</div>`;
        return;
    }
    const width = container.clientWidth || 640;
    const height = 220;
    const finalOpts = Object.assign({}, opts, {
        width,
        height
    });
    container.innerHTML = '';
    new uPlot(finalOpts, data, container);
};

const renderVentasMes = () => {
    const x = [];
    const y = [];
    (ventasSeries || []).forEach(row => {
        if (!row || row.length < 2) return;
        const ts = Number(row[0]);
        const total = Number(row[1]);
        if (!Number.isNaN(ts) && !Number.isNaN(total)) {
            x.push(ts);
            y.push(total);
        }
    });
    const opts = {
        scales: {
            x: {
                time: true
            }
        },
        axes: [{
            size: 50,
            gap: 5,
            values: (u, ticks) => ticks.map(t => formatShortDate(t)),
            grid: {
                show: true,
                stroke: '#eee',
                width: 1
            }
        },
        {
            size: 50,
            gap: 5,
            grid: {
                show: true,
                stroke: '#eee',
                width: 1
            }
        }],
        series: [{},
        {
            label: 'Ventas',
            stroke: '#d4af37',
            width: 2,
            fill: 'rgba(212, 175, 55, 0.15)',
            points: {
                show: true,
                size: 6,
                fill: '#d4af37'
            }
        }],
        padding: [10, 10, 0, 0]
    };
    renderChart('#chart-ventas-mes', opts, [x, y]);
};

const renderOrdenesEstado = () => {
    const labels = [];
    const values = [];
    (ordenesEstado || []).forEach(row => {
        const label = String(row.estado || '').trim();
        const total = Number(row.total || 0);
        if (!label) return;
        labels.push(label);
        values.push(Number.isNaN(total) ? 0 : total);
    });
    const count = labels.length;
    const opts = {
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
        axes: [{
            size: 40,
            gap: 5,
            splits: (u) => labels.map((_, i) => i),
            values: (u, splits) => splits.map(i => labels[i] || ''),
            ticks: {
                show: false
            },
            grid: {
                show: false
            }
        },
        {
            size: 50,
            gap: 5,
            grid: {
                show: true,
                stroke: '#eee',
                width: 1
            }
        }],
        series: [{},
        {
            label: 'Ordenes',
            stroke: '#b59d5d',
            fill: 'rgba(212, 175, 55, 0.6)',
            width: 0,
            points: {
                show: false
            }
        }],
        padding: [10, 20, 0, 20]
    };
    if (window.uPlot && uPlot.paths && uPlot.paths.bars) {
        opts.series[1].paths = uPlot.paths.bars({
            size: [0.65, 100]
        });
    }
    renderChart('#chart-ordenes-estado', opts, [labels.map((_, idx) => idx), values]);
};

document.addEventListener('DOMContentLoaded', () => {
    renderVentasMes();
    renderOrdenesEstado();
});
