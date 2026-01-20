<?php
/**
 * ============================================================================
 * PARTIAL: BARRA DE NAVEGACIÓN LATERAL
 * ============================================================================
 * 
 * Genera la barra lateral de navegación con menús dinámicos basados en
 * los permisos del usuario autenticado.
 * 
 * Características:
 * - Menús colapsables (Bootstrap Collapse en navegadores modernos)
 * - Íconos Fatcow para modo legacy, Emojis para modo moderno
 * - Resaltado automático de sección activa
 * - Panel de usuario con avatar y rol
 * - Botón de logout con protección CSRF
 * 
 * Permisos de menú (seg_menu IDs):
 * - 1: Dashboard (Inicio)
 * - 2: Inventario (Insumos, Maquinaria, Oro, Ubicaciones)
 * - 3: Producción (Órdenes, Mis Órdenes para artesanos)
 * - 4: Reportes (Producción, Ventas, Compras)
 * - 5: Usuarios (Administración)
 * - 6: Proveedores
 * - 7: Configuración
 * 
 * Funciones helper:
 * - dedumsoft_nav_active(): Marca link activo por página
 * - dedumsoft_nav_active_section(): Marca link activo por sección GET
 * 
 * @package Dedumsoft\Partials
 * @author  Equipo Dedumsoft
 */

require_once PRIVATE_PATH . '/Database/Guard.php';

// =============================================================================
// FUNCIONES HELPER DE NAVEGACIÓN
// =============================================================================

/**
 * Determina si un link de navegación debe estar activo.
 * 
 * @param string $current Nombre del archivo PHP actual
 * @param string $target  Nombre del archivo objetivo
 * @return string 'active' si coinciden, '' si no
 */
if (!function_exists('dedumsoft_nav_active')) {
    function dedumsoft_nav_active(string $current, string $target): string
    {
        return $current === $target ? 'active' : '';
    }
}

/**
 * Determina si un link de navegación con sección debe estar activo.
 * Usa el parámetro GET 'section' para comparar.
 * 
 * @param string $current Nombre del archivo PHP actual
 * @param string $target  Nombre del archivo objetivo
 * @param string $section Nombre de la sección a verificar
 * @param string $default Sección por defecto si no hay parámetro
 * @return string 'active' si coinciden, '' si no
 */
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

// =============================================================================
// PREPARACIÓN DE DATOS DE NAVEGACIÓN
// =============================================================================

// Detectar página actual y modo de navegador
$current = basename($_SERVER['PHP_SELF'] ?? '');
$legacy = dedumsoft_is_legacy_browser();
$legacy_ua = dedumsoft_is_legacy_ua();

// Determinar qué secciones del menú están expandidas
$inv_open = in_array($current, ['inventario.php', 'inventario_insumos.php', 'inventario_maquinaria.php', 'inventario_oro.php', 'proveedores.php', 'ubicaciones.php'], true);
$prod_open = in_array($current, ['produccion.php'], true);
$rep_open = in_array($current, ['reportes.php'], true);
$adm_open = in_array($current, ['usuarios.php'], true);
$cfg_open = in_array($current, ['configuracion.php'], true);

// Datos del usuario para el panel inferior
$nombre = trim($user['nombre'] ?? '');
$avatar = $nombre !== '' ? strtoupper(substr($nombre, 0, 1)) : '?';
$rolid = (int) ($user['rolid'] ?? 0);

// Etiqueta del rol para mostrar
$rol_label = 'Operador';
if ($rolid === 1) {
    $rol_label = 'Administrador';
} elseif ($rolid === 3) {
    $rol_label = 'Lectura';
}

// =============================================================================
// CÁLCULO DE PERMISOS DE MENÚ
// =============================================================================
// Permisos de menu: id_menu => ['abrir', 'guardar', 'editar', 'eliminar']
// seg_menu ids: 1=Dashboard, 2=Inventario, 3=Produccion, 4=Reportes, 5=Usuarios, 6=Proveedores, 7=Configuracion

$permisos = $user['permisos_menu'] ?? [];
$can_dashboard = isset($permisos[1]);
$can_inventario = isset($permisos[2]);
$can_produccion = isset($permisos[3]);
$can_reportes = isset($permisos[4]);
$can_usuarios = isset($permisos[5]);
$can_proveedores = isset($permisos[6]);
$can_config = isset($permisos[7]);

// Determinar si mostrar "Mis Órdenes" (solo artesanos no-admin)
$has_artesano = !empty($user['artesano_id']);
$show_mis_ordenes = $can_produccion && $has_artesano && $rolid !== 1;

// Determinar si mostrar link de inicio (al menos un permiso)
$show_home = $can_dashboard || $can_inventario || $can_produccion || $can_reportes || $can_usuarios || $can_proveedores || $can_config;

