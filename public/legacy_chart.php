<?php
/**
 * ============================================================================
 * GENERADOR DE GRÁFICOS PARA MODO LEGACY (IE8)
 * ============================================================================
 * 
 * Genera imágenes PNG de gráficos para navegadores que no soportan
 * JavaScript moderno (Canvas, SVG, etc.).
 * 
 * Tipos de gráficos soportados:
 * - produccion: Barras de órdenes por estado
 * - inventario: Barras de items por tipo
 * - eficiencia: Barras de piezas por artesano
 * - materiales: Barras de costo por tipo de material
 * - ventas: Líneas de ventas diarias
 * - compras: Barras de compras por tipo de inventario
 * - usuarios: Barras de usuarios por rol
 * - ventas_mes: Líneas de ventas del mes (dashboard)
 * - ordenes_estado: Barras de órdenes por estado (dashboard)
 * 
 * Características:
 * - Usa la extensión GD de PHP para generar imágenes
 * - Caché via ETag para optimizar ancho de banda
 * - Dimensiones configurables (width, height)
 * - Rango de fechas configurable
 * 
 * Autenticación: Requerida (via sesión)
 * Autorización: Menú 4 (Reportes) o Menú 1 (Dashboard)
 * 
 * Parámetros GET:
 * - chart: Tipo de gráfico (requerido)
 * - w: Ancho en pixeles (default: 580, max: 800)
 * - h: Alto en pixeles (default: 240, max: 400)
 * - desde: Fecha inicial del rango
 * - hasta: Fecha final del rango
 * 
 * Respuesta: Imagen PNG (Content-Type: image/png)
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Controllers/ReporteController.php';

// Verificar autenticación (sin redirección, genera imagen vacía)
$user = get_session_user();
if (!$user) {
    dedumsoft_output_empty_png();
}

// Parámetros del gráfico
$chart = strtolower(trim($_GET['chart'] ?? ''));
$width = dedumsoft_clamp((int) ($_GET['w'] ?? 580), 320, 800);
$height = dedumsoft_clamp((int) ($_GET['h'] ?? 240), 180, 400);

// Verificar autorización para reportes o graficos del dashboard.
$dashboard_charts = ['ventas_mes', 'ordenes_estado'];
$can_view_chart = dedumsoft_user_can_menu(4, $user)
    || (in_array($chart, $dashboard_charts, true) && dedumsoft_user_can_menu(1, $user));
if (!$can_view_chart) {
    dedumsoft_output_empty_png();
}

// Parámetros de rango de fechas
$default_desde = date('Y-m-01');
$default_hasta = date('Y-m-t');
$desde = dedumsoft_parse_date($_GET['desde'] ?? $default_desde, $default_desde);
$hasta = dedumsoft_parse_date($_GET['hasta'] ?? $default_hasta, $default_hasta);

// Corregir si las fechas están invertidas
if ($desde > $hasta) {
    $tmp = $desde;
    $desde = $hasta;
    $hasta = $tmp;
}

// Validación de tipo de gráfico permitido
if (!in_array($chart, ['produccion', 'inventario', 'eficiencia', 'materiales', 'ventas', 'compras', 'usuarios', 'ventas_mes', 'ordenes_estado'], true)) {
    dedumsoft_render_empty_chart('Sin datos', $width, $height);
}

// Cache control params (optional)
$cache_bust = trim((string) ($_GET['cb'] ?? ''));
$disable_cache = !empty($_GET['nocache']);

// Verificar que GD esté disponible
if (!function_exists('imagecreatetruecolor')) {
    dedumsoft_output_empty_png();
}

// =============================================================================
// GENERACIÓN DE GRÁFICOS SEGÚN TIPO
// =============================================================================
try {
    $ctrl = new ReporteController($connLogic);
    $result = $ctrl->legacyChartData($chart, $desde, $hasta);
    if (!$result['success']) {
        dedumsoft_render_empty_chart('Sin datos', $width, $height);
    }

    $rows = $result['data'];
    dedumsoft_apply_etag($chart, $width, $height, $desde, $hasta, $rows, $cache_bust, $disable_cache);

    switch ($chart) {
        case 'produccion':
            dedumsoft_render_bar_chart($rows, 'estado', 'total', 'Produccion', $width, $height);
            break;
        case 'inventario':
            dedumsoft_render_bar_chart($rows, 'tipo', 'total', 'Inventario', $width, $height);
            break;
        case 'eficiencia':
            dedumsoft_render_bar_chart($rows, 'artesano', 'piezas', 'Eficiencia', $width, $height);
            break;
        case 'materiales':
            dedumsoft_render_bar_chart($rows, 'tipo_material', 'total', 'Materiales', $width, $height);
            break;
        case 'ventas':
            dedumsoft_render_line_chart($rows, 'dia', 'total', 'Ventas', $width, $height);
            break;
        case 'compras':
            dedumsoft_render_bar_chart($rows, 'tipo_inventario', 'total', 'Compras', $width, $height);
            break;
        case 'usuarios':
            dedumsoft_render_bar_chart($rows, 'rol', 'total', 'Usuarios', $width, $height);
            break;
        case 'ventas_mes':
            dedumsoft_render_line_chart($rows, 'dia', 'total', 'Ventas del mes', $width, $height);
            break;
        case 'ordenes_estado':
            dedumsoft_render_bar_chart($rows, 'estado', 'total', 'Ordenes', $width, $height);
            break;
    }
} catch (Exception $e) {
    error_log('legacy_chart error: ' . $e->getMessage());
    dedumsoft_render_empty_chart('Sin datos', $width, $height);
}

// =============================================================================
// FUNCIONES AUXILIARES PARA GENERACIÓN DE GRÁFICOS
// =============================================================================

/**
 * Limita un valor numérico a un rango específico.
 * 
 * @param int $value Valor a limitar
 * @param int $min Valor mínimo permitido
 * @param int $max Valor máximo permitido
 * @return int Valor limitado
 */
