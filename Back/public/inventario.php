<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$oro_tipo = $_GET['oro_tipo'] ?? '';
$insumo_categoria = $_GET['insumo_categoria'] ?? '';
$insumo_stock_bajo = isset($_GET['insumo_stock_bajo']) && $_GET['insumo_stock_bajo'] !== '0';
$maq_estado = $_GET['maq_estado'] ?? '';
$oro_rows = [];
$insumo_rows = [];
$maq_rows = [];
$categoria_options = [];

function format_categoria_badge($cat) {
    $cat = trim((string)$cat);
    if ($cat === '') return '';
    $key = strtolower($cat);
    $label = ucwords(str_replace('_', ' ', $cat));
    $cls = 'ds-badge--neutral';
    if (strpos($key, 'piedra') !== false || strpos($key, 'gema') !== false) $cls = 'ds-badge--info';
    elseif (strpos($key, 'metal') !== false || strpos($key, 'oro') !== false) $cls = 'ds-badge--warning';
    elseif (strpos($key, 'herramienta') !== false || strpos($key, 'equipo') !== false) $cls = 'ds-badge--muted';
    elseif (strpos($key, 'quimico') !== false || strpos($key, 'limpieza') !== false) $cls = 'ds-badge--danger';
    elseif (strpos($key, 'empaque') !== false || strpos($key, 'caja') !== false) $cls = 'ds-badge--success';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function format_maq_tipo_badge($tipo) {
    $tipo = trim((string)$tipo);
    if ($tipo === '') return '';
    $key = strtolower($tipo);
    $label = ucwords(str_replace('_', ' ', $tipo));
    $cls = 'ds-badge--neutral';
    if (strpos($key, 'corte') !== false || strpos($key, 'sierra') !== false) $cls = 'ds-badge--danger';
    elseif (strpos($key, 'pulido') !== false || strpos($key, 'acabado') !== false) $cls = 'ds-badge--info';
    elseif (strpos($key, 'fundicion') !== false || strpos($key, 'horno') !== false) $cls = 'ds-badge--warning';
    elseif (strpos($key, 'soldadura') !== false) $cls = 'ds-badge--success';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

function format_maq_estado_badge($estado) {
    $estado = trim((string)$estado);
    if ($estado === '') return '';
    $key = strtolower($estado);
    $label = ucwords(str_replace('_', ' ', $estado));
    $cls = 'ds-badge--neutral';
    if ($key === 'operativa') $cls = 'ds-badge--success';
    elseif ($key === 'mantenimiento') $cls = 'ds-badge--warning';
    elseif ($key === 'averiada') $cls = 'ds-badge--danger';
    elseif ($key === 'fuera_servicio' || strpos($key, 'fuera') !== false) $cls = 'ds-badge--muted';
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

try {
    $stmt = $connLogic->query(
        "SELECT DISTINCT categoria FROM inventario_insumos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
    );
    $categoria_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('inventario categorias error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $oro_tipo !== '' ? $oro_tipo : null, $oro_tipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $oro_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, categoria, cantidad, proveedor_nombre FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_categoria !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':stock_bajo', $insumo_stock_bajo, PDO::PARAM_BOOL);
        $stmt->execute();
        $insumo_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo, estado, ubicacion_id, ubicacion_nombre FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $maq_estado !== '' ? $maq_estado : null, $maq_estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $maq_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Inventario</h1>
        <p>Control de metales, insumos y maquinaria</p>
    </div>

    <div class="card" id="inv-oro">
        <strong>Inventario de oro</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="oro-tipo">Tipo</label>
                <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="10k" <?php echo $oro_tipo === '10k' ? 'selected' : ''; ?>>10k</option>
                    <option value="14k" <?php echo $oro_tipo === '14k' ? 'selected' : ''; ?>>14k</option>
                    <option value="18k" <?php echo $oro_tipo === '18k' ? 'selected' : ''; ?>>18k</option>
                    <option value="22k" <?php echo $oro_tipo === '22k' ? 'selected' : ''; ?>>22k</option>
                    <option value="24k" <?php echo $oro_tipo === '24k' ? 'selected' : ''; ?>>24k</option>
                </select>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario.php#inv-oro" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="oro-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Peso</th>
                        <th>Precio</th>
                        <th>Proveedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($oro_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['tipo_oro']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['peso_gramos']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['precio_gramo']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['proveedor_nombre'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-insumos">
        <strong>Insumos</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-insumos" class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label muted" for="insumo-categoria">Categoria</label>
                <select id="insumo-categoria" name="insumo_categoria" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                    <option value="<?php echo htmlspecialchars((string) $categoria); ?>"
                        <?php echo $insumo_categoria === $categoria ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $categoria); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo" name="insumo_stock_bajo"
                    value="1" <?php echo $insumo_stock_bajo ? 'checked' : ''; ?>>
                <label class="form-check-label muted" for="insumo-stock-bajo">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario.php#inv-insumos" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="insumos-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoria</th>
                        <th>Cantidad</th>
                        <th>Proveedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($insumo_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                        <td><?php echo format_categoria_badge($row['categoria']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['proveedor_nombre'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-maquinaria">
        <strong>Maquinaria</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-maquinaria" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="maq-estado">Estado</label>
                <select id="maq-estado" name="maq_estado" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="operativa" <?php echo $maq_estado === 'operativa' ? 'selected' : ''; ?>>Operativa
                    </option>
                    <option value="mantenimiento" <?php echo $maq_estado === 'mantenimiento' ? 'selected' : ''; ?>>
                        Mantenimiento</option>
                    <option value="averiada" <?php echo $maq_estado === 'averiada' ? 'selected' : ''; ?>>Averiada
                    </option>
                    <option value="fuera_servicio" <?php echo $maq_estado === 'fuera_servicio' ? 'selected' : ''; ?>>
                        Fuera
                        de servicio</option>
                </select>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario.php#inv-maquinaria" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="maq-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Ubicacion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($maq_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                        <td><?php echo format_maq_tipo_badge($row['tipo']); ?></td>
                        <td><?php echo format_maq_estado_badge($row['estado']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['ubicacion_nombre'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php if (!$legacy): ?>
<script>
function formatCategoria(cat) {
    if (!cat) return '';
    var key = cat.toLowerCase();
    var label = cat.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    var cls = 'ds-badge--neutral';
    if (key.indexOf('piedra') > -1 || key.indexOf('gema') > -1) cls = 'ds-badge--info';
    else if (key.indexOf('metal') > -1 || key.indexOf('oro') > -1) cls = 'ds-badge--warning';
    else if (key.indexOf('herramienta') > -1 || key.indexOf('equipo') > -1) cls = 'ds-badge--muted';
    else if (key.indexOf('quimico') > -1 || key.indexOf('limpieza') > -1) cls = 'ds-badge--danger';
    else if (key.indexOf('empaque') > -1 || key.indexOf('caja') > -1) cls = 'ds-badge--success';
    return '<span class="ds-badge ' + cls + '">' + label + '</span>';
}
function formatMaqTipo(tipo) {
    if (!tipo) return '';
    var key = tipo.toLowerCase();
    var label = tipo.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    var cls = 'ds-badge--neutral';
    if (key.indexOf('corte') > -1 || key.indexOf('sierra') > -1) cls = 'ds-badge--danger';
    else if (key.indexOf('pulido') > -1 || key.indexOf('acabado') > -1) cls = 'ds-badge--info';
    else if (key.indexOf('fundicion') > -1 || key.indexOf('horno') > -1) cls = 'ds-badge--warning';
    else if (key.indexOf('soldadura') > -1) cls = 'ds-badge--success';
    return '<span class="ds-badge ' + cls + '">' + label + '</span>';
}
function formatMaqEstado(estado) {
    if (!estado) return '';
    var key = estado.toLowerCase();
    var label = estado.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    var cls = 'ds-badge--neutral';
    if (key === 'operativa') cls = 'ds-badge--success';
    else if (key === 'mantenimiento') cls = 'ds-badge--warning';
    else if (key === 'averiada') cls = 'ds-badge--danger';
    else if (key === 'fuera_servicio' || key.indexOf('fuera') > -1) cls = 'ds-badge--muted';
    return '<span class="ds-badge ' + cls + '">' + label + '</span>';
}
$(function() {
    var dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    $.getJSON('../api/inventario_oro.php?limit=100&offset=0', function(data) {
        $('#oro-table').DataTable({
            data: data.DATOS || [],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'tipo_oro'
                },
                {
                    data: 'peso_gramos'
                },
                {
                    data: 'precio_gramo'
                },
                {
                    data: 'proveedor_nombre',
                    defaultContent: ''
                }
            ],
            language: dtLang
        });
    });
    $.getJSON('../api/inventario_insumos.php?limit=100&offset=0', function(data) {
        $('#insumos-table').DataTable({
            data: data.DATOS || [],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'categoria',
                    render: formatCategoria
                },
                {
                    data: 'cantidad'
                },
                {
                    data: 'proveedor_nombre',
                    defaultContent: ''
                }
            ],
            language: dtLang
        });
    });
    $.getJSON('../api/inventario_maquinaria.php?limit=100&offset=0', function(data) {
        $('#maq-table').DataTable({
            data: data.DATOS || [],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'tipo',
                    render: formatMaqTipo
                },
                {
                    data: 'estado',
                    render: formatMaqEstado
                },
                {
                    data: 'ubicacion_nombre',
                    defaultContent: ''
                }
            ],
            language: dtLang
        });
    });
});
</script>
<?php elseif ($legacy): ?>
<script>
if (window.DedumTableSort) {
    DedumTableSort.init('oro-table');
    DedumTableSort.init('insumos-table');
    DedumTableSort.init('maq-table');
}
</script>
<?php endif; ?>