<?php
require_once __DIR__ . '/../../connection/guard.php';

if (!function_exists('dedumsoft_nav_active')) {
    function dedumsoft_nav_active(string $current, string $target): string
    {
        return $current === $target ? 'active' : '';
    }
}

if (!function_exists('dedumsoft_nav_active_section')) {
    function dedumsoft_nav_active_section(string $current, string $target, string $section, string $default = ''): string
    {
        if ($current !== $target) {
            return '';
        }
        $current_section = $_GET['section'] ?? '';
        if ($current_section === '' && $default !== '') {
            return $default === $section ? 'active' : '';
        }
        return $current_section === $section ? 'active' : '';
    }
}

$current = basename($_SERVER['PHP_SELF'] ?? '');
$legacy = dedumsoft_is_legacy_browser();
$legacy_ua = dedumsoft_is_legacy_ua();
$inv_open = in_array($current, ['inventario.php', 'inventario_insumos.php', 'inventario_maquinaria.php', 'inventario_oro.php', 'proveedores.php', 'ubicaciones.php'], true);
$prod_open = in_array($current, ['produccion.php'], true);
$rep_open = in_array($current, ['reportes.php'], true);
$adm_open = in_array($current, ['usuarios.php'], true);
$cfg_open = in_array($current, ['configuracion.php'], true);