function dedumsoft_clamp(int $value, int $min, int $max): int
{
    if ($value < $min) {
        return $min;
    }
    if ($value > $max) {
        return $max;
    }
    return $value;
}

/**
 * Parsea una fecha en formato Y-m-d.
 * Si el formato es inválido, retorna el valor de fallback.
 * 
 * @param string $value Fecha a parsear
 * @param string $fallback Valor por defecto
 * @return string Fecha en formato Y-m-d
 */
function dedumsoft_parse_date(string $value, string $fallback): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if ($dt === false) {
        return $fallback;
    }
    return $dt->format('Y-m-d');
}

/**
 * Emite un PNG vacío de 1x1 pixel transparente.
 * Útil como respuesta cuando no hay datos o hay error.
 * 
 * @return void Termina la ejecución con exit()
 */
function dedumsoft_output_empty_png(): void
{
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    // PNG base64 de 1x1 pixel transparente
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');
    exit;
}

/**
 * Configura el ETag usando los datos del gráfico y responde 304 si aplica.
 *
 * @param string $chart Tipo de gráfico
 * @param int $width Ancho de la imagen
 * @param int $height Alto de la imagen
 * @param string $desde Fecha inicio
 * @param string $hasta Fecha fin
 * @param array $rows Filas del reporte
 * @param string $cache_bust Valor opcional para invalidar caché
 * @param bool $disable_cache Si true, no se usa caché
 * @return void
 */
