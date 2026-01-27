<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: INVENTARIO DE INSUMOS
 * ============================================================================
 * 
 * Página de gestión del inventario de insumos (materiales consumibles).
 * Permite visualizar, agregar, editar y eliminar registros de insumos.
 * 
 * Características:
 * - Tabla de inventario con filtros (categoría, stock bajo)
 * - Modal para crear/editar registros
 * - Modal para registrar compras
 * - Alertas de stock bajo
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 2 (Inventario)
 * 
 * Parámetros GET (solo legacy):
 * - insumo_categoria: Filtrar por categoría
 * - insumo_stock_bajo: Mostrar solo items con stock bajo
 * 
 * APIs utilizadas:
 * - GET /api/inventario_insumos.php - Listar insumos
 * - POST /api/inventario_insumos.php - Crear registro
 * - PATCH /api/inventario_insumos.php - Actualizar registro
 * - DELETE /api/inventario_insumos.php - Eliminar registro
 * - POST /api/compras.php - Registrar compra
 * - GET /api/proveedores.php - Lista de proveedores
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';

// Verificar autenticación y autorización
require_login('/login.php');
require_menu_access(2); // Menú: Inventario

// Detectar modo de interfaz
$legacy = dedumsoft_is_legacy_browser();

// Filtros de búsqueda (solo usados en modo legacy)
$insumo_categoria = trim((string) ($_GET['insumo_categoria'] ?? ''));
$insumo_stock_bajo = isset($_GET['insumo_stock_bajo']) && $_GET['insumo_stock_bajo'] !== '0';
$insumo_rows = [];
$categoria_options = [];
$proveedor_options = [];

/**
 * Genera un badge HTML con color según la categoría.
 * Cada categoría tiene un color asociado para fácil identificación.
 * 
 * @param string $cat Nombre de la categoría
 * @return string HTML del badge
 */
