<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INICIO PARA OPERARIOS
 * ============================================================================
 * 
 * Página de inicio simplificada para usuarios sin acceso al dashboard.
 * Muestra accesos directos a las secciones permitidas según el rol.
 * 
 * Características:
 * - Tarjetas de acceso rápido a módulos autorizados
 * - Redirección automática a index.php si tiene permiso de dashboard
 * - Acceso especial "Mis Órdenes" para artesanos
 * - Soporte dual: íconos emoji (moderno) o PNG Fatcow (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Al menos un permiso de menú (excepto dashboard)
 * 
 * Módulos posibles:
 * - Mis Órdenes (artesanos)
 * - Producción (Menú 3)
 * - Inventario (Menú 2)
 * - Proveedores (Menú 6)
 * - Reportes (Menú 4)
 * - Usuarios (Menú 5)
 * - Configuración (Menú 7)
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

// Verificar autenticación
require_login('/login.php');

$user = get_session_user();

// Si el usuario tiene acceso al dashboard, redirigir al index completo
if (dedumsoft_user_can_menu(1, $user)) {
    header('Location: ' . base_url() . '/index.php');
    exit;
}

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();
$rolid = (int) ($user['rolid'] ?? 0);

// Calcular permisos de menú
$can_inventario = dedumsoft_user_can_menu(2, $user);
$can_produccion = dedumsoft_user_can_menu(3, $user);
$can_reportes = dedumsoft_user_can_menu(4, $user);
$can_usuarios = dedumsoft_user_can_menu(5, $user);
$can_proveedores = dedumsoft_user_can_menu(6, $user);
$can_config = dedumsoft_user_can_menu(7, $user);

// Mostrar "Mis Órdenes" solo para artesanos no-admin
$show_mis_ordenes = $can_produccion && !empty($user['artesano_id']) && $rolid !== 1;

// Si no tiene ningún permiso, denegar acceso
if (!$can_inventario && !$can_produccion && !$can_reportes && !$can_usuarios && !$can_proveedores && !$can_config) {
    dedumsoft_forbidden();
}

// Construir array de tarjetas de acceso rápido
$cards = [];
if ($show_mis_ordenes) {
    $cards[] = [
        'href' => 'artesano_ordenes.php',
        'title' => 'Mis Ordenes',
        'cta' => 'Ver ordenes',
        'desc' => 'Trabajos asignados a tu usuario',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/user_orange.png" alt="Mis Ordenes">' : '&#128119;'
    ];
}
if ($can_produccion) {
    $cards[] = [
        'href' => 'produccion.php',
        'title' => 'Ordenes de Produccion',
        'cta' => 'Abrir tablero',
        'desc' => 'Seguimiento general de ordenes',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/application_view_list.png" alt="Produccion">' : '&#127981;'
    ];
}
if ($can_inventario) {
    $cards[] = [
        'href' => 'inventario_insumos.php',
        'title' => 'Inventario',
        'cta' => 'Ir a inventario',
        'desc' => 'Insumos, maquinaria y oro',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/box.png" alt="Inventario">' : '&#128230;'
    ];
}
if ($can_proveedores) {
    $cards[] = [
        'href' => 'proveedores.php',
        'title' => 'Proveedores',
        'cta' => 'Ver proveedores',
        'desc' => 'Gestion de proveedores',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/lorry.png" alt="Proveedores">' : '&#128666;'
    ];
}
if ($can_reportes) {
    $cards[] = [
        'href' => 'reportes.php',
        'title' => 'Reportes',
        'cta' => 'Abrir reportes',
        'desc' => 'Indicadores operativos',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/chart_line.png" alt="Reportes">' : '&#128200;'
    ];
}
if ($can_usuarios) {
    $cards[] = [
        'href' => 'usuarios.php',
        'title' => 'Usuarios',
        'cta' => 'Gestionar',
        'desc' => 'Control de usuarios',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/user.png" alt="Usuarios">' : '&#128101;'
    ];
}
if ($can_config) {
    $cards[] = [
        'href' => 'configuracion.php',
        'title' => 'Configuracion',
        'cta' => 'Ajustes',
        'desc' => 'Preferencias del sistema',
        'icon' => $legacy ? '<img src="assets/icons/fatcow/32/cog.png" alt="Configuracion">' : '&#9881;'
    ];
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';

$tones = ['gold', 'silver', 'bronze', 'pearl'];
?>
<div class="content">
    <div class="content-header">
        <h1>Panel Operario</h1>
        <p>Accesos rapidos segun tus permisos</p>
    </div>

    <div class="dashboard-grid">
        <?php foreach ($cards as $idx => $card): ?>
            <a class="dashboard-card <?php echo $tones[$idx % count($tones)]; ?>"
                href="<?php echo htmlspecialchars($card['href']); ?>" style="color: inherit; text-decoration: none;">
                <div class="card-icon"><?php echo $card['icon']; ?></div>
                <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                <p class="stat"><?php echo htmlspecialchars($card['cta']); ?></p>
                <div class="card-footer">
                    <span><?php echo htmlspecialchars($card['desc']); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
</body>

</html>