function dedumsoft_apply_etag(
    string $chart,
    int $width,
    int $height,
    string $desde,
    string $hasta,
    array $rows,
    string $cache_bust,
    bool $disable_cache
): void {
    if ($disable_cache) {
        return;
    }

    $payload = json_encode($rows);
    if ($payload === false) {
        $payload = serialize($rows);
    }

    $seed = $chart . '|' . $width . '|' . $height . '|' . $desde . '|' . $hasta . '|' . $payload;
    if ($cache_bust !== '') {
        $seed .= '|cb:' . $cache_bust;
    }

    $etag = '"' . md5($seed) . '"';
    $GLOBALS['etag'] = $etag;

    $client_etag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($client_etag === $etag) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

/**
 * Crea un canvas (imagen) con paleta de colores predefinida.
 * Configura el fondo y habilita antialiasing si está disponible.
 * 
 * @param int $width Ancho en pixeles
 * @param int $height Alto en pixeles
 * @param array &$colors Array donde se guardarán los colores asignados
 * @return resource|GdImage Recurso de imagen GD
 */
function dedumsoft_create_canvas(int $width, int $height, array &$colors)
{
    $img = imagecreatetruecolor($width, $height);

    // Paleta de colores del sistema
    $colors = [
        'bg' => imagecolorallocate($img, 248, 249, 250),          // Fondo gris claro
        'axis' => imagecolorallocate($img, 52, 58, 64),           // Ejes gris oscuro
        'grid' => imagecolorallocate($img, 206, 212, 218),        // Líneas de cuadrícula
        'bar1' => imagecolorallocate($img, 13, 110, 253),         // Azul vibrante
        'bar2' => imagecolorallocate($img, 25, 135, 84),          // Verde
        'bar3' => imagecolorallocate($img, 220, 53, 69),          // Rojo
        'bar4' => imagecolorallocate($img, 255, 193, 7),          // Amarillo
        'bar5' => imagecolorallocate($img, 111, 66, 193),         // Púrpura
        'bar6' => imagecolorallocate($img, 13, 202, 240),         // Cyan
        'bar_top' => imagecolorallocate($img, 255, 255, 255),     // Brillo superior
        'shadow' => imagecolorallocatealpha($img, 0, 0, 0, 90),   // Sombra semi-transparente
        'line' => imagecolorallocate($img, 13, 110, 253),         // Línea principal
        'line_dark' => imagecolorallocate($img, 10, 88, 202),     // Línea borde
        'fill' => imagecolorallocatealpha($img, 13, 110, 253, 90),// Relleno de área
        'text' => imagecolorallocate($img, 33, 37, 41),           // Texto principal
        'value' => imagecolorallocate($img, 13, 110, 253),        // Valores numéricos
        'muted' => imagecolorallocate($img, 108, 117, 125)        // Texto secundario
    ];

    // Fondo blanco
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $colors['bg']);

    // Habilitar antialiasing si está disponible
    if (function_exists('imageantialias')) {
        imageantialias($img, true);
    }
    return $img;
}

/**
 * Calcula las coordenadas del área de dibujo del gráfico.
 * Deja márgenes para título, etiquetas y ejes.
 * 
 * @param int $width Ancho total de la imagen
 * @param int $height Alto total de la imagen
 * @return array Coordenadas: left, top, right, bottom, width, height
 */
function dedumsoft_plot_area(int $width, int $height): array
{
    $left = 50;     // Margen para etiquetas del eje Y
    $right = 12;    // Margen derecho
    $top = 26;      // Margen para título
    $bottom = 42;   // Margen para etiquetas del eje X
    return [
        'left' => $left,
        'top' => $top,
        'right' => $width - $right,
        'bottom' => $height - $bottom,
        'width' => $width - $left - $right,
        'height' => $height - $top - $bottom
    ];
}

/**
 * Dibuja el título centrado en la parte superior del gráfico.
 * 
 * @param resource|GdImage $img Recurso de imagen
 * @param string $title Título del gráfico
 * @param array $colors Paleta de colores
 * @param int $width Ancho total de la imagen
 * @return void
 */
function dedumsoft_draw_title($img, string $title, array $colors, int $width): void
{
    $title = trim($title);
    if ($title === '') {
        return;
    }
    $font = 3;  // Fuente built-in de GD (tamaño mediano)
    $x = (int) (($width - (strlen($title) * imagefontwidth($font))) / 2);
    if ($x < 4) {
        $x = 4;
    }
    imagestring($img, $font, $x, 6, $title, $colors['text']);
}

