<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

try {
    $stmt = $connLogic->prepare(
        'SELECT id, codigo_orden, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones FROM fun_obtener_ordenes(:offset, :limit, :estado)'
    );
    $stmt->execute([':offset' => 0, ':limit' => 5, ':estado' => null]);
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ordenes = [];
}
$ordenes_count = count($ordenes);

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Bienvenido a Joyas Van</h1>
        <p>Lujo en Cada Detalle</p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card gold">
            <div class="card-icon">&#x1F48E;</div>
            <h3>Inventario Actual</h3>
            <p class="stat">0 items</p>
            <div class="card-footer">
                <span>0%</span>
            </div>
        </div>

        <div class="dashboard-card silver">
            <div class="card-icon">&#x1F4B0;</div>
            <h3>Ventas del Mes</h3>
            <p class="stat">$0</p>
            <div class="card-footer">
                <span>0% mes anterior</span>
            </div>
        </div>

        <div class="dashboard-card bronze">
            <div class="card-icon">&#x1F4C8;</div>
            <h3>Ordenes Activas</h3>
            <p class="stat"><?php echo (int)$ordenes_count; ?></p>
            <div class="card-footer">
                <span>0 refabricado</span>
            </div>
        </div>

        <div class="dashboard-card pearl">
            <div class="card-icon">&#x2705;</div>
            <h3>Ordenes Completadas</h3>
            <p class="stat">0</p>
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
                                <td><?php echo htmlspecialchars($orden['estado'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($orden['fecha_creacion'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
