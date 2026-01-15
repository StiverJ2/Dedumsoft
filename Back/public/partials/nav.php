<?php
require_once __DIR__ . '/../../connection/guard.php';
?>
<nav>
    <strong>Dedumsoft</strong>
    <div class="muted"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></div>
    <hr>
    <a href="index.php">Dashboard</a>
    <a href="inventario.php">Inventario</a>
    <a href="produccion.php">Produccion</a>
    <a href="reportes.php">Reportes</a>
    <a href="usuarios.php">Usuarios</a>
    <a href="proveedores.php">Proveedores</a>
    <a href="configuracion.php">Configuracion</a>
    <form action="../auth/logout.php" method="post" style="margin-top:16px;">
        <button type="submit" class="btn">Salir</button>
    </form>
</nav>