function format_categoria_badge($cat)
{
    $cat = trim((string)$cat);
    if ($cat === '') return '';
    $key = strtolower($cat);
    $label = ucwords(str_replace('_', ' ', $cat));
    
    // Asignar color según tipo de categoría
    $cls = 'ds-badge--neutral';
    if (strpos($key, 'piedra') !== false || strpos($key, 'gema') !== false) $cls = 'ds-badge--info';
    elseif (strpos($key, 'metal') !== false || strpos($key, 'oro') !== false) $cls = 'ds-badge--warning';
    elseif (strpos($key, 'herramienta') !== false || strpos($key, 'equipo') !== false) $cls = 'ds-badge--muted';
    elseif (strpos($key, 'quimico') !== false || strpos($key, 'limpieza') !== false) $cls = 'ds-badge--danger';
    elseif (strpos($key, 'empaque') !== false || strpos($key, 'caja') !== false) $cls = 'ds-badge--success';
    
    return '<span class="ds-badge ' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

// Cargar opciones de categorías (valores distintos en BD)
try {
    $stmt = $connLogic->query(
        "SELECT DISTINCT categoria FROM inventario_insumos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
    );
    $categoria_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('inventario categorias error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

// Cargar proveedores para dropdown
try {
    $stmt = $connLogic->query("SELECT id, nombre, tipo FROM proveedores WHERE activo = TRUE ORDER BY nombre");
    $proveedor_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $insumo_limit = ($insumo_categoria !== '' || $insumo_stock_bajo) ? 200 : 20;
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, categoria, cantidad, stock_minimo, proveedor_nombre FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $insumo_limit, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_categoria !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':stock_bajo', $insumo_stock_bajo, PDO::PARAM_BOOL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $insumo_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($insumo_categoria !== '') {
            $insumo_rows = array_values(array_filter($insumo_rows, function ($row) use ($insumo_categoria) {
                return strcasecmp(trim((string) ($row['categoria'] ?? '')), $insumo_categoria) === 0;
            }));
        }
        if ($insumo_stock_bajo) {
            $insumo_rows = array_values(array_filter($insumo_rows, function ($row) {
                $cantidad = isset($row['cantidad']) ? (float) $row['cantidad'] : 0;
                $stock_minimo = isset($row['stock_minimo']) ? (float) $row['stock_minimo'] : 0;
                return $cantidad <= $stock_minimo;
            }));
        }
    } catch (PDOException $e) {
        error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
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
                <button type="button" class="btn-add" id="btn-compra-insumo">+ Registrar Compra</button>
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
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
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

    $.getJSON('api/proveedores.php?limit=500', function(res) {
        proveedoresCache = (res.DATOS || []).map(function(p) {
            var tipoRaw = p.tipo_nombre || p.tipo || '';
            var tipoLower = String(tipoRaw || '').toLowerCase();
            return {
                value: p.id,
                label: p.nombre + (tipoRaw ? ' (' + tipoRaw + ')' : ''),
                tipo: tipoLower
            };
        });
    });

    function buildInsumoForm(data) {
        data = data || {};
        var provOpts = [{ value: '', label: '-- Sin proveedor --' }].concat(
            proveedoresCache.filter(function(p) {
                var isInsumo = p.tipo === 'insumos';
                var isCurrent = String(p.value) === String(data.proveedor_id || '');
                return isInsumo || !data.id || isCurrent;
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
        var compraToggle = '<div class="ds-form-group"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
        DsCrud.openModal({
            title: 'Nuevo Insumo',
            body: '<form id="frm-insumo">' + buildInsumoForm() + compraToggle + '</form>',
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
                var registrarCompra = payload.registrar_compra === 'on';
                delete payload.registrar_compra;

                var compraCantidad = parseFloat(payload.cantidad || 0);
                if (registrarCompra) {
                    if (!isFinite(compraCantidad) || compraCantidad <= 0) {
                        DsCrud.toast('Cantidad debe ser mayor a 0 para registrar compra', 'error');
                        return;
                    }
                    payload.cantidad = 0;
                }

                DsCrud.api('api/inventario_insumos.php', 'POST', payload, function(success, resp) {
                    if (!registrarCompra) {
                        DsCrud.toast('Insumo creado', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                        return;
                    }
                    var compraPayload = {
                        tipo_inventario: 'insumos',
                        item_id: resp.ID,
                        cantidad: compraCantidad
                    };
                    DsCrud.api('api/compras.php', 'POST', compraPayload, function() {
                        DsCrud.toast('Insumo creado y compra registrada', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    }, function(e) {
                        DsCrud.toast('Insumo creado, pero no se pudo registrar la compra: ' + e, 'error');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    });
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openInsumoEdit(row) {
        DsCrud.api('api/inventario_insumos.php?id=' + row.id, 'GET', null, function(res) {
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
                    DsCrud.api('api/inventario_insumos.php', 'PATCH', payload, function() {
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
            DsCrud.api('api/inventario_insumos.php', 'DELETE', { id: row.id }, function() {
                DsCrud.toast('Insumo eliminado', 'success');
                insumosTable.ajax.reload();
            }, function(e) {
                DsCrud.toast(e, 'error');
            });
        });
    }

    function buildCompraInsumoForm(options, data) {
        data = data || {};
        return DsCrud.field({
                name: 'item_id',
                label: 'Insumo',
                type: 'select',
                value: data.item_id,
                options: options,
                required: true
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
                type: 'number',
                value: data.cantidad,
                required: true,
                attrs: 'step="0.01" min="0"'
            }) +
            DsCrud.field({
                name: 'motivo',
                label: 'Motivo',
                value: data.motivo || 'Compra proveedor'
            }) +
            DsCrud.field({
                name: 'referencia',
                label: 'Referencia',
                value: data.referencia
            }) +
            DsCrud.field({
                name: 'fecha',
                label: 'Fecha',
                type: 'datetime-local',
                value: data.fecha
            });
    }

    function openInsumoCompra() {
        axios.get('api/inventario_insumos.php?limit=500').then(function(res) {
            var items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
            if (!items.length) {
                DsCrud.toast('No hay insumos disponibles', 'warning');
                return;
            }
            var options = [{ value: '', label: '-- Seleccione --' }].concat(items.map(function(it) {
                var label = it.nombre || ('Insumo #' + it.id);
                if (it.categoria) {
                    label += ' (' + it.categoria + ')';
                }
                label += ' #' + it.id;
                return { value: it.id, label: label };
            }));

            DsCrud.openModal({
                title: 'Registrar compra de insumos',
                body: '<form id="frm-compra-insumo">' + buildCompraInsumoForm(options) + '</form>',
                onSave: function(m) {
                    var f = m.querySelector('#frm-compra-insumo');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    var fd = new FormData(f),
                        payload = { tipo_inventario: 'insumos' };
                    fd.forEach(function(v, k) {
                        payload[k] = v;
                    });
                    if (payload.fecha) {
                        payload.fecha = payload.fecha.replace('T', ' ');
                    }
                    DsCrud.api('api/compras.php', 'POST', payload, function() {
                        DsCrud.toast('Compra registrada', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    }, function(e) {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        }).catch(function() {
            DsCrud.toast('Error cargando inventario', 'error');
        });
    }

    insumosTable = $('#insumos-table').DataTable({
        ajax: {
            url: 'api/inventario_insumos.php?limit=500',
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
    $('#btn-compra-insumo').on('click', openInsumoCompra);
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

    var insumoInventoryOptions = <?php echo json_encode(array_map(function($row) {
        $label = $row['nombre'] ?? ('Insumo #' . $row['id']);
        $label .= ' #' . $row['id'];
        return ['value' => $row['id'], 'label' => $label];
    }, $insumo_rows)); ?>;

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

    function buildInsumoFormHtml(d, showCompra) {
        d = d || {};
        var catOpts = [{ value: '', label: '-- Seleccionar --' }].concat(categoriaOptions);
        var insProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(insumos)') > -1) {
                insProveedores.push(proveedorOptions[i]);
            }
        }
        if (insProveedores.length === 1) insProveedores = proveedorOptions;

        var html = '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Categoria <span style="color:red">*</span></label>' +
            selectHtml('categoria', d.categoria || '', catOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
            esc(d.cantidad || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', insProveedores, false) + '</div>';
        if (showCompra) {
            html += '<div style="margin-bottom:12px;"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
        }
        return html;
    }

    function buildCompraInsumoFormHtml(d) {
        d = d || {};
        var invOpts = [{ value: '', label: '-- Seleccione --' }].concat(insumoInventoryOptions);
        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Insumo <span style="color:red">*</span></label>' +
            selectHtml('item_id', d.item_id || '', invOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
            esc(d.cantidad || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Motivo</label><input type="text" name="motivo" value="' +
            esc(d.motivo || 'Compra proveedor') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Referencia</label><input type="text" name="referencia" value="' +
            esc(d.referencia || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Fecha</label><input type="datetime-local" name="fecha" value="' +
            esc(d.fecha || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-insumo'), 'click', function() {
        DsCrud.openModal({
            title: 'Nuevo Insumo',
            body: '<form id="frm-insumo">' + buildInsumoFormHtml({}, true) + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                var registrarCompra = data.registrar_compra === true;
                delete data.registrar_compra;

                var compraCantidad = parseFloat(data.cantidad || 0);
                if (registrarCompra) {
                    if (!isFinite(compraCantidad) || compraCantidad <= 0) {
                        DsCrud.toast('Cantidad debe ser mayor a 0 para registrar compra', 'error');
                        return;
                    }
                    data.cantidad = 0;
                }

                DsCrud.apiLegacy('api/inventario_insumos.php', 'POST', data, function(res) {
                    if (!registrarCompra) {
                        DsCrud.toast('Insumo creado', 'success');
                        DsCrud.closeModal();
                        location.reload();
                        return;
                    }
                    var compraPayload = {
                        tipo_inventario: 'insumos',
                        item_id: res.ID,
                        cantidad: compraCantidad
                    };
                    DsCrud.apiLegacy('api/compras.php', 'POST', compraPayload, function() {
                        DsCrud.toast('Insumo creado y compra registrada', 'success');
                        DsCrud.closeModal();
                        location.reload();
                    }, function(e) {
                        DsCrud.toast('Insumo creado, pero no se pudo registrar la compra: ' + e, 'error');
                        DsCrud.closeModal();
                        location.reload();
                    });
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.addEvent(DsCrud.getById('btn-compra-insumo'), 'click', function() {
        if (!insumoInventoryOptions.length) {
            DsCrud.toast('No hay insumos disponibles', 'error');
            return;
        }
        DsCrud.openModal({
            title: 'Registrar compra de insumos',
            body: '<form id="frm-compra-insumo">' + buildCompraInsumoFormHtml() + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                data.tipo_inventario = 'insumos';
                if (data.fecha) {
                    data.fecha = data.fecha.replace('T', ' ');
                }
                DsCrud.apiLegacy('api/compras.php', 'POST', data, function() {
                    DsCrud.toast('Compra registrada', 'success');
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
            DsCrud.apiLegacy('api/inventario_insumos.php?id=' + id, 'GET', null, function(res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Insumo #' + id,
                    body: '<form id="frm-insumo">' + buildInsumoFormHtml(d, false) + '</form>',
                    onSave: function(modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/inventario_insumos.php', 'PATCH', data,
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
                DsCrud.apiLegacy('api/inventario_insumos.php', 'DELETE', { id: id }, function() {
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
