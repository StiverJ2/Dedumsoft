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

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="header">
        <h2>Dashboard</h2>
    </div>
    <div class="card">
        <strong>Ordenes recientes</strong>
        <table>
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
<?php include __DIR__ . '/partials/footer.php'; ?>
