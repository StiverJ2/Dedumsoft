<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: DASHBOARD PRINCIPAL
 * ============================================================================
 * 
 * Página de inicio para usuarios administradores.
 * Muestra un resumen ejecutivo del estado del sistema.
 * 
 * Indicadores mostrados:
 * - Inventario: Total de items y cantidad en stock bajo
 * - Ventas: Total del mes actual
 * - Órdenes: Cantidad de órdenes activas
 * - Completadas: Órdenes terminadas este mes
 * 
 * Gráficos:
 * - Ventas del mes: Gráfico de líneas temporal
 * - Órdenes por estado: Gráfico de barras
 * 
 * Características:
 * - Consultas optimizadas con un solo acceso a BD
 * - Soporte dual: uPlot (moderno) o PNG via GD (legacy)
 * - Íconos adaptables: emoji (moderno) o PNG Fatcow (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 1 (Dashboard)
 * 
 * APIs internas:
 * - fun_obtener_ordenes(): Órdenes recientes
 * - fun_reporte_inventario(): Conteo de inventario
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

// Verificar autenticación y autorización
require_login('login.php');
require_menu_access(1); // Menú: Dashboard

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();
$load_uplot = !$legacy;  // uPlot solo para navegadores modernos

// =============================================================================
// CARGA DE DATOS PARA EL DASHBOARD
// =============================================================================

// Cargar órdenes recientes (límite: 5)
try {
    $stmt = $connLogic->prepare(
        'SELECT id, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones FROM fun_obtener_ordenes(:offset, :limit, :estado)'
    );
    $stmt->execute([':offset' => 0, ':limit' => 5, ':estado' => null]);
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ordenes = [];
}

// Inicializar contadores
$inventario_total = 0;
$inventario_stock_bajo = 0;
$ventas_mes_total = 0.0;
$ordenes_activas = 0;
$ordenes_completadas_mes = 0;
$ventas_chart = [];
$ordenes_estado = [];

// Rango del mes actual
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Cargar métricas del dashboard
try {
    // Inventario: suma de las tres tablas de inventario
    $inventario_total = (int) $connLogic
        ->query('SELECT (SELECT COUNT(1) FROM inventario_oro) + (SELECT COUNT(1) FROM inventario_insumos) + (SELECT COUNT(1) FROM inventario_maquinaria) AS total')
        ->fetchColumn();

    // Stock bajo: usa la función de reporte
    $inventario_stock_bajo = (int) $connLogic
        ->query('SELECT COUNT(1) FROM fun_reporte_inventario()')
        ->fetchColumn();

    // Ventas del mes: suma de creaciones vendidas
    $stmt = $connLogic->prepare(
        'SELECT COALESCE(SUM(ct.precio_venta_real), 0) FROM creaciones_terminadas ct WHERE ct.vendida = TRUE AND ct.fecha_venta::date BETWEEN :desde AND :hasta'
    );
    $stmt->execute([':desde' => $month_start, ':hasta' => $month_end]);
    $ventas_mes_total = (float) $stmt->fetchColumn();

    // Órdenes activas: estados distintos de terminada/cancelada
    $ordenes_activas = (int) $connLogic
        ->query("SELECT COUNT(1) FROM ordenes_produccion op INNER JOIN estados_orden eo ON op.estado_id = eo.id WHERE eo.nombre NOT IN ('terminada', 'cancelada')")
        ->fetchColumn();

    // Órdenes completadas este mes
    $stmt = $connLogic->prepare(
        "SELECT COUNT(1) FROM ordenes_produccion op INNER JOIN estados_orden eo ON op.estado_id = eo.id WHERE eo.nombre = 'terminada' AND op.fecha_fin_real IS NOT NULL AND op.fecha_fin_real::date BETWEEN :desde AND :hasta"
    );
    $stmt->execute([':desde' => $month_start, ':hasta' => $month_end]);
    $ordenes_completadas_mes = (int) $stmt->fetchColumn();

    // Datos para gráfico de ventas (serie temporal)
    $stmt = $connLogic->prepare(
        'SELECT fecha_venta::date AS dia, COALESCE(SUM(precio_venta_real), 0) AS total FROM creaciones_terminadas WHERE vendida = TRUE AND fecha_venta::date BETWEEN :desde AND :hasta GROUP BY dia ORDER BY dia'
    );
    $stmt->execute([':desde' => $month_start, ':hasta' => $month_end]);
    $ventas_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ventas_rows as $row) {
        $ventas_chart[] = [
            strtotime((string) $row['dia']),
            (float) $row['total']
        ];
    }

    // Datos para gráfico de órdenes por estado
    $ordenes_estado = $connLogic
        ->query('SELECT eo.nombre AS estado, COUNT(1) AS total FROM ordenes_produccion op LEFT JOIN estados_orden eo ON op.estado_id = eo.id GROUP BY eo.nombre ORDER BY eo.nombre')
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En caso de error, mantener valores en cero
    $inventario_total = 0;
    $inventario_stock_bajo = 0;
    $ventas_mes_total = 0.0;
    $ordenes_activas = 0;
    $ordenes_completadas_mes = 0;
    $ventas_chart = [];
    $ordenes_estado = [];
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';

/**
 * Formatea una fecha/hora para visualización.
 * 
 * @param string|null $value Fecha en formato ISO 8601
 * @return string Fecha formateada (Y-m-d H:i) o cadena vacía
 */
if (!function_exists('dedumsoft_format_datetime')) {
    function dedumsoft_format_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d H:i');
        } catch (Exception $e) {
            // Fallback: eliminar microsegundos
            return preg_replace('/\.\d+/', '', $value);
        }
    }
}

