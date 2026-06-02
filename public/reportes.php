<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: REPORTES GERENCIALES
 * ============================================================================
 *
 * Centro de reportes con múltiples vistas de datos consolidados.
 * Incluye gráficos interactivos (uPlot) en modo moderno.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Repositories/ReporteRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(4); // Menú: Reportes
$legacy = page_is_legacy();

// =============================================================================
// DATA LAYER
// =============================================================================

$repRepo = new ReporteRepository($connLogic);

$load_uplot = !$legacy;

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$input_type = $legacy ? 'text' : 'date';
$cache_bust = trim((string) ($_GET['cb'] ?? ''));
$chart_params = 'desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);
if ($cache_bust !== '') {
    $chart_params .= '&cb=' . urlencode($cache_bust);
}

$rep_produccion = [];
$rep_inventario = [];
$rep_eficiencia = [];
$rep_materiales = [];
$rep_ventas = [];
$rep_compras = [];
$rep_usuarios = [];

if ($legacy) {
    try {
        $rep_produccion = $repRepo->produccion($desde, $hasta);
    } catch (PDOException $e) {
        error_log('reportes legacy produccion error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_inventario = $repRepo->inventario();
    } catch (PDOException $e) {
        error_log('reportes legacy inventario error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_eficiencia = $repRepo->eficienciaArtesanos($desde, $hasta);
    } catch (PDOException $e) {
        error_log('reportes legacy eficiencia error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_materiales = $repRepo->usoMateriales($desde, $hasta);
    } catch (PDOException $e) {
        error_log('reportes legacy materiales error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_ventas = $repRepo->ventas($desde, $hasta);
    } catch (PDOException $e) {
        error_log('reportes legacy ventas error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_compras = $repRepo->compras($desde, $hasta);
    } catch (PDOException $e) {
        error_log('reportes legacy compras error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $rep_usuarios = $repRepo->usuarios();
    } catch (PDOException $e) {
        error_log('reportes legacy usuarios error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(4);
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
                <input type="<?php echo $input_type; ?>" id="desde" name="desde" class="form-control form-control-sm ds-field" value="<?php echo page_e($desde); ?>">
            </div>
            <div>
                <label class="form-label muted" for="hasta">Hasta</label>
                <input type="<?php echo $input_type; ?>" id="hasta" name="hasta" class="form-control form-control-sm ds-field" value="<?php echo page_e($hasta); ?>">
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
                            <td><?php echo page_e((string) ($row['id'] ?? '')); ?></td>
                            <td><?php echo page_e((string)$row['producto']); ?></td>
                            <td><?php echo page_e((string)$row['cantidad']); ?></td>
                            <td><?php echo page_e((string)($row['artesano'] ?? '')); ?></td>
                            <td><?php echo page_e($estado_label); ?></td>
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
                            <td><?php echo page_e((string)$row['tipo']); ?></td>
                            <td><?php echo page_e((string)$row['item_id']); ?></td>
                            <td><?php echo page_e((string)$row['nombre']); ?></td>
                            <td><?php echo page_e((string)$row['cantidad']); ?></td>
                            <td><?php echo page_e((string)$row['stock_minimo']); ?></td>
                            <td><?php echo page_e((string)($row['proveedor'] ?? '')); ?></td>
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
                            <td><?php echo page_e((string)($row['artesano'] ?? '')); ?></td>
                            <td><?php echo page_e((string)$row['piezas']); ?></td>
                            <td><?php echo page_e((string)$row['horas']); ?></td>
                            <td><?php echo page_e((string)$row['promedio_horas']); ?></td>
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
                            <td><?php echo page_e((string)$row['tipo_material']); ?></td>
                            <td><?php echo page_e((string)$row['material_id']); ?></td>
                            <td><?php echo page_e((string)($row['material_nombre'] ?? '')); ?></td>
                            <td><?php echo page_e((string)$row['cantidad_total']); ?></td>
                            <td><?php echo page_e((string)$row['costo_total']); ?></td>
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
                            <td><?php echo page_e((string) ($row['id'] ?? '')); ?></td>
                            <td><?php echo page_e((string)$row['producto_id']); ?></td>
                            <td><?php echo page_e($fecha); ?></td>
                            <td><?php echo page_e((string)$row['precio_venta']); ?></td>
                            <td><?php echo page_e((string)$row['utilidad']); ?></td>
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
                            <td><?php echo page_e((string)$row['tipo_inventario']); ?></td>
                            <td><?php echo page_e((string)$row['cantidad_total']); ?></td>
                            <td><?php echo page_e((string)$row['movimientos']); ?></td>
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
                            <td><?php echo page_e((string)$row['id_usuario']); ?></td>
                            <td><?php echo page_e((string)$row['username']); ?></td>
                            <td><?php echo page_e((string)$row['nombre']); ?></td>
                            <td><?php echo page_e((string)$row['rol']); ?></td>
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

<?php
page_render_end();
?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
