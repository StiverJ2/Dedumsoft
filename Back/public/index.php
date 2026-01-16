<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();

try {
    $stmt = $connLogic->prepare(
        'SELECT id, codigo_orden, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones FROM fun_obtener_ordenes(:offset, :limit, :estado)'
    );
    $stmt->execute([':offset' => 0, ':limit' => 5, ':estado' => null]);
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ordenes = [];
}
$inventario_total = 0;
$inventario_stock_bajo = 0;
$ventas_mes_total = 0.0;
$ordenes_activas = 0;
$ordenes_completadas_mes = 0;
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

try {
    $inventario_total = (int)$connLogic
        ->query('SELECT (SELECT COUNT(*) FROM inventario_oro) + (SELECT COUNT(*) FROM inventario_insumos) + (SELECT COUNT(*) FROM inventario_maquinaria) AS total')
        ->fetchColumn();

    $inventario_stock_bajo = (int)$connLogic
        ->query('SELECT COUNT(*) FROM fun_reporte_inventario()')
        ->fetchColumn();

    $stmt = $connLogic->prepare(
        'SELECT COALESCE(SUM(ct.precio_venta_real), 0) FROM creaciones_terminadas ct WHERE ct.vendida = TRUE AND ct.fecha_venta::date BETWEEN :desde AND :hasta'
    );
    $stmt->execute([':desde' => $month_start, ':hasta' => $month_end]);
    $ventas_mes_total = (float)$stmt->fetchColumn();

    $ordenes_activas = (int)$connLogic
        ->query("SELECT COUNT(*) FROM ordenes_produccion WHERE estado NOT IN ('terminada', 'cancelada')")
        ->fetchColumn();

    $stmt = $connLogic->prepare(
        "SELECT COUNT(*) FROM ordenes_produccion WHERE estado = 'terminada' AND fecha_fin_real IS NOT NULL AND fecha_fin_real::date BETWEEN :desde AND :hasta"
    );
    $stmt->execute([':desde' => $month_start, ':hasta' => $month_end]);
    $ordenes_completadas_mes = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $inventario_total = 0;
    $inventario_stock_bajo = 0;
    $ventas_mes_total = 0.0;
    $ordenes_activas = 0;
    $ordenes_completadas_mes = 0;
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';

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
            return preg_replace('/\.\d+/', '', $value);
        }
    }
}

if (!function_exists('dedumsoft_format_status_badge')) {
    function dedumsoft_format_status_badge(?string $value): string
    {
        $raw = strtolower(trim((string)$value));
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
if ($legacy) {
    $icon_inventory = '<img src="assets/icons/fatcow/32/box.png" alt="Inventario">';
    $icon_sales = '<img src="assets/icons/fatcow/32/cash_stack.png" alt="Ventas">';
    $icon_orders = '<img src="assets/icons/fatcow/32/application_view_list.png" alt="Ordenes">';
    $icon_done = '<img src="assets/icons/fatcow/32/tick.png" alt="Completadas">';
} else {
    $icon_inventory = '&#128230;';
    $icon_sales = '&#128176;';
    $icon_orders = '&#128203;';
    $icon_done = '&#9989;';
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
                <span><?php echo (int)$inventario_stock_bajo; ?> en stock bajo</span>
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
            <p class="stat"><?php echo (int)$ordenes_activas; ?></p>
            <div class="card-footer">
                <span><?php echo count($ordenes); ?> recientes</span>
            </div>
        </div>

        <div class="dashboard-card pearl">
            <div class="card-icon"><?php echo $icon_done; ?></div>
            <h3>Ordenes Completadas</h3>
            <p class="stat"><?php echo (int)$ordenes_completadas_mes; ?></p>
            <div class="card-footer">
                <span>este mes</span>
            </div>
        </div>
    </div>

    <div class="recent-section">
        <h2>Ordenes recientes</h2>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Producto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$ordenes): ?>
                        <tr><td colspan="4">Sin datos</td></tr>
                    <?php else: ?>
                        <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($orden['codigo_orden'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($orden['producto_nombre'] ?? ''); ?></td>
                                <td><?php echo dedumsoft_format_status_badge($orden['estado'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(dedumsoft_format_datetime($orden['fecha_creacion'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
