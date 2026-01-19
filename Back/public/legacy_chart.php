<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

$user = get_session_user();
if (!$user) {
    dedumsoft_output_empty_png();
}

$chart = strtolower(trim($_GET['chart'] ?? ''));
$width = dedumsoft_clamp((int) ($_GET['w'] ?? 580), 320, 800);
$height = dedumsoft_clamp((int) ($_GET['h'] ?? 240), 180, 400);

$default_desde = date('Y-m-01');
$default_hasta = date('Y-m-t');
$desde = dedumsoft_parse_date($_GET['desde'] ?? $default_desde, $default_desde);
$hasta = dedumsoft_parse_date($_GET['hasta'] ?? $default_hasta, $default_hasta);
if ($desde > $hasta) {
    $tmp = $desde;
    $desde = $hasta;
    $hasta = $tmp;
}

if (!function_exists('imagecreatetruecolor')) {
    dedumsoft_output_empty_png();
}

try {
    switch ($chart) {
        case 'produccion':
            $rows = dedumsoft_query(
                'SELECT estado, COUNT(*) AS total FROM fun_reporte_produccion(:desde, :hasta) GROUP BY estado ORDER BY estado',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_bar_chart($rows, 'estado', 'total', 'Produccion', $width, $height);
            break;
        case 'inventario':
            $rows = dedumsoft_query(
                'SELECT tipo, COUNT(*) AS total FROM fun_reporte_inventario() GROUP BY tipo ORDER BY tipo',
                []
            );
            dedumsoft_render_bar_chart($rows, 'tipo', 'total', 'Inventario', $width, $height);
            break;
        case 'eficiencia':
            $rows = dedumsoft_query(
                'SELECT artesano, piezas FROM fun_reporte_eficiencia_artesanos(:desde, :hasta) ORDER BY piezas DESC',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_bar_chart($rows, 'artesano', 'piezas', 'Eficiencia', $width, $height);
            break;
        case 'materiales':
            $rows = dedumsoft_query(
                'SELECT tipo_material, SUM(costo_total) AS total FROM fun_reporte_uso_materiales(:desde, :hasta) GROUP BY tipo_material ORDER BY tipo_material',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_bar_chart($rows, 'tipo_material', 'total', 'Materiales', $width, $height);
            break;
        case 'ventas':
            $rows = dedumsoft_query(
                'SELECT fecha_venta::date AS dia, SUM(precio_venta) AS total FROM fun_reporte_ventas(:desde, :hasta) GROUP BY dia ORDER BY dia',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_line_chart($rows, 'dia', 'total', 'Ventas', $width, $height);
            break;
        case 'compras':
            $rows = dedumsoft_query(
                'SELECT tipo_inventario, SUM(cantidad_total) AS total FROM fun_reporte_compras(:desde, :hasta) GROUP BY tipo_inventario ORDER BY tipo_inventario',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_bar_chart($rows, 'tipo_inventario', 'total', 'Compras', $width, $height);
            break;
        case 'usuarios':
            $rows = dedumsoft_query(
                'SELECT rol, COUNT(*) AS total FROM seguridad.fun_reporte_usuarios() GROUP BY rol ORDER BY rol',
                []
            );
            dedumsoft_render_bar_chart($rows, 'rol', 'total', 'Usuarios', $width, $height);
            break;
        case 'ventas_mes':
            $rows = dedumsoft_query(
                'SELECT fecha_venta::date AS dia, SUM(precio_venta_real) AS total FROM creaciones_terminadas WHERE vendida = TRUE AND fecha_venta::date BETWEEN :desde AND :hasta GROUP BY dia ORDER BY dia',
                [':desde' => $desde, ':hasta' => $hasta]
            );
            dedumsoft_render_line_chart($rows, 'dia', 'total', 'Ventas del mes', $width, $height);
            break;
        case 'ordenes_estado':
            $rows = dedumsoft_query(
                'SELECT COALESCE(eo.nombre, \'sin_estado\') AS estado, COUNT(*) AS total FROM ordenes_produccion op LEFT JOIN estados_orden eo ON op.estado_id = eo.id GROUP BY COALESCE(eo.nombre, \'sin_estado\') ORDER BY COALESCE(eo.nombre, \'sin_estado\')',
                []
            );
            dedumsoft_render_bar_chart($rows, 'estado', 'total', 'Ordenes', $width, $height);
            break;
        default:
            dedumsoft_render_empty_chart('Sin datos', $width, $height);
            break;
    }
} catch (PDOException $e) {
    error_log('legacy_chart error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    dedumsoft_render_empty_chart('Sin datos', $width, $height);
}

function dedumsoft_query(string $sql, array $params): array
{
    $conn = $GLOBALS['connLogic'] ?? null;
    if (!$conn instanceof PDO) {
        return [];
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

function dedumsoft_parse_date(string $value, string $fallback): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if ($dt === false) {
        return $fallback;
    }
    return $dt->format('Y-m-d');
}

function dedumsoft_output_empty_png(): void
{
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');
    exit;
}

function dedumsoft_create_canvas(int $width, int $height, array &$colors)
{
    $img = imagecreatetruecolor($width, $height);
    $colors = [
        'bg' => imagecolorallocate($img, 255, 255, 255),
        'axis' => imagecolorallocate($img, 90, 90, 90),
        'grid' => imagecolorallocate($img, 220, 220, 220),
        'bar' => imagecolorallocate($img, 212, 175, 55),
        'bar_alt' => imagecolorallocate($img, 180, 140, 40),
        'line' => imagecolorallocate($img, 212, 175, 55),
        'fill' => imagecolorallocate($img, 245, 233, 200),
        'text' => imagecolorallocate($img, 50, 50, 50),
        'value' => imagecolorallocate($img, 30, 30, 30),
        'muted' => imagecolorallocate($img, 120, 120, 120)
    ];
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $colors['bg']);
    if (function_exists('imageantialias')) {
        imageantialias($img, true);
    }
    return $img;
}

function dedumsoft_plot_area(int $width, int $height): array
{
    $left = 50;
    $right = 12;
    $top = 26;
    $bottom = 42;
    return [
        'left' => $left,
        'top' => $top,
        'right' => $width - $right,
        'bottom' => $height - $bottom,
        'width' => $width - $left - $right,
        'height' => $height - $top - $bottom
    ];
}

function dedumsoft_draw_title($img, string $title, array $colors, int $width): void
{
    $title = trim($title);
    if ($title === '') {
        return;
    }
    $font = 3;
    $x = (int) (($width - (strlen($title) * imagefontwidth($font))) / 2);
    if ($x < 4) {
        $x = 4;
    }
    imagestring($img, $font, $x, 6, $title, $colors['text']);
}

function dedumsoft_render_empty_chart(string $title, int $width, int $height): void
{
    $colors = [];
    $img = dedumsoft_create_canvas($width, $height, $colors);
    dedumsoft_draw_title($img, $title, $colors, $width);
    imagestring($img, 3, 20, (int) ($height / 2) - 6, 'Sin datos', $colors['text']);
    dedumsoft_output_image($img);
}

function dedumsoft_render_bar_chart(array $rows, string $label_key, string $value_key, string $title, int $width, int $height): void
{
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

    for ($i = 0; $i < $count; $i++) {
        $bar_height = (int) (($values[$i] / $max) * $plot['height']);
        if ($bar_height < 1 && $values[$i] > 0) {
            $bar_height = 1;
        }
        $x1 = $plot['left'] + $gap + ($bar_width + $gap) * $i;
        $y1 = $plot['bottom'] - $bar_height;
        $x2 = $x1 + $bar_width;

        // Draw bar with slight 3D effect
        imagefilledrectangle($img, $x1, $y1, $x2, $plot['bottom'] - 1, $colors['bar']);
        imageline($img, $x1, $y1, $x2, $y1, $colors['bar_alt']);

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

function dedumsoft_render_line_chart(array $rows, string $label_key, string $value_key, string $title, int $width, int $height): void
{
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
    if (!$points) {
        dedumsoft_render_empty_chart($title, $width, $height);
        return;
    }

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
            imageline($img, $prev_x, $prev_y, $px, $py, $colors['line']);
        }
        imagefilledellipse($img, $px, $py, 6, 6, $colors['line']);
        imageellipse($img, $px, $py, 6, 6, $colors['bar_alt']);

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

function dedumsoft_draw_axes($img, array $plot, array $colors): void
{
    imageline($img, $plot['left'], $plot['top'], $plot['left'], $plot['bottom'], $colors['axis']);
    imageline($img, $plot['left'], $plot['bottom'], $plot['right'], $plot['bottom'], $colors['axis']);
}

function dedumsoft_draw_grid($img, array $plot, array $colors, float $max_value): void
{
    $grid_lines = 4;
    $step = $plot['height'] / $grid_lines;
    $value_step = $max_value / $grid_lines;

    for ($i = 0; $i <= $grid_lines; $i++) {
        $y = $plot['bottom'] - (int) ($step * $i);
        // Draw horizontal grid line
        if ($i > 0) {
            imagedashedline($img, $plot['left'] + 1, $y, $plot['right'], $y, $colors['grid']);
        }
        // Draw Y-axis label
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

function dedumsoft_trim_label(string $label, int $max_len): string
{
    $label = trim($label);
    if (strlen($label) <= $max_len) {
        return $label;
    }
    return substr($label, 0, $max_len - 1) . '.';
}

function dedumsoft_output_image($img): void
{
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    imagepng($img);
    imagedestroy($img);
    exit;
}
