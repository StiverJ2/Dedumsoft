<?php
require_once __DIR__ . '/../../connection/guard.php';

if (!function_exists('dedumsoft_nav_active')) {
    function dedumsoft_nav_active(string $current, string $target): string
    {
        return $current === $target ? 'active' : '';
    }
}

$current = basename($_SERVER['PHP_SELF'] ?? '');
$inv_open = in_array($current, ['inventario.php', 'proveedores.php'], true);
$prod_open = in_array($current, ['produccion.php'], true);
$rep_open = in_array($current, ['reportes.php'], true);
$adm_open = in_array($current, ['usuarios.php'], true);
$cfg_open = in_array($current, ['configuracion.php'], true);

$nombre = trim($user['nombre'] ?? '');
$avatar = $nombre !== '' ? strtoupper(substr($nombre, 0, 1)) : '?';
$rolid = (int)($user['rolid'] ?? 0);
$rol_label = 'Operador';
if ($rolid === 1) {
    $rol_label = 'Administrador';
} elseif ($rolid === 3) {
    $rol_label = 'Lectura';
}
?>
<aside class="ds-sidebar">
    <div class="ds-logo">
        <div class="ds-logo-wrap">
            <div class="diamond-logo"></div>
            <h2>Joyas Van</h2>
        </div>
        <div class="gold-bar"></div>
    </div>

    <div class="ds-nav">
        <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'index.php'); ?>" href="index.php">
            <span class="ds-icon">&#x1F3E0;</span>
            <span class="ds-nav-label">Inicio</span>
        </a>

        <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-inventario" aria-expanded="<?php echo $inv_open ? 'true' : 'false'; ?>" aria-controls="ds-inventario">
            <span class="ds-icon">&#x1F48E;</span>
            <span class="ds-nav-label">Inventario</span>
            <span class="ds-arrow">&#9660;</span>
        </button>
        <div id="ds-inventario" class="collapse ds-submenu <?php echo $inv_open ? 'show' : ''; ?>">
            <a class="<?php echo dedumsoft_nav_active($current, 'inventario.php'); ?>" href="inventario.php">
                <span class="ds-subicon">&#x1F9EA;</span> Insumos
            </a>
            <a class="<?php echo dedumsoft_nav_active($current, 'inventario.php'); ?>" href="inventario.php">
                <span class="ds-subicon">&#x1F529;</span> Maquinaria
            </a>
            <a class="<?php echo dedumsoft_nav_active($current, 'inventario.php'); ?>" href="inventario.php">
                <span class="ds-subicon">&#x1F48D;</span> Oro
            </a>
            <a class="<?php echo dedumsoft_nav_active($current, 'proveedores.php'); ?>" href="proveedores.php">
                <span class="ds-subicon">&#x1F9FE;</span> Proveedores
            </a>
        </div>

        <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-produccion" aria-expanded="<?php echo $prod_open ? 'true' : 'false'; ?>" aria-controls="ds-produccion">
            <span class="ds-icon">&#x1F4E6;</span>
            <span class="ds-nav-label">Produccion</span>
            <span class="ds-arrow">&#9660;</span>
        </button>
        <div id="ds-produccion" class="collapse ds-submenu <?php echo $prod_open ? 'show' : ''; ?>">
            <a class="<?php echo dedumsoft_nav_active($current, 'produccion.php'); ?>" href="produccion.php">
                <span class="ds-subicon">&#x1F9F0;</span> Ordenes
            </a>
        </div>

        <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-reportes" aria-expanded="<?php echo $rep_open ? 'true' : 'false'; ?>" aria-controls="ds-reportes">
            <span class="ds-icon">&#x1F4C8;</span>
            <span class="ds-nav-label">Reportes</span>
            <span class="ds-arrow">&#9660;</span>
        </button>
        <div id="ds-reportes" class="collapse ds-submenu <?php echo $rep_open ? 'show' : ''; ?>">
            <a class="<?php echo dedumsoft_nav_active($current, 'reportes.php'); ?>" href="reportes.php">
                <span class="ds-subicon">&#x1F4CA;</span> Produccion
            </a>
            <a class="<?php echo dedumsoft_nav_active($current, 'reportes.php'); ?>" href="reportes.php">
                <span class="ds-subicon">&#x1F4B0;</span> Ventas
            </a>
            <a class="<?php echo dedumsoft_nav_active($current, 'reportes.php'); ?>" href="reportes.php">
                <span class="ds-subicon">&#x1F9FE;</span> Compras
            </a>
        </div>

        <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-admin" aria-expanded="<?php echo $adm_open ? 'true' : 'false'; ?>" aria-controls="ds-admin">
            <span class="ds-icon">&#x1F465;</span>
            <span class="ds-nav-label">Administracion</span>
            <span class="ds-arrow">&#9660;</span>
        </button>
        <div id="ds-admin" class="collapse ds-submenu <?php echo $adm_open ? 'show' : ''; ?>">
            <a class="<?php echo dedumsoft_nav_active($current, 'usuarios.php'); ?>" href="usuarios.php">
                <span class="ds-subicon">&#x1F464;</span> Usuarios
            </a>
        </div>

        <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-config" aria-expanded="<?php echo $cfg_open ? 'true' : 'false'; ?>" aria-controls="ds-config">
            <span class="ds-icon">&#x2699;</span>
            <span class="ds-nav-label">Ajustes</span>
            <span class="ds-arrow">&#9660;</span>
        </button>
        <div id="ds-config" class="collapse ds-submenu <?php echo $cfg_open ? 'show' : ''; ?>">
            <a class="<?php echo dedumsoft_nav_active($current, 'configuracion.php'); ?>" href="configuracion.php">
                <span class="ds-subicon">&#x1F6E0;</span> Configuracion
            </a>
        </div>
    </div>

    <div class="ds-user-panel">
        <div class="ds-user-avatar"><?php echo htmlspecialchars($avatar); ?></div>
        <div class="ds-user-info">
            <div class="ds-user-name"><?php echo htmlspecialchars($nombre !== '' ? $nombre : 'Admin'); ?></div>
            <div class="ds-user-role"><?php echo htmlspecialchars($rol_label); ?></div>
        </div>
    </div>

    <form class="ds-logout" action="../auth/logout.php" method="post">
        <button type="submit" class="btn btn-sm">Salir</button>
    </form>
</aside>
<main class="ds-main">
