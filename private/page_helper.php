<?php
/**
 * ============================================================================
 * PAGE HELPER — Reducción de boilerplate para páginas HTML
 * ============================================================================
 *
 * Este archivo centraliza el patrón repetitivo de cada public/*.php:
 *   require bootstrap → require auth → require db → login check → menu check
 *
 * Uso típico en una página:
 *   <?php
 *   require_once __DIR__ . '/../private/page_helper.php';
 *   page_init(6); // Menú 6 = Proveedores
 *   $legacy = page_is_legacy();
 *   // ... obtener datos ...
 *   page_render_start(6);
 *   // ... HTML ...
 *   page_render_end();
 *
 * @package Dedumsoft\Helpers
 * @author  Equipo Dedumsoft
 */

// Solo se carga desde un script que ya hizo bootstrap (o lo hacemos nosotros)
if (!defined('DEDUMSOFT_APP')) {
    require_once __DIR__ . '/bootstrap.php';
}

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Database/Guard.php';
require_once PRIVATE_PATH . '/view_helper.php';

/**
 * Inicializa una página HTML protegida.
 *
 * @param int    $menu_id        ID del menú requerido (seg_menu.id)
 * @param string $login_redirect Ruta de redirección si no está logueado
 * @return void
 */
function page_init(int $menu_id, string $login_redirect = 'login.php'): void
{
    require_login($login_redirect);
    require_menu_access($menu_id);
}

/**
 * Detecta si el navegador del cliente es legacy (IE8).
 *
 * @return bool
 */
function page_is_legacy(): bool
{
    return dedumsoft_is_legacy_browser();
}

/**
 * Renderiza la cabecera de la página (header + nav).
 *
 * @param int         $menu_id   ID del menú activo (para resaltar en nav)
 * @param string|null $uplot     Si '1' carga uPlot
 * @return void
 */
function page_render_start(int $menu_id, ?string $uplot = null): void
{
    // uPlot se carga definiendo $load_uplot antes del include de header
    if ($uplot !== null) {
        $load_uplot = true;
    }

    include VIEWS_PATH . '/layouts/header.php';
    include VIEWS_PATH . '/layouts/nav.php';
}

/**
 * Renderiza el cierre de la página (footer + scripts de página + cierre de body/html).
 *
 * @param callable|null $after_footer Callback opcional para scripts dependientes del footer
 * @return void
 */
function page_render_end(?callable $after_footer = null): void
{
    include VIEWS_PATH . '/layouts/footer.php';

    if ($after_footer !== null) {
        $after_footer();
    }

    echo "\n</body>\n</html>\n";
}

/**
 * Escapa texto para uso seguro en HTML.
 * Wrapper corto de htmlspecialchars con UTF-8.
 *
 * @param string|null $text Texto a escapar
 * @return string
 */
function page_e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un badge HTML con color según el estado de una orden.
 * Extraído de index.php para reutilización en cualquier página.
 *
 * @param string|null $value Estado de la orden
 * @return string HTML del badge
 */
function page_status_badge(?string $value): string
{
    $raw = strtolower(trim((string) $value));
    $label = strtoupper(str_replace('_', ' ', $raw));
    $class = 'ds-badge--neutral';

    switch ($raw) {
        case 'pendiente':
            $class = 'ds-badge--warning';
            break;
        case 'en_proceso':
            $class = 'ds-badge--info';
            break;
        case 'terminada':
            $class = 'ds-badge--success';
            break;
        case 'cancelada':
            $class = 'ds-badge--danger';
            break;
        case 'pausada':
            $class = 'ds-badge--muted';
            break;
    }

    return '<span class="ds-badge ' . $class . '">' . page_e($label) . '</span>';
}

/**
 * Formatea una fecha/hora ISO 8601 para visualización.
 *
 * @param string|null $value Fecha en formato ISO 8601
 * @return string Fecha formateada (Y-m-d H:i) o cadena vacía
 */
function page_format_datetime(?string $value): string
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

/**
 * Devuelve el ícono apropiado según el modo legacy.
 *
 * @param string $name  Nombre del ícono: 'inventory', 'sales', 'orders', 'done'
 * @param bool   $legacy Si true usa PNG Fatcow, si false usa emoji
 * @return string HTML del ícono
 */
function page_icon(string $name, bool $legacy): string
{
    if ($legacy) {
        $map = [
            'inventory' => 'box.png',
            'sales'     => 'cash_stack.png',
            'orders'    => 'application_view_list.png',
            'done'      => 'tick.png',
        ];
        $file = $map[$name] ?? 'bullet_star.png';
        return '<img src="assets/icons/fatcow/32/' . $file . '" alt="">';
    }

    $map = [
        'inventory' => '&#128230;', // 📦
        'sales'     => '&#128176;', // 💰
        'orders'    => '&#128203;', // 📋
        'done'      => '&#9989;',   // ✅
    ];
    return $map[$name] ?? '';
}
