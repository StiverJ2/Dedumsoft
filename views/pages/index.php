<?php
/**
 * View: Dashboard principal
 *
 * @var bool  $legacy
 * @var array $ordenes
 * @var int   $inventario_total
 * @var int   $inventario_stock_bajo
 * @var float $ventas_mes_total
 * @var int   $ordenes_activas
 * @var int   $ordenes_completadas_mes
 * @var array $ventas_chart
 * @var array $ordenes_estado
 * @var string $month_start
 * @var string $month_end
 * @var string $chart_cb
 * @var string $chart_cb_q
 * @var string $icon_inventory
 * @var string $icon_sales
 * @var string $icon_orders
 * @var string $icon_done
 */
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
                <span><?php echo v_e(date('m/Y', strtotime($month_start))); ?></span>
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
                    src="legacy_chart.php?chart=ventas_mes&desde=<?php echo urlencode($month_start); ?>&hasta=<?php echo urlencode($month_end); ?><?php echo $chart_cb; ?>"
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
                <img class="ds-chart-img" src="legacy_chart.php?chart=ordenes_estado<?php echo $chart_cb_q; ?>" alt="Grafico ordenes por estado">
                <div class="chart-summary">
                    <?php
                    $estado_summary = [];
                    foreach ($ordenes_estado as $est) {
                        $estado_label = v_e(ucfirst(str_replace('_', ' ', $est['estado'])));
                        $estado_total = (int) $est['total'];
                        $estado_summary[] = $estado_label . ': ' . $estado_total;
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
                                <td><?php echo v_e((string) ($orden['id'] ?? '')); ?></td>
                                <td><?php echo v_e($orden['producto_nombre'] ?? ''); ?></td>
                                <td><?php echo v_status_badge($orden['estado'] ?? ''); ?></td>
                                <td><?php echo v_e(v_format_date($orden['fecha_creacion'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
