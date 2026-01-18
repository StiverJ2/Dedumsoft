<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$insumo_categoria = $_GET['insumo_categoria'] ?? '';
$insumo_stock_bajo = isset($_GET['insumo_stock_bajo']) && $_GET['insumo_stock_bajo'] !== '0';
$insumo_rows = [];
$categoria_options = [];
$proveedor_options = [];

function format_categoria_badge($cat)
{
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

if ($legacy) {
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
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Insumos</h1>
        <p>Control de materiales y consumibles</p>
    </div>

    <div class="card" id="inv-insumos">
        <div class="ds-toolbar">
            <strong>Insumos</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-insumo">+ Nuevo Insumo</button>
            </div>
        </div>
        <?php if ($legacy): ?>
        <form method="get" action="inventario_insumos.php#inv-insumos" class="d-flex flex-wrap gap-3 align-items-end">
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
            <a href="inventario_insumos.php#inv-insumos" class="btn btn-sm btn-secondary">Limpiar</a>
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
</div>

<?php if (!$legacy): ?>
<script>
window.DEDUMSOFT_ICON_MODE = 'emoji';
</script>
<?php endif; ?>
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

$(function() {
    var dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    var insumosTable;
    var proveedoresCache = [];

    $.getJSON('../api/proveedores.php?limit=500', function(res) {
        proveedoresCache = (res.DATOS || []).map(function(p) {
            return {
                value: p.id,
                label: p.nombre + ' (' + p.tipo + ')'
            };
        });
    });

    function buildInsumoForm(data) {
        data = data || {};
        var provOpts = [{ value: '', label: '-- Sin proveedor --' }].concat(
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
                label: 'Categoria',
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
                label: 'Stock minimo',
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
                        payload = { id: d.id };
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
        DsCrud.confirm('Eliminar insumo "' + row.nombre + '"?', function() {
            DsCrud.api('../api/inventario_insumos.php', 'DELETE', { id: row.id }, function() {
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
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'categoria', render: formatCategoria },
            { data: 'cantidad' },
            { data: 'proveedor_nombre', defaultContent: '' },
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
});
</script>
<?php elseif ($legacy): ?>
<script>
(function() {
    if (window.DedumTableSort) {
        DedumTableSort.init('insumos-table');
    }

    var categoriaOptions = <?php echo json_encode(array_map(function($c) {
        return ['value' => $c, 'label' => ucwords(str_replace('_', ' ', $c))];
    }, $categoria_options)); ?>;

    var proveedorOptions = <?php echo json_encode(array_merge(
        [['value' => '', 'label' => '-- Sin proveedor --']],
        array_map(function($p) {
            return ['value' => $p['id'], 'label' => $p['nombre'] . ' (' . $p['tipo'] . ')'];
        }, $proveedor_options)
    )); ?>;

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

    function buildInsumoFormHtml(d) {
        d = d || {};
        var catOpts = [{ value: '', label: '-- Seleccionar --' }].concat(categoriaOptions);
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
            DsCrud.confirm('Eliminar insumo #' + id + '?', function() {
                DsCrud.api('../api/inventario_insumos.php', 'DELETE', { id: id }, function() {
                    DsCrud.toast('Insumo eliminado', 'success');
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
</body>
</html>