/**
 * Renderiza un gráfico vacío con un mensaje.
 * Usado cuando no hay datos disponibles.
 * 
 * @param string $title Título o mensaje a mostrar
 * @param int $width Ancho de la imagen
 * @param int $height Alto de la imagen
 * @return void Termina con exit()
 */
function dedumsoft_render_empty_chart(string $title, int $width, int $height): void
{
    $colors = [];
    $img = dedumsoft_create_canvas($width, $height, $colors);
    dedumsoft_draw_title($img, $title, $colors, $width);
    imagestring($img, 3, 20, (int) ($height / 2) - 6, 'Sin datos', $colors['text']);
    dedumsoft_output_image($img);
}

/**
 * Renderiza un gráfico de barras verticales.
 * 
 * Características:
 * - Barras con sombra y gradiente
 * - Colores rotativos (6 colores)
 * - Etiquetas de valor sobre cada barra
 * - Etiquetas de categoría debajo (si hay ≤12 barras)
 * - Resumen con total e items
 * 
 * @param array $rows Datos del gráfico
 * @param string $label_key Clave para etiquetas (eje X)
 * @param string $value_key Clave para valores (eje Y)
 * @param string $title Título del gráfico
 * @param int $width Ancho de la imagen
 * @param int $height Alto de la imagen
 * @return void Termina con exit()
 */
function dedumsoft_render_bar_chart(array $rows, string $label_key, string $value_key, string $title, int $width, int $height): void
{
    // Extraer etiquetas y valores
    $labels = [];
    $values = [];
    foreach ($rows as $row) {
        $label = (string) ($row[$label_key] ?? '');
        $value = (float) ($row[$value_key] ?? 0);
        if ($label === '') {
            continue;
        }
        $labels[] = $label;
        $values[] = $value;
    }

    // Si no hay datos, mostrar gráfico vacío
    if (!$values) {
        dedumsoft_render_empty_chart($title, $width, $height);
        return;
    }

    $colors = [];
    $img = dedumsoft_create_canvas($width, $height, $colors);
    $plot = dedumsoft_plot_area($width, $height);
    dedumsoft_draw_title($img, $title, $colors, $width);

    $max = max($values);
    $total = array_sum($values);
    if ($max <= 0) {
        $max = 1;
    }

    // Draw grid lines and Y-axis labels
    dedumsoft_draw_grid($img, $plot, $colors, $max);
    dedumsoft_draw_axes($img, $plot, $colors);

    $count = count($values);
    $gap = max(4, (int) ($plot['width'] / max(1, $count * 6)));
    $bar_width = (int) (($plot['width'] - ($count + 1) * $gap) / max(1, $count));
    if ($bar_width < 8) {
        $bar_width = 8;
        $gap = 2;
    }

    $bar_gap_width = $bar_width + $gap;
    $plot_height = $plot['height'];
    $plot_left = $plot['left'];
    $plot_bottom = $plot['bottom'];

    for ($i = 0; $i < $count; $i++) {
        $bar_height = (int) (($values[$i] / $max) * $plot_height);
        if ($bar_height < 1 && $values[$i] > 0) {
            $bar_height = 1;
        }
        $x1 = $plot_left + $gap + $bar_gap_width * $i;
        $y1 = $plot_bottom - $bar_height;
        $x2 = $x1 + $bar_width;

        // Seleccionar color de barra (rotar entre 6 colores)
        $bar_color = $colors['bar' . (($i % 6) + 1)];

        // Dibujar sombra (offset de 3px)
        if ($bar_height > 3) {
            imagefilledrectangle($img, $x1 + 3, $y1 + 3, $x2 + 3, $plot_bottom, $colors['shadow']);
        }

        // Dibujar barra principal
        imagefilledrectangle($img, $x1, $y1, $x2, $plot_bottom - 1, $bar_color);

        // Agregar gradiente sutil (líneas blancas semi-transparentes en la parte superior)
        if ($bar_height > 8) {
            $gradient_height = min(10, (int) ($bar_height / 3));
            for ($g = 0; $g < $gradient_height; $g++) {
                $alpha = (int) (100 - ($g * 8));
                if ($alpha < 0)
                    $alpha = 0;
                $grad_color = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
                imageline($img, $x1 + 1, $y1 + $g, $x2 - 1, $y1 + $g, $grad_color);
            }
        }

        // Borde superior más prominente
        imageline($img, $x1, $y1, $x2, $y1, $colors['bar_top']);
        imagesetthickness($img, 2);
        imagerectangle($img, $x1, $y1, $x2, $plot_bottom - 1, imagecolorallocatealpha($img, 0, 0, 0, 50));
        imagesetthickness($img, 1);

        // Draw value on top of bar
        $value_text = dedumsoft_format_value($values[$i]);
        $value_width = strlen($value_text) * imagefontwidth(1);
        $value_x = $x1 + (int) (($bar_width - $value_width) / 2);
        if ($value_x < $plot['left']) {
            $value_x = $plot['left'];
        }
        $value_y = $y1 - 10;
        if ($value_y < $plot['top']) {
            $value_y = $y1 + 2;
        }
        imagestring($img, 1, $value_x, $value_y, $value_text, $colors['value']);

        // Draw label below bar
        if ($count <= 12) {
            $label = dedumsoft_trim_label($labels[$i], 8);
            $label_width = strlen($label) * imagefontwidth(1);
            $label_x = $x1 + (int) (($bar_width - $label_width) / 2);
            if ($label_x < $plot['left']) {
                $label_x = $plot['left'];
            }
            imagestring($img, 1, $label_x, $plot['bottom'] + 4, $label, $colors['text']);
        }
    }

    // Draw summary
    $summary = 'Total: ' . dedumsoft_format_value($total) . ' | Items: ' . $count;
    imagestring($img, 1, $plot['left'], $plot['bottom'] + 18, $summary, $colors['muted']);

    dedumsoft_output_image($img);
}

