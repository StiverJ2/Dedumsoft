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
$proveedor_options = [];
$ubicacion_options = [];

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

try {
    $stmt = $connLogic->query("SELECT id, nombre, tipo FROM proveedores WHERE activo = TRUE ORDER BY nombre");
    $proveedor_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

try {
    $stmt = $connLogic->query("SELECT id, nombre FROM ubicaciones WHERE activo = TRUE ORDER BY nombre");
    $ubicacion_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario ubicaciones error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

$tipo_maquinaria_options = [];
try {
    $stmt = $connLogic->query("SELECT id, codigo, nombre FROM tipos_maquinaria WHERE activo = TRUE ORDER BY nombre");
    $tipo_maquinaria_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario tipos_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $oro_tipo !== '' ? $oro_tipo : null, $oro_tipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $oro_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, categoria, cantidad, proveedor_nombre FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_categoria !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':stock_bajo', $insumo_stock_bajo, PDO::PARAM_BOOL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $insumo_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo_maquinaria_id, tipo_nombre, estado, ubicacion_id, ubicacion_nombre FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $maq_estado !== '' ? $maq_estado : null, $maq_estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
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
        <div class="ds-toolbar">
            <strong>Inventario de oro</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-oro">+ Nuevo Oro</button>
            </div>
        </div>
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
                        <th>Acciones</th>
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
                        <td class="ds-actions-col"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-insumos">
        <div class="ds-toolbar">
            <strong>Insumos</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-insumo">+ Nuevo Insumo</button>
            </div>
        </div>
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
                        <th>Acciones</th>
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
                        <td class="ds-actions-col"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-maquinaria">
        <div class="ds-toolbar">
            <strong>Maquinaria</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-maquinaria">+ Nueva Maquinaria</button>
            </div>
        </div>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($maq_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                        <td><?php echo format_maq_tipo_badge($row['tipo_nombre'] ?? ''); ?></td>
                        <td><?php echo format_maq_estado_badge($row['estado']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['ubicacion_nombre'] ?? '')); ?></td>
                        <td class="ds-actions-col"></td>
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
    var label = cat.replace(/_/g, ' ').replace(/\b\w/g, function(c) {
        return c.toUpperCase();
    });
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
    var label = tipo.replace(/_/g, ' ').replace(/\b\w/g, function(c) {
        return c.toUpperCase();
    });
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
    var label = estado.replace(/_/g, ' ').replace(/\b\w/g, function(c) {
        return c.toUpperCase();
    });
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
    var oroTable, insumosTable, maqTable;
    var proveedoresCache = [];
    var ubicacionesCache = [];
    var tipoMaquinariaCache = [];

    // Cargar proveedores y ubicaciones para selects
    $.getJSON('../api/proveedores.php?limit=500', function(res) {
        proveedoresCache = (res.DATOS || []).map(function(p) {
            return {
                value: p.id,
                label: p.nombre + ' (' + p.tipo + ')'
            };
        });
    });
    $.getJSON('../api/ubicaciones.php?limit=500', function(res) {
        ubicacionesCache = (res.DATOS || []).map(function(u) {
            return {
                value: u.id,
                label: u.nombre
            };
        });
    });
    $.getJSON('../api/tipos_maquinaria.php', function(res) {
        tipoMaquinariaCache = (res.DATOS || []).map(function(t) {
            return {
                value: t.id,
                label: t.nombre
            };
        });
    });

    var oroTipoOptions = [{
            value: '10k',
            label: '10k'
        },
        {
            value: '14k',
            label: '14k'
        },
        {
            value: '18k',
            label: '18k'
        },
        {
            value: '22k',
            label: '22k'
        },
        {
            value: '24k',
            label: '24k'
        }
    ];
    var maqEstadoOptions = [{
            value: 'operativa',
            label: 'Operativa'
        },
        {
            value: 'mantenimiento',
            label: 'Mantenimiento'
        },
        {
            value: 'averiada',
            label: 'Averiada'
        },
        {
            value: 'fuera_servicio',
            label: 'Fuera de servicio'
        }
    ];

    // ===================== ORO =====================
    function buildOroForm(data) {
        data = data || {};
        var provOpts = [{
            value: '',
            label: '-- Sin proveedor --'
        }].concat(
            proveedoresCache.filter(function(p) {
                return p.label.indexOf('(oro)') > -1 || !data.id;
            })
        );
        return DsCrud.field({
                name: 'tipo_oro',
                label: 'Tipo',
                type: 'select',
                value: data.tipo_oro,
                options: oroTipoOptions,
                required: true
            }) +
            DsCrud.field({
                name: 'peso_gramos',
                label: 'Peso (gramos)',
                type: 'number',
                value: data.peso_gramos,
                required: true,
                attrs: 'step="0.01" min="0"'
            }) +
            DsCrud.field({
                name: 'precio_gramo',
                label: 'Precio/gramo',
                type: 'number',
                value: data.precio_gramo,
                required: true,
                attrs: 'step="0.01" min="0"'
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    }

    function openOroCreate() {
        DsCrud.openModal({
            title: 'Nuevo Inventario Oro',
            body: '<form id="frm-oro">' + buildOroForm() + '</form>',
            onSave: function(m) {
                var f = m.querySelector('#frm-oro');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                var fd = new FormData(f),
                    payload = {};
                fd.forEach(function(v, k) {
                    payload[k] = v;
                });
                DsCrud.api('../api/inventario_oro.php', 'POST', payload, function() {
                    DsCrud.toast('Oro creado', 'success');
                    oroTable.ajax.reload();
                    DsCrud.closeModal();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openOroEdit(row) {
        DsCrud.api('../api/inventario_oro.php?id=' + row.id, 'GET', null, function(res) {
            var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Oro #' + d.id,
                body: '<form id="frm-oro">' + buildOroForm(d) + '</form>',
                onSave: function(m) {
                    var f = m.querySelector('#frm-oro');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    var fd = new FormData(f),
                        payload = {
                            id: d.id
                        };
                    fd.forEach(function(v, k) {
                        payload[k] = v;
                    });
                    DsCrud.api('../api/inventario_oro.php', 'PUT', payload, function() {
                        DsCrud.toast('Oro actualizado', 'success');
                        oroTable.ajax.reload();
                        DsCrud.closeModal();
                    }, function(e) {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        });
    }

    function openOroDelete(row) {
        DsCrud.confirm('¿Eliminar registro de oro #' + row.id + '?', function() {
            DsCrud.api('../api/inventario_oro.php', 'DELETE', {
                id: row.id
            }, function() {
                DsCrud.toast('Oro eliminado', 'success');
                oroTable.ajax.reload();
            }, function(e) {
                DsCrud.toast(e, 'error');
            });
        });
    }
    oroTable = $('#oro-table').DataTable({
        ajax: {
            url: '../api/inventario_oro.php?limit=500',
            dataSrc: 'DATOS'
        },
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
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') return '';
                    return DsCrud.actionButtons(row.id);
                }
            }
        ],
        language: dtLang
    });
    $('#btn-add-oro').on('click', openOroCreate);
    $('#oro-table').on('click', '.ds-action-btn[data-action="edit"]', function() {
        openOroEdit(oroTable.row($(this).closest('tr')).data());
    });
    $('#oro-table').on('click', '.ds-action-btn[data-action="delete"]', function() {
        openOroDelete(oroTable.row($(this).closest('tr')).data());
    });

    // ===================== INSUMOS =====================
    function buildInsumoForm(data) {
        data = data || {};
        var provOpts = [{
            value: '',
            label: '-- Sin proveedor --'
        }].concat(
            proveedoresCache.filter(function(p) {
                return p.label.indexOf('(insumos)') > -1 || !data.id;
            })
        );
        return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre,
                required: true
            }) +
            DsCrud.field({
                name: 'categoria',
                label: 'Categoría',
                value: data.categoria
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
                type: 'number',
                value: data.cantidad,
                required: true,
                attrs: 'min="0"'
            }) +
            DsCrud.field({
                name: 'stock_minimo',
                label: 'Stock mínimo',
                type: 'number',
                value: data.stock_minimo,
                attrs: 'min="0"'
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    }

    function openInsumoCreate() {
        DsCrud.openModal({
            title: 'Nuevo Insumo',
            body: '<form id="frm-insumo">' + buildInsumoForm() + '</form>',
            onSave: function(m) {
                var f = m.querySelector('#frm-insumo');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                var fd = new FormData(f),
                    payload = {};
                fd.forEach(function(v, k) {
                    payload[k] = v;
                });
                DsCrud.api('../api/inventario_insumos.php', 'POST', payload, function() {
                    DsCrud.toast('Insumo creado', 'success');
                    insumosTable.ajax.reload();
                    DsCrud.closeModal();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openInsumoEdit(row) {
        DsCrud.api('../api/inventario_insumos.php?id=' + row.id, 'GET', null, function(res) {
            var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Insumo #' + d.id,
                body: '<form id="frm-insumo">' + buildInsumoForm(d) + '</form>',
                onSave: function(m) {
                    var f = m.querySelector('#frm-insumo');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    var fd = new FormData(f),
                        payload = {
                            id: d.id
                        };
                    fd.forEach(function(v, k) {
                        payload[k] = v;
                    });
                    DsCrud.api('../api/inventario_insumos.php', 'PUT', payload, function() {
                        DsCrud.toast('Insumo actualizado', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    }, function(e) {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        });
    }

    function openInsumoDelete(row) {
        DsCrud.confirm('¿Eliminar insumo "' + row.nombre + '"?', function() {
            DsCrud.api('../api/inventario_insumos.php', 'DELETE', {
                id: row.id
            }, function() {
                DsCrud.toast('Insumo eliminado', 'success');
                insumosTable.ajax.reload();
            }, function(e) {
                DsCrud.toast(e, 'error');
            });
        });
    }
    insumosTable = $('#insumos-table').DataTable({
        ajax: {
            url: '../api/inventario_insumos.php?limit=500',
            dataSrc: 'DATOS'
        },
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
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') return '';
                    return DsCrud.actionButtons(row.id);
                }
            }
        ],
        language: dtLang
    });
    $('#btn-add-insumo').on('click', openInsumoCreate);
    $('#insumos-table').on('click', '.ds-action-btn[data-action="edit"]', function() {
        openInsumoEdit(insumosTable.row($(this).closest('tr')).data());
    });
    $('#insumos-table').on('click', '.ds-action-btn[data-action="delete"]', function() {
        openInsumoDelete(insumosTable.row($(this).closest('tr')).data());
    });

    // ===================== MAQUINARIA =====================
    function buildMaqForm(data) {
        data = data || {};
        var provOpts = [{
            value: '',
            label: '-- Sin proveedor --'
        }].concat(
            proveedoresCache.filter(function(p) {
                return p.label.indexOf('(maquinaria)') > -1 || !data.id;
            })
        );
        var ubOpts = [{
            value: '',
            label: '-- Sin ubicación --'
        }].concat(ubicacionesCache);
        var tipoOpts = [{
            value: '',
            label: '-- Seleccione tipo --'
        }].concat(tipoMaquinariaCache);
        return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre,
                required: true
            }) +
            DsCrud.field({
                name: 'tipo_maquinaria_id',
                label: 'Tipo',
                type: 'select',
                value: data.tipo_maquinaria_id,
                options: tipoOpts,
                required: true
            }) +
            DsCrud.field({
                name: 'estado',
                label: 'Estado',
                type: 'select',
                value: data.estado,
                options: maqEstadoOptions,
                required: true
            }) +
            DsCrud.field({
                name: 'ubicacion_id',
                label: 'Ubicación',
                type: 'select',
                value: data.ubicacion_id,
                options: ubOpts
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    }

    function openMaqCreate() {
        DsCrud.openModal({
            title: 'Nueva Maquinaria',
            body: '<form id="frm-maq">' + buildMaqForm() + '</form>',
            onSave: function(m) {
                var f = m.querySelector('#frm-maq');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                var fd = new FormData(f),
                    payload = {};
                fd.forEach(function(v, k) {
                    payload[k] = v;
                });
                DsCrud.api('../api/inventario_maquinaria.php', 'POST', payload, function() {
                    DsCrud.toast('Maquinaria creada', 'success');
                    maqTable.ajax.reload();
                    DsCrud.closeModal();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openMaqEdit(row) {
        DsCrud.api('../api/inventario_maquinaria.php?id=' + row.id, 'GET', null, function(res) {
            var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Maquinaria #' + d.id,
                body: '<form id="frm-maq">' + buildMaqForm(d) + '</form>',
                onSave: function(m) {
                    var f = m.querySelector('#frm-maq');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    var fd = new FormData(f),
                        payload = {
                            id: d.id
                        };
                    fd.forEach(function(v, k) {
                        payload[k] = v;
                    });
                    DsCrud.api('../api/inventario_maquinaria.php', 'PUT', payload,
                        function() {
                            DsCrud.toast('Maquinaria actualizada', 'success');
                            maqTable.ajax.reload();
                            DsCrud.closeModal();
                        },
                        function(e) {
                            DsCrud.toast(e, 'error');
                        });
                }
            });
        });
    }

    function openMaqDelete(row) {
        DsCrud.confirm('¿Eliminar maquinaria "' + row.nombre + '"?', function() {
            DsCrud.api('../api/inventario_maquinaria.php', 'DELETE', {
                id: row.id
            }, function() {
                DsCrud.toast('Maquinaria eliminada', 'success');
                maqTable.ajax.reload();
            }, function(e) {
                DsCrud.toast(e, 'error');
            });
        });
    }
    maqTable = $('#maq-table').DataTable({
        ajax: {
            url: '../api/inventario_maquinaria.php?limit=500',
            dataSrc: 'DATOS'
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'nombre'
            },
            {
                data: 'tipo_nombre',
                render: formatMaqTipo
            },
            {
                data: 'estado',
                render: formatMaqEstado
            },
            {
                data: 'ubicacion_nombre',
                defaultContent: ''
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (type !== 'display') return '';
                    return DsCrud.actionButtons(row.id);
                }
            }
        ],
        language: dtLang
    });
    $('#btn-add-maquinaria').on('click', openMaqCreate);
    $('#maq-table').on('click', '.ds-action-btn[data-action="edit"]', function() {
        openMaqEdit(maqTable.row($(this).closest('tr')).data());
    });
    $('#maq-table').on('click', '.ds-action-btn[data-action="delete"]', function() {
        openMaqDelete(maqTable.row($(this).closest('tr')).data());
    });
});
</script>
<?php elseif ($legacy): ?>
<script>
(function() {
    if (window.DedumTableSort) {
        DedumTableSort.init('oro-table');
        DedumTableSort.init('insumos-table');
        DedumTableSort.init('maq-table');
    }

    // Datos de la BD inyectados por PHP
    var categoriaOptions = <?php echo json_encode(array_map(function($c) {
        return ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))];
    }, $categoria_options)); ?>;

    var proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $proveedor_options)
    )); ?>;

    var ubicacionOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin ubicación --']],
        array_map(function($u) {
            return ['value' => $u['id'], 'label' => $u['nombre']];
        }, $ubicacion_options)
    )); ?>;

    var tipoMaquinariaOptions = <?php echo json_encode(array_map(function($t) {
        return ['value' => $t['id'], 'label' => $t['nombre']];
    }, $tipo_maquinaria_options)); ?>;

    var oroTipoOptions = [{
            value: '10k',
            label: '10k'
        }, {
            value: '14k',
            label: '14k'
        }, {
            value: '18k',
            label: '18k'
        },
        {
            value: '22k',
            label: '22k'
        }, {
            value: '24k',
            label: '24k'
        }
    ];

    var maqEstadoOptions = [{
            value: 'operativa',
            label: 'Operativa'
        }, {
            value: 'mantenimiento',
            label: 'Mantenimiento'
        },
        {
            value: 'averiada',
            label: 'Averiada'
        }, {
            value: 'fuera_servicio',
            label: 'Fuera de servicio'
        }
    ];

    function esc(s) {
        if (s === null || s === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(s)));
        return div.innerHTML;
    }

    function selectHtml(name, value, options, req) {
        var h = '<select name="' + name + '" id="field-' + name +
            '" style="width:100%;padding:6px;font-size:14px;"' + (req ? ' required' : '') + '>';
        for (var i = 0; i < options.length; i++) {
            var sel = (String(options[i].value) == String(value)) ? ' selected' : '';
            h += '<option value="' + esc(options[i].value) + '"' + sel + '>' + esc(options[i].label) + '</option>';
        }
        h += '</select>';
        return h;
    }

    // ========== ORO ==========
    function buildOroFormHtml(d) {
        d = d || {};
        // Filtrar proveedores de oro
        var oroProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(oro)') > -1) {
                oroProveedores.push(proveedorOptions[i]);
            }
        }
        if (oroProveedores.length === 1) oroProveedores = proveedorOptions; // si no hay de oro, mostrar todos

        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
            selectHtml('tipo_oro', d.tipo_oro || '10k', oroTipoOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Peso (gramos) <span style="color:red">*</span></label><input type="text" name="peso_gramos" value="' +
            esc(d.peso_gramos || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Precio/gramo <span style="color:red">*</span></label><input type="text" name="precio_gramo" value="' +
            esc(d.precio_gramo || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', oroProveedores, false) + '</div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-oro'), 'click', function() {
        DsCrud.openModal({
            title: 'Nuevo Inventario Oro',
            body: '<form id="frm-oro">' + buildOroFormHtml() + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.api('../api/inventario_oro.php', 'POST', data, function() {
                    DsCrud.toast('Oro creado', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('oro-table', {
        onEdit: function(id) {
            DsCrud.api('../api/inventario_oro.php?id=' + id, 'GET', null, function(res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Oro #' + id,
                    body: '<form id="frm-oro">' + buildOroFormHtml(d) + '</form>',
                    onSave: function(modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.api('../api/inventario_oro.php', 'PUT', data,
                            function() {
                                DsCrud.toast('Oro actualizado', 'success');
                                DsCrud.closeModal();
                                location.reload();
                            },
                            function(e) {
                                DsCrud.toast(e, 'error');
                            });
                    }
                });
            }, function(e) {
                DsCrud.toast('Error: ' + e, 'error');
            });
        },
        onDelete: function(id) {
            DsCrud.confirm('¿Eliminar oro #' + id + '?', function() {
                DsCrud.api('../api/inventario_oro.php', 'DELETE', {
                    id: id
                }, function() {
                    DsCrud.toast('Oro eliminado', 'success');
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });

    // ========== INSUMOS ==========
    function buildInsumoFormHtml(d) {
        d = d || {};
        // Añadir opción vacía al inicio de categorías si no existe
        var catOpts = [{
            value: '',
            label: '-- Seleccionar --'
        }].concat(categoriaOptions);
        // Filtrar proveedores de insumos
        var insProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(insumos)') > -1) {
                insProveedores.push(proveedorOptions[i]);
            }
        }
        if (insProveedores.length === 1) insProveedores = proveedorOptions;

        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Categoria <span style="color:red">*</span></label>' +
            selectHtml('categoria', d.categoria || '', catOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
            esc(d.cantidad || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', insProveedores, false) + '</div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-insumo'), 'click', function() {
        DsCrud.openModal({
            title: 'Nuevo Insumo',
            body: '<form id="frm-insumo">' + buildInsumoFormHtml() + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.api('../api/inventario_insumos.php', 'POST', data, function() {
                    DsCrud.toast('Insumo creado', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('insumos-table', {
        onEdit: function(id) {
            DsCrud.api('../api/inventario_insumos.php?id=' + id, 'GET', null, function(res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Insumo #' + id,
                    body: '<form id="frm-insumo">' + buildInsumoFormHtml(d) + '</form>',
                    onSave: function(modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.api('../api/inventario_insumos.php', 'PUT', data,
                            function() {
                                DsCrud.toast('Insumo actualizado', 'success');
                                DsCrud.closeModal();
                                location.reload();
                            },
                            function(e) {
                                DsCrud.toast(e, 'error');
                            });
                    }
                });
            }, function(e) {
                DsCrud.toast('Error: ' + e, 'error');
            });
        },
        onDelete: function(id) {
            DsCrud.confirm('¿Eliminar insumo #' + id + '?', function() {
                DsCrud.api('../api/inventario_insumos.php', 'DELETE', {
                    id: id
                }, function() {
                    DsCrud.toast('Insumo eliminado', 'success');
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });

    // ========== MAQUINARIA ==========
    function buildMaqFormHtml(d) {
        d = d || {};
        // Filtrar proveedores de maquinaria
        var maqProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(maquinaria)') > -1) {
                maqProveedores.push(proveedorOptions[i]);
            }
        }
        if (maqProveedores.length === 1) maqProveedores = proveedorOptions;

        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
            selectHtml('tipo_maquinaria_id', d.tipo_maquinaria_id || '', tipoMaquinariaOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Estado <span style="color:red">*</span></label>' +
            selectHtml('estado', d.estado || 'operativa', maqEstadoOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Ubicación</label>' +
            selectHtml('ubicacion_id', d.ubicacion_id || '', ubicacionOptions, false) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', maqProveedores, false) + '</div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-maquinaria'), 'click', function() {
        DsCrud.openModal({
            title: 'Nueva Maquinaria',
            body: '<form id="frm-maq">' + buildMaqFormHtml() + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.api('../api/inventario_maquinaria.php', 'POST', data, function() {
                    DsCrud.toast('Maquinaria creada', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('maq-table', {
        onEdit: function(id) {
            DsCrud.api('../api/inventario_maquinaria.php?id=' + id, 'GET', null, function(res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Maquinaria #' + id,
                    body: '<form id="frm-maq">' + buildMaqFormHtml(d) + '</form>',
                    onSave: function(modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.api('../api/inventario_maquinaria.php', 'PUT', data,
                            function() {
                                DsCrud.toast('Maquinaria actualizada',
                                    'success');
                                DsCrud.closeModal();
                                location.reload();
                            },
                            function(e) {
                                DsCrud.toast(e, 'error');
                            });
                    }
                });
            }, function(e) {
                DsCrud.toast('Error: ' + e, 'error');
            });
        },
        onDelete: function(id) {
            DsCrud.confirm('¿Eliminar maquinaria #' + id + '?', function() {
                DsCrud.api('../api/inventario_maquinaria.php', 'DELETE', {
                    id: id
                }, function() {
                    DsCrud.toast('Maquinaria eliminada', 'success');
                    location.reload();
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });
})();
</script>
<?php endif; ?>