/**
 * Genera un badge HTML con color según el estado de la orden.
 * 
 * Colores:
 * - pendiente: Amarillo (warning)
 * - en_proceso: Azul (info)
 * - terminada: Verde (success)
 * - cancelada: Rojo (danger)
 * - pausada: Gris (muted)
 * 
 * @param string|null $value Estado de la orden
 * @return string HTML del badge
 */
if (!function_exists('dedumsoft_format_status_badge')) {
    function dedumsoft_format_status_badge(?string $value): string
    {
        $raw = strtolower(trim((string) $value));
        $label = strtoupper(str_replace('_', ' ', $raw));
        $class = 'ds-badge--neutral';
        if ($raw === 'pendiente') {
            $class = 'ds-badge--warning';
        } elseif ($raw === 'en_proceso') {
            $class = 'ds-badge--info';
        } elseif ($raw === 'terminada') {
            $class = 'ds-badge--success';
        } elseif ($raw === 'cancelada') {
            $class = 'ds-badge--danger';
        } elseif ($raw === 'pausada') {
            $class = 'ds-badge--muted';
        }
        return '<span class="ds-badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

// Definir íconos según modo (emoji vs PNG)
if ($legacy) {
    $icon_inventory = '<img src="assets/icons/fatcow/32/box.png" alt="Inventario">';
    $icon_sales = '<img src="assets/icons/fatcow/32/cash_stack.png" alt="Ventas">';
    $icon_orders = '<img src="assets/icons/fatcow/32/application_view_list.png" alt="Ordenes">';
    $icon_done = '<img src="assets/icons/fatcow/32/tick.png" alt="Completadas">';
} else {
    $icon_inventory = '&#128230;';  // 📦
    $icon_sales = '&#128176;';      // 💰
    $icon_orders = '&#128203;';     // 📋
    $icon_done = '&#9989;';         // ✅
}
?>
<div class="content">
    <div class="content-header">
        <h1>Bienvenido a Joyas Van</h1>
        <p>Lujo en Cada Detalle</p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card gold">
            <div class="card-icon"><?php echo $icon_inventory; ?></div>
            <h3>Inventario Actual</h3>
            <p class="stat"><?php echo number_format($inventario_total); ?> items</p>
            <div class="card-footer">
                <span><?php echo (int) $inventario_stock_bajo; ?> en stock bajo</span>
            </div>
        </div>

        <div class="dashboard-card silver">
            <div class="card-icon"><?php echo $icon_sales; ?></div>
            <h3>Ventas del Mes</h3>
            <p class="stat">$<?php echo number_format($ventas_mes_total, 2); ?></p>
            <div class="card-footer">
                <span><?php echo htmlspecialchars(date('m/Y', strtotime($month_start))); ?></span>
            </div>
        </div>

        <div class="dashboard-card bronze">
            <div class="card-icon"><?php echo $icon_orders; ?></div>
            <h3>Ordenes Activas</h3>
            <p class="stat"><?php echo (int) $ordenes_activas; ?></p>
            <div class="card-footer">
                <span><?php echo count($ordenes); ?> recientes</span>
            </div>
        </div>

        <div class="dashboard-card pearl last">
            <div class="card-icon"><?php echo $icon_done; ?></div>
            <h3>Ordenes Completadas</h3>
            <p class="stat"><?php echo (int) $ordenes_completadas_mes; ?></p>
            <div class="card-footer">
                <span>este mes</span>
            </div>
        </div>
    </div>

    <div class="dashboard-charts">
        <div class="chart-card">
            <strong>Ventas del mes</strong>
            <?php if (!$legacy): ?>
                <div class="ds-chart" id="chart-ventas-mes"></div>
            <?php else: ?>
                <img class="ds-chart-img"
                    src="legacy_chart.php?chart=ventas_mes&desde=<?php echo urlencode($month_start); ?>&hasta=<?php echo urlencode($month_end); ?>"
                    alt="Grafico ventas del mes">
                <div class="chart-summary">
                    Total: $<?php echo number_format($ventas_mes_total, 2); ?> |
                    Periodo: <?php echo date('d/m', strtotime($month_start)); ?> -
                    <?php echo date('d/m/Y', strtotime($month_end)); ?> |
                    Transacciones: <?php echo count($ventas_chart); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="chart-card">
            <strong>Ordenes por estado</strong>
            <?php if (!$legacy): ?>
                <div class="ds-chart" id="chart-ordenes-estado"></div>
            <?php else: ?>
                <img class="ds-chart-img" src="legacy_chart.php?chart=ordenes_estado" alt="Grafico ordenes por estado">
                <div class="chart-summary">
                    <?php
                    $estado_summary = [];
                    foreach ($ordenes_estado as $est) {
                        $estado_summary[] = ucfirst(str_replace('_', ' ', $est['estado'])) . ': ' . $est['total'];
                    }
                    echo implode(' | ', $estado_summary) ?: 'Sin datos';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="recent-section">
        <h2>Ordenes recientes</h2>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Producto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$ordenes): ?>
                        <tr>
                            <td colspan="4">Sin datos</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($orden['id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($orden['producto_nombre'] ?? ''); ?></td>
                                <td><?php echo dedumsoft_format_status_badge($orden['estado'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(dedumsoft_format_datetime($orden['fecha_creacion'] ?? '')); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (!$legacy): ?>
    <script>
        const ventasSeries = <?php echo json_encode($ventas_chart); ?>;
        const ordenesEstado = <?php echo json_encode($ordenes_estado); ?>;

        function formatShortDate(seconds) {
            const d = new Date(seconds * 1000);
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return month + '-' + day;
        }

        function renderChart(containerId, opts, data, emptyMessage) {
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
        }

        function renderVentasMes() {
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
                }
                ],
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
                }
                ],
                padding: [10, 10, 0, 0]
            };
            renderChart('#chart-ventas-mes', opts, [x, y]);
        }

        function renderOrdenesEstado() {
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
                }
                ],
                series: [{},
                {
                    label: 'Ordenes',
                    stroke: '#b59d5d',
                    fill: 'rgba(212, 175, 55, 0.6)',
                    width: 0,
                    points: {
                        show: false
                    }
                }
                ],
                padding: [10, 20, 0, 20]
            };
            if (window.uPlot && uPlot.paths && uPlot.paths.bars) {
                opts.series[1].paths = uPlot.paths.bars({
                    size: [0.65, 100]
                });
            }
            renderChart('#chart-ordenes-estado', opts, [labels.map((_, idx) => idx), values]);
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderVentasMes();
            renderOrdenesEstado();
        });
    </script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>