/**
 * Renderiza un gráfico de líneas temporal.
 * 
 * Características:
 * - Área rellena bajo la línea
 * - Puntos con sombra y borde
 * - Etiquetas de fecha en el eje X
 * - Valores mostrados en puntos clave
 * - Resumen con total, promedio y cantidad de puntos
 * 
 * @param array $rows Datos del gráfico (deben tener campo fecha)
 * @param string $label_key Clave del campo fecha
 * @param string $value_key Clave del campo valor
 * @param string $title Título del gráfico
 * @param int $width Ancho de la imagen
 * @param int $height Alto de la imagen
 * @return void Termina con exit()
 */
function dedumsoft_render_line_chart(array $rows, string $label_key, string $value_key, string $title, int $width, int $height): void
{
    // Convertir fechas a timestamps
    $points = [];
    foreach ($rows as $row) {
        $label = (string) ($row[$label_key] ?? '');
        $value = (float) ($row[$value_key] ?? 0);
        $ts = strtotime($label);
        if (!$ts) {
            continue;
        }
        $points[] = [$ts, $value];
    }

    // Sin datos, mostrar gráfico vacío
    if (!$points) {
        dedumsoft_render_empty_chart($title, $width, $height);
        return;
    }

    // Ordenar por fecha
    usort($points, function ($a, $b) {
        return $a[0] <=> $b[0];
    });

    $xs = array_column($points, 0);
    $ys = array_column($points, 1);
    $min_x = min($xs);
    $max_x = max($xs);
    $max_y = max($ys);
    $min_y = min($ys);
    $total = array_sum($ys);
    $avg = count($ys) > 0 ? $total / count($ys) : 0;
    if ($max_y <= 0) {
        $max_y = 1;
    }

    $colors = [];
    $img = dedumsoft_create_canvas($width, $height, $colors);
    $plot = dedumsoft_plot_area($width, $height);
    dedumsoft_draw_title($img, $title, $colors, $width);

    // Draw grid and axes
    dedumsoft_draw_grid($img, $plot, $colors, $max_y);
    dedumsoft_draw_axes($img, $plot, $colors);

    // Draw filled area under line
    $fill_points = [];
    foreach ($points as $point) {
        $x_val = $point[0];
        $y_val = $point[1];
        $px = $plot['left'];
        if ($max_x > $min_x) {
            $px = $plot['left'] + (int) (($x_val - $min_x) / ($max_x - $min_x) * $plot['width']);
        }
        $py = $plot['bottom'] - (int) (($y_val / $max_y) * $plot['height']);
        $fill_points[] = [$px, $py];
    }

    // Draw filled polygon
    if (count($fill_points) >= 2) {
        $poly = [];
        $poly[] = $plot['left'];
        $poly[] = $plot['bottom'];
        foreach ($fill_points as $fp) {
            $poly[] = $fp[0];
            $poly[] = $fp[1];
        }
        $poly[] = $fill_points[count($fill_points) - 1][0];
        $poly[] = $plot['bottom'];
        if (function_exists('imagefilledpolygon')) {
            imagefilledpolygon($img, $poly, $colors['fill']);
        }
    }

    // Draw line and points with values
    $prev_x = null;
    $prev_y = null;
    $point_index = 0;
    $show_all_values = count($points) <= 10;
    foreach ($points as $point) {
        $x_val = $point[0];
        $y_val = $point[1];
        $px = $plot['left'];
        if ($max_x > $min_x) {
            $px = $plot['left'] + (int) (($x_val - $min_x) / ($max_x - $min_x) * $plot['width']);
        }
        $py = $plot['bottom'] - (int) (($y_val / $max_y) * $plot['height']);
        if ($prev_x !== null) {
            // Sombra de línea
            imagesetthickness($img, 3);
            imageline($img, $prev_x + 1, $prev_y + 1, $px + 1, $py + 1, imagecolorallocatealpha($img, 0, 0, 0, 80));
            // Línea principal más gruesa
            imageline($img, $prev_x, $prev_y, $px, $py, $colors['line']);
            imagesetthickness($img, 1);
        }
        // Punto con sombra y borde
        imagefilledellipse($img, $px + 1, $py + 1, 8, 8, imagecolorallocatealpha($img, 0, 0, 0, 80));
        imagefilledellipse($img, $px, $py, 8, 8, $colors['line']);
        imageellipse($img, $px, $py, 8, 8, $colors['line_dark']);
        imagefilledellipse($img, $px, $py, 4, 4, $colors['bar_top']);

        // Show value for key points (first, last, max, or all if few points)
        if ($show_all_values || $point_index === 0 || $point_index === count($points) - 1 || $y_val === $max_y) {
            $value_text = dedumsoft_format_value($y_val);
            $value_width = strlen($value_text) * imagefontwidth(1);
            $value_x = $px - (int) ($value_width / 2);
            $value_y = $py - 12;
            if ($value_y < $plot['top']) {
                $value_y = $py + 8;
            }
            imagestring($img, 1, $value_x, $value_y, $value_text, $colors['value']);
        }

        $prev_x = $px;
        $prev_y = $py;
        $point_index++;
    }

    // Draw date range labels
    $start_label = date('M d', $min_x);
    $end_label = date('M d', $max_x);
    imagestring($img, 1, $plot['left'], $plot['bottom'] + 4, $start_label, $colors['text']);
    $end_width = strlen($end_label) * imagefontwidth(1);
    imagestring($img, 1, $plot['right'] - $end_width, $plot['bottom'] + 4, $end_label, $colors['text']);

    // Draw middle date if range is long enough
    if ($max_x - $min_x > 86400 * 5) {
        $mid_x = (int) (($min_x + $max_x) / 2);
        $mid_label = date('M d', $mid_x);
        $mid_px = $plot['left'] + (int) ($plot['width'] / 2) - (int) (strlen($mid_label) * imagefontwidth(1) / 2);
        imagestring($img, 1, $mid_px, $plot['bottom'] + 4, $mid_label, $colors['muted']);
    }

    // Draw summary
    $summary = 'Total: ' . dedumsoft_format_value($total) . ' | Prom: ' . dedumsoft_format_value($avg) . ' | Pts: ' . count($points);
    imagestring($img, 1, $plot['left'], $plot['bottom'] + 18, $summary, $colors['muted']);

    dedumsoft_output_image($img);
}