// URL de inicio según permisos (dashboard completo o versión operario)
$home_href = $can_dashboard ? 'index.php' : 'index_operario.php';
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
            <?php if ($show_home): ?>
                <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, $home_href); ?>"
                    href="<?php echo $home_href; ?>">
                    <img class="ds-icon-img" src="assets/icons/fatcow/16/application_home.png" alt="">
                    Inicio
                </a>
            <?php endif; ?>

            <?php if ($can_inventario): ?>
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
                <?php if ($can_proveedores): ?>
                    <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'proveedores.php'); ?>" href="proveedores.php">
                        <img class="ds-icon-img" src="assets/icons/fatcow/16/lorry.png" alt="">
                        Proveedores
                    </a>
                <?php endif; ?>
                <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'ubicaciones.php'); ?>" href="ubicaciones.php">
                    <img class="ds-icon-img" src="assets/icons/fatcow/16/building.png" alt="">
                    Ubicaciones
                </a>
            <?php endif; ?>

            <?php if ($can_produccion): ?>
                <div class="ds-nav-section">Produccion</div>
                <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'produccion.php'); ?>" href="produccion.php">
                    <img class="ds-icon-img" src="assets/icons/fatcow/16/application_view_list.png" alt="">
                    Ordenes
                </a>

                <?php if ($show_mis_ordenes): ?>
                    <div class="ds-nav-section">Artesano</div>
                    <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'artesano_ordenes.php'); ?>"
                        href="artesano_ordenes.php">
                        <img class="ds-icon-img" src="assets/icons/fatcow/16/user_orange.png" alt="">
                        Mis Ordenes
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($can_reportes): ?>
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
            <?php endif; ?>

            <?php if ($can_usuarios): ?>
                <div class="ds-nav-section">Administracion</div>
                <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'usuarios.php'); ?>" href="usuarios.php">
                    <img class="ds-icon-img" src="assets/icons/fatcow/16/user.png" alt="">
                    Usuarios
                </a>
            <?php endif; ?>

            <?php if ($can_config): ?>
                <div class="ds-nav-section">Ajustes</div>
                <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'configuracion.php'); ?>"
                    href="configuracion.php">
                    <img class="ds-icon-img" src="assets/icons/fatcow/16/cog.png" alt="">
                    Configuracion
                </a>
            <?php endif; ?>
        </div>

        <div class="ds-user-panel">
            <div class="ds-user-avatar"><?php echo htmlspecialchars($avatar); ?></div>
            <div class="ds-user-info">
                <div class="ds-user-name"><?php echo htmlspecialchars($nombre !== '' ? $nombre : 'Admin'); ?></div>
                <div class="ds-user-role"><?php echo htmlspecialchars($rol_label); ?></div>
            </div>
        </div>

        <form class="ds-logout" action="auth/logout.php" method="post">
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
                <?php if ($show_home): ?>
                    <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, $home_href); ?>"
                        href="<?php echo $home_href; ?>">
                        <span class="ds-icon">&#127968;</span>
                        <span class="ds-nav-label">Inicio</span>
                    </a>
                <?php endif; ?>

                <?php if ($can_inventario): ?>
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
                        <?php if ($can_proveedores): ?>
                            <a class="<?php echo dedumsoft_nav_active($current, 'proveedores.php'); ?>" href="proveedores.php">
                                <span class="ds-subicon">&#128666;</span> Proveedores
                            </a>
                        <?php endif; ?>
                        <a class="<?php echo dedumsoft_nav_active($current, 'ubicaciones.php'); ?>" href="ubicaciones.php">
                            <span class="ds-subicon">&#128205;</span> Ubicaciones
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($can_produccion): ?>
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

                    <?php if ($show_mis_ordenes): ?>
                        <a class="ds-nav-link <?php echo dedumsoft_nav_active($current, 'artesano_ordenes.php'); ?>"
                            href="artesano_ordenes.php">
                            <span class="ds-icon">&#128119;</span>
                            <span class="ds-nav-label">Mis Ordenes</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($can_reportes): ?>
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
                <?php endif; ?>

                <?php if ($can_usuarios): ?>
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
                <?php endif; ?>

                <?php if ($can_config): ?>
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
                <?php endif; ?>
            </div>

            <div class="ds-user-panel">
                <div class="ds-user-avatar"><?php echo htmlspecialchars($avatar); ?></div>
                <div class="ds-user-info">
                    <div class="ds-user-name"><?php echo htmlspecialchars($nombre !== '' ? $nombre : 'Admin'); ?></div>
                    <div class="ds-user-role"><?php echo htmlspecialchars($rol_label); ?></div>
                </div>
            </div>

            <form class="ds-logout" action="auth/logout.php" method="post">
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
        <?php if (!empty($_GET['denied'])): ?>
            <div class="ds-alert ds-alert--warning">
                <strong>Acceso no autorizado.</strong> Se te envio al inicio disponible para tu rol.
            </div>
        <?php endif; ?>