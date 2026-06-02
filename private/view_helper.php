<?php
/**
 * ============================================================================
 * VIEW HELPER — Renderizado seguro de vistas
 * ============================================================================
 *
 * Funciones para renderizar vistas con variables extraídas de forma segura.
 *
 * Uso:
 *   $data = ['rows' => $rows, 'title' => 'Proveedores'];
 *   render_view('pages/proveedores', $data);
 *
 * La vista recibe las claves de $data como variables locales.
 * Si una clave no existe, la variable será null (no undefined).
 *
 * @package Dedumsoft\Views
 */

/**
 * Renderiza una vista con variables extraídas.
 *
 * @param string $view  Ruta relativa a views/ (sin extensión .php)
 * @param array  $data  Variables a extraer en el scope de la vista
 * @return void
 */
function render_view(string $view, array $data = []): void
{
    $file = VIEWS_PATH . '/' . $view . '.php';

    if (!file_exists($file)) {
        error_log('[VIEW] Vista no encontrada: ' . $file);
        echo '<!-- VIEW ERROR: ' . htmlspecialchars($view) . ' -->';
        return;
    }

    // Extraer variables con prefijo _view_ para evitar colisiones con globales
    // pero también crear alias cortos para uso en la vista
    extract($data, EXTR_SKIP);

    include $file;
}

/**
 * Escapa texto para uso seguro en HTML (alias corto).
 *
 * @param string|null $text
 * @return string
 */
function v_e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un badge de estado para órdenes en vistas.
 *
 * @param string|null $estado
 * @return string
 */
function v_status_badge(?string $estado): string
{
    return page_status_badge($estado);
}

/**
 * Formatea una fecha para vistas.
 *
 * @param string|null $fecha
 * @param string      $formato
 * @return string
 */
function v_format_date(?string $fecha, string $formato = 'Y-m-d H:i'): string
{
    return page_format_datetime($fecha);
}

/**
 * Devuelve un ícono según el modo legacy.
 *
 * @param string $name
 * @param bool   $legacy
 * @return string
 */
function v_icon(string $name, bool $legacy): string
{
    return page_icon($name, $legacy);
}