/**
 * Dibuja los ejes X e Y del gráfico.
 * 
 * @param resource|GdImage $img Recurso de imagen
 * @param array $plot Coordenadas del área de dibujo
 * @param array $colors Paleta de colores
 * @return void
 */
function dedumsoft_draw_axes($img, array $plot, array $colors): void
{
    // Eje Y (vertical)
    imageline($img, $plot['left'], $plot['top'], $plot['left'], $plot['bottom'], $colors['axis']);
    // Eje X (horizontal)
    imageline($img, $plot['left'], $plot['bottom'], $plot['right'], $plot['bottom'], $colors['axis']);
}

/**
 * Dibuja la cuadrícula horizontal con etiquetas de valores.
 * 
 * @param resource|GdImage $img Recurso de imagen
 * @param array $plot Coordenadas del área de dibujo
 * @param array $colors Paleta de colores
 * @param float $max_value Valor máximo del eje Y
 * @return void
 */
function dedumsoft_draw_grid($img, array $plot, array $colors, float $max_value): void
{
    $grid_lines = 4;  // Número de líneas horizontales
    $step = $plot['height'] / $grid_lines;
    $value_step = $max_value / $grid_lines;

    for ($i = 0; $i <= $grid_lines; $i++) {
        $y = $plot['bottom'] - (int) ($step * $i);

        // Dibujar línea de cuadrícula (excepto en la base)
        if ($i > 0) {
            imageline($img, $plot['left'] + 1, $y, $plot['right'], $y, $colors['grid']);
        }

        // Etiqueta del valor en el eje Y
        $label_value = $value_step * $i;
        $label = dedumsoft_format_value($label_value);
        $label_width = strlen($label) * imagefontwidth(1);
        $label_x = $plot['left'] - $label_width - 4;
        if ($label_x < 2) {
            $label_x = 2;
        }
        imagestring($img, 1, $label_x, $y - 4, $label, $colors['muted']);
    }
}