$nombre = trim($user['nombre'] ?? '');
$avatar = $nombre !== '' ? strtoupper(substr($nombre, 0, 1)) : '?';
$rolid = (int) ($user['rolid'] ?? 0);
$rol_label = 'Operador';
if ($rolid === 1) {
    $rol_label = 'Administrador';
} elseif ($rolid === 3) {
    $rol_label = 'Lectura';
}
?>
<?php if ($legacy): ?>
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
                <img class="ds-icon-img" src="assets/icons/fatcow/16/application_home.png" alt="">
                Inicio
            </a>

            <div class="ds-nav-section">Inventario</div>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'inventario_insumos.php'); ?>"
                href="inventario_insumos.php#inv-insumos">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/box_front.png" alt="">
                Insumos
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'inventario_maquinaria.php'); ?>"
                href="inventario_maquinaria.php#inv-maquinaria">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/toolbox.png" alt="">
                Maquinaria
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'inventario_oro.php'); ?>"
                href="inventario_oro.php#inv-oro">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/diamond.png" alt="">
                Oro
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'proveedores.php'); ?>" href="proveedores.php">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/lorry.png" alt="">
                Proveedores
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'ubicaciones.php'); ?>" href="ubicaciones.php">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/building.png" alt="">
                Ubicaciones
            </a>

            <div class="ds-nav-section">Produccion</div>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'produccion.php'); ?>" href="produccion.php">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/application_view_list.png" alt="">
                Ordenes
            </a>

            <div class="ds-nav-section">Reportes</div>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'produccion', 'produccion'); ?>"
                href="reportes.php?section=produccion#rep-produccion-section">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/chart_line.png" alt="">
                Produccion
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'ventas', 'produccion'); ?>"
                href="reportes.php?section=ventas#rep-ventas-section">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/card_money.png" alt="">
                Ventas
            </a>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'compras', 'produccion'); ?>"
                href="reportes.php?section=compras#rep-compras-section">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/cart.png" alt="">
                Compras
            </a>

            <div class="ds-nav-section">Administracion</div>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'usuarios.php'); ?>" href="usuarios.php">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/user.png" alt="">
                Usuarios
            </a>

            <div class="ds-nav-section">Ajustes</div>
            <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'configuracion.php'); ?>"
                href="configuracion.php">
                <img class="ds-icon-img" src="assets/icons/fatcow/16/cog.png" alt="">
                Configuracion
            </a>
        </div>

        <div class="ds-user-panel">
            <div class="ds-user-avatar"><?php echo htmlspecialchars($avatar); ?></div>
            <div class="ds-user-info">
                <div class="ds-user-name"><?php echo htmlspecialchars($nombre !== '' ? $nombre : 'Admin'); ?></div>
                <div class="ds-user-role"><?php echo htmlspecialchars($rol_label); ?></div>
            </div>
        </div>

        <form class="ds-logout" action="../auth/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(dedumsoft_csrf_token()); ?>">
            <button type="submit" class="btn btn-sm">Salir</button>
        </form>
    </aside>
    <main class="ds-main">
    <?php else: ?>
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
                    <span class="ds-icon">&#127968;</span>
                    <span class="ds-nav-label">Inicio</span>
                </a>

                <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-inventario"
                    aria-expanded="<?php echo $inv_open ? 'true' : 'false'; ?>" aria-controls="ds-inventario">
                    <span class="ds-icon">&#128230;</span>
                    <span class="ds-nav-label">Inventario</span>
                    <span class="ds-arrow">&#9660;</span>
                </button>
                <div id="ds-inventario" class="collapse ds-submenu <?php echo $inv_open ? 'show' : ''; ?>">
                    <a class="<?php echo dedumsoft_nav_active($current, 'inventario_insumos.php'); ?>"
                        href="inventario_insumos.php#inv-insumos">
                        <span class="ds-subicon">&#128230;</span> Insumos
                    </a>
                    <a class="<?php echo dedumsoft_nav_active($current, 'inventario_maquinaria.php'); ?>"
                        href="inventario_maquinaria.php#inv-maquinaria">
                        <span class="ds-subicon">&#128295;</span> Maquinaria
                    </a>
                    <a class="<?php echo dedumsoft_nav_active($current, 'inventario_oro.php'); ?>"
                        href="inventario_oro.php#inv-oro">
                        <span class="ds-subicon">&#128142;</span> Oro
                    </a>
                    <a class="<?php echo dedumsoft_nav_active($current, 'proveedores.php'); ?>" href="proveedores.php">
                        <span class="ds-subicon">&#128666;</span> Proveedores
                    </a>
                    <a class="<?php echo dedumsoft_nav_active($current, 'ubicaciones.php'); ?>" href="ubicaciones.php">
                        <span class="ds-subicon">&#128205;</span> Ubicaciones
                    </a>
                </div>

                <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-produccion"
                    aria-expanded="<?php echo $prod_open ? 'true' : 'false'; ?>" aria-controls="ds-produccion">
                    <span class="ds-icon">&#127981;</span>
                    <span class="ds-nav-label">Produccion</span>
                    <span class="ds-arrow">&#9660;</span>
                </button>
                <div id="ds-produccion" class="collapse ds-submenu <?php echo $prod_open ? 'show' : ''; ?>">
                    <a class="<?php echo dedumsoft_nav_active($current, 'produccion.php'); ?>" href="produccion.php">
                        <span class="ds-subicon">&#128203;</span> Ordenes
                    </a>
                </div>

                <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-reportes"
                    aria-expanded="<?php echo $rep_open ? 'true' : 'false'; ?>" aria-controls="ds-reportes">
                    <span class="ds-icon">&#128202;</span>
                    <span class="ds-nav-label">Reportes</span>
                    <span class="ds-arrow">&#9660;</span>
                </button>
                <div id="ds-reportes" class="collapse ds-submenu <?php echo $rep_open ? 'show' : ''; ?>">
                    <a class="<?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'produccion', 'produccion'); ?>"
                        href="reportes.php?section=produccion#rep-produccion-section">
                        <span class="ds-subicon">&#128200;</span> Produccion
                    </a>
                    <a class="<?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'ventas', 'produccion'); ?>"
                        href="reportes.php?section=ventas#rep-ventas-section">
                        <span class="ds-subicon">&#128176;</span> Ventas
                    </a>
                    <a class="<?php echo dedumsoft_nav_active_section($current, 'reportes.php', 'compras', 'produccion'); ?>"
                        href="reportes.php?section=compras#rep-compras-section">
                        <span class="ds-subicon">&#128722;</span> Compras
                    </a>
                </div>

                <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-admin"
                    aria-expanded="<?php echo $adm_open ? 'true' : 'false'; ?>" aria-controls="ds-admin">
                    <span class="ds-icon">&#128101;</span>
                    <span class="ds-nav-label">Administracion</span>
                    <span class="ds-arrow">&#9660;</span>
                </button>
                <div id="ds-admin" class="collapse ds-submenu <?php echo $adm_open ? 'show' : ''; ?>">
                    <a class="<?php echo dedumsoft_nav_active($current, 'usuarios.php'); ?>" href="usuarios.php">
                        <span class="ds-subicon">&#128100;</span> Usuarios
                    </a>
                </div>

                <button class="ds-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ds-config"
                    aria-expanded="<?php echo $cfg_open ? 'true' : 'false'; ?>" aria-controls="ds-config">
                    <span class="ds-icon">&#9881;</span>
                    <span class="ds-nav-label">Ajustes</span>
                    <span class="ds-arrow">&#9660;</span>
                </button>
                <div id="ds-config" class="collapse ds-submenu <?php echo $cfg_open ? 'show' : ''; ?>">
                    <a class="<?php echo dedumsoft_nav_active($current, 'configuracion.php'); ?>" href="configuracion.php">
                        <span class="ds-subicon">&#9881;</span> Configuracion
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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(dedumsoft_csrf_token()); ?>">
                <button type="submit" class="btn btn-sm">Salir</button>
            </form>
        </aside>
        <main class="ds-main">
        <?php endif; ?>
        <?php if (!$legacy && $legacy_ua): ?>
            <div class="ds-legacy-alert">
                Si el sitio no se ve bien en tu navegador, haz clic para
                <a href="mode.php?mode=legacy">cambiar a modo legacy</a>.
            </div>
        <?php endif; ?>