/**
 * Formatea un valor numérico para visualización compacta.
 * 
 * Usa sufijos para valores grandes:
 * - M: millones (ej: 1.5M)
 * - K: miles (ej: 2.3K)
 * 
 * @param float $value Valor a formatear
 * @return string Valor formateado
 */
function dedumsoft_format_value(float $value): string
{
    if ($value >= 1000000) {
        return number_format($value / 1000000, 1) . 'M';
    }
    if ($value >= 1000) {
        return number_format($value / 1000, 1) . 'K';
    }
    if ($value == (int) $value) {
        return (string) (int) $value;
    }
    return number_format($value, 1);
}

/**
 * Trunca una etiqueta a una longitud máxima.
 * 
 * @param string $label Etiqueta original
 * @param int $max_len Longitud máxima permitida
 * @return string Etiqueta truncada con '.' si excede el límite
 */
function dedumsoft_trim_label(string $label, int $max_len): string
{
    $label = trim($label);
    if (strlen($label) <= $max_len) {
        return $label;
    }
    return substr($label, 0, $max_len - 1) . '.';
}

/**
 * Emite la imagen PNG al navegador con headers de caché.
 * 
 * Si existe un ETag en $GLOBALS, configura caché por 5 minutos.
 * De lo contrario, desactiva caché.
 * 
 * @param resource|GdImage $img Recurso de imagen a emitir
 * @return void Termina con exit()
 */
function dedumsoft_output_image($img): void
{
    if (isset($GLOBALS['etag'])) {
        header('ETag: ' . $GLOBALS['etag']);
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    header('Content-Type: image/png');
    imagepng($img, null, 6); // Compresión nivel 6 (balance tamaño/velocidad)
    exit;
}
