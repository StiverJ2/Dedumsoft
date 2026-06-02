<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: MIS ÓRDENES (VISTA ARTESANO)
 * ============================================================================
 * 
 * Página de gestión de órdenes para artesanos.
 * Solo muestra las órdenes asignadas al artesano actual.
 * 
 * Características:
 * - Lista de órdenes asignadas al artesano
 * - Cambio de estado de orden (iniciar, pausar, terminar)
 * - Registro de consumo de materiales (oro, insumos)
 * - Registro de pieza terminada con datos de venta
 * - Visualización de prioridad y fecha límite
 * - Soporte dual: DataTables (moderno) o tabla HTML (legacy)
 * 
 * Autenticación: Requerida
 * Autorización: Menú 3 (Producción) + Rol de artesano
 * 
 * Restricciones:
 * - Solo accesible para usuarios con artesano_id asociado
 * - Administradores (rolid=1) no pueden acceder
 * 
 * APIs utilizadas:
 * - GET /api/produccion/artesano_ordenes.php - Listar órdenes del artesano
 * - PATCH /api/produccion/artesano_ordenes.php - Cambiar estado de orden
 * - POST /api/produccion/artesano_consumo.php - Registrar consumo de material
 * - POST /api/produccion/artesano_terminada.php - Registrar pieza terminada
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/OrdenController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(3); // Menú: Producción
$legacy = page_is_legacy();
$user = get_session_user();

// =============================================================================
// VALIDACIÓN DE ACCESO DE ARTESANO
// =============================================================================
// Solo usuarios con artesano_id pueden ver esta página.
// Administradores no pueden acceder (deben usar produccion.php).

$artesano_id = $user['artesano_id'] ?? null;
$rolid = (int) ($user['rolid'] ?? 0);
if (!$artesano_id || $rolid === 1) {
    dedumsoft_forbidden();
}

$ctrl = new OrdenController($connLogic);
$pageData = $ctrl->pageDataArtesanoOrdenes($artesano_id, $legacy);

$artesano_nombre = $pageData['artesano_nombre'];
$estados_options = $pageData['estados_options'];
$ordenes_rows    = $pageData['ordenes_rows'];

// =============================================================================
// RENDER
// =============================================================================
page_render_start(3);
render_view('pages/artesano_ordenes', [
    'artesano_id' => $artesano_id,
    'artesano_nombre' => $artesano_nombre,
    'ordenes_rows' => $ordenes_rows,
    'legacy' => $legacy,
]);
page_render_end(function () use ($legacy, $artesano_id, $estados_options) {
    if (!$artesano_id) {
        return;
    }
    ?>

<?php if (!$legacy): ?>
<script>
$(() => {
    const dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    const artesanoId = <?php echo (int) $artesano_id; ?>;
    let ordenesTable;
    let estadosCache = [];
    let oroCache = [];
    let insumosCache = [];
    let calidadCache = [];

    // Cargar datos de referencia
    Promise.all([
        axios.get('api/catalogos/opciones.php?tipo=estados_orden'),
        axios.get('api/catalogos/opciones.php?tipo=niveles_calidad'),
        axios.get('api/inventario/oro.php?limit=500'),
        axios.get('api/inventario/insumos.php?limit=500')
    ]).then((results) => {
        estadosCache = results[0].data.DATOS || [];
        calidadCache = results[1].data.DATOS || [];
        oroCache = (results[2].data.DATOS || []).map((o) => ({
            value: o.id,
            label: `${o.tipo_oro_nombre || 'Oro'} #${o.id} (${o.peso_gramos}g)`
        }));
        insumosCache = (results[3].data.DATOS || []).map((i) => ({
            value: i.id,
            label: `${i.nombre} (${i.cantidad} disp.)`
        }));
    });

    const esc = (value) => DsCrud.escapeHtml(value === null || value === undefined ? '' : String(value));

    const formatPrioridad = (v) => {
        const val = String(v || 'normal').toLowerCase();
        let cls = 'ds-badge--neutral';
        if (val === 'alta' || val === 'urgente') cls = 'ds-badge--danger';
        else if (val === 'media' || val === 'normal') cls = 'ds-badge--warning';
        else if (val === 'baja') cls = 'ds-badge--muted';
        const label = esc(val.charAt(0).toUpperCase() + val.slice(1));
        return `<span class="ds-badge ${cls}">${label}</span>`;
    };

    const formatEstado = (v) => {
        const raw = String(v || '');
        const label = esc(raw.replace(/_/g, ' ').toUpperCase());
        const key = raw.toLowerCase();
        let cls = 'ds-badge--neutral';
        if (key === 'pendiente') cls = 'ds-badge--warning';
        else if (key === 'en_proceso') cls = 'ds-badge--info';
        else if (key === 'terminada') cls = 'ds-badge--success';
        else if (key === 'cancelada') cls = 'ds-badge--danger';
        else if (key === 'pausada') cls = 'ds-badge--muted';
        return `<span class="ds-badge ${cls}">${label}</span>`;
    };

    ordenesTable = $('#ordenes-artesano-table').DataTable({
        ajax: {
            url: 'api/produccion/artesano_ordenes.php?artesano_id=' + artesanoId,
            dataSrc: 'DATOS'
        },
        columns: [{
                data: 'id'
            },
            {
                data: 'producto_nombre'
            },
            {
                data: 'cantidad'
            },
            {
                data: 'prioridad',
                render: formatPrioridad
            },
            {
                data: 'estado',
                render: formatEstado
            },
            {
                data: 'fecha_inicio',
                render: (v) => (v ? v.split('T')[0] : '-')
            },
            {
                data: 'fecha_fin_estimada',
                render: (v) => (v ? v.split('T')[0] : '-')
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (data, type, row) => {
                    if (type !== 'display') return '';
                    return '<button class="ds-action-btn" data-action="estado" title="Cambiar estado">🔄</button>' +
                        '<button class="ds-action-btn" data-action="consumo" title="Registrar consumo">📦</button>' +
                        '<button class="ds-action-btn" data-action="terminar" title="Registrar pieza terminada">✅</button>';
                }
            }
        ],
        language: dtLang,
        order: [
            [3, 'desc'],
            [0, 'desc']
        ]
    });

    // Cambiar estado
    const openCambiarEstado = (row) => {
        const estadoOpts = estadosCache.map((e) => ({
            value: e.value || e.id,
            label: e.label || e.nombre
        }));
        const body = '<form id="frm-estado">' +
            DsCrud.field({
                name: 'estado_id',
                label: 'Nuevo estado',
                type: 'select',
                options: estadoOpts,
                required: true
            }) +
            '</form>';
        DsCrud.openModal({
            title: 'Cambiar estado - Orden #' + row.id,
            body: body,
            onSave: (m) => {
                const f = m.querySelector('#frm-estado');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {
                    id: row.id,
                    estado_id: fd.get('estado_id')
                };
                DsCrud.api('api/produccion/artesano_ordenes.php', 'PATCH', payload, () => {
                    DsCrud.toast('Estado actualizado', 'success');
                    ordenesTable.ajax.reload();
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    // Registrar consumo de materiales
    const openRegistrarConsumo = (row) => {
        const body = '<form id="frm-consumo">' +
            DsCrud.field({
                name: 'tipo_material',
                label: 'Tipo de material',
                type: 'select',
                options: [{
                        value: 'oro',
                        label: 'Oro'
                    },
                    {
                        value: 'insumo',
                        label: 'Insumo'
                    }
                ],
                required: true
            }) +
            '<div id="material-select-container"></div>' +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad consumida',
                type: 'number',
                required: true,
                attrs: 'step="0.001" min="0.001"'
            }) +
            '</form>';
        DsCrud.openModal({
            title: 'Registrar consumo - Orden #' + row.id,
            body: body,
            onSave: (m) => {
                const f = m.querySelector('#frm-consumo');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {
                    orden_id: row.id,
                    tipo_material: fd.get('tipo_material'),
                    material_id: fd.get('material_id'),
                    cantidad: parseFloat(fd.get('cantidad'))
                };
                DsCrud.api('api/produccion/artesano_consumo.php', 'POST', payload, () => {
                    DsCrud.toast('Consumo registrado', 'success');
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });

        // Cambiar opciones de material según tipo
        setTimeout(() => {
            const tipoSelect = document.querySelector('[name="tipo_material"]');
            const container = document.getElementById('material-select-container');

            const updateMaterialOptions = () => {
                const tipo = tipoSelect.value;
                const opts = tipo === 'oro' ? oroCache : insumosCache;
                container.innerHTML = DsCrud.field({
                    name: 'material_id',
                    label: 'Material',
                    type: 'select',
                    options: opts,
                    required: true
                });
            };
            tipoSelect.addEventListener('change', updateMaterialOptions);
            updateMaterialOptions();
        }, 100);
    };

    // Registrar pieza terminada
    const openRegistrarTerminada = (row) => {
        const calidadOpts = calidadCache.map((c) => ({
            value: c.value || c.id,
            label: c.label || c.nombre
        }));
        const body = '<form id="frm-terminada">' +
            DsCrud.field({
                name: 'peso_final',
                label: 'Peso final (gramos)',
                type: 'number',
                required: true,
                attrs: 'step="0.001" min="0.001"'
            }) +
            DsCrud.field({
                name: 'tiempo_real',
                label: 'Tiempo real (horas)',
                type: 'number',
                attrs: 'step="0.25" min="0"'
            }) +
            DsCrud.field({
                name: 'calidad_id',
                label: 'Calidad',
                type: 'select',
                options: calidadOpts
            }) +
            DsCrud.field({
                name: 'observaciones',
                label: 'Observaciones',
                type: 'textarea'
            }) +
            '</form>';
        DsCrud.openModal({
            title: 'Registrar pieza terminada - Orden #' + row.id,
            body: body,
            onSave: (m) => {
                const f = m.querySelector('#frm-terminada');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {
                    orden_id: row.id,
                    peso_final: parseFloat(fd.get('peso_final')),
                    tiempo_real: fd.get('tiempo_real') ? parseFloat(fd.get('tiempo_real')) :
                        null,
                    calidad_id: fd.get('calidad_id') || null,
                    observaciones: fd.get('observaciones') || null
                };
                DsCrud.api('api/produccion/artesano_terminada.php', 'POST', payload, () => {
                    DsCrud.toast('Pieza terminada registrada', 'success');
                    ordenesTable.ajax.reload();
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    $('#ordenes-artesano-table').on('click', '.ds-action-btn[data-action="estado"]', (e) => {
        const row = ordenesTable.row($(e.currentTarget).closest('tr')).data();
        openCambiarEstado(row);
    });
    $('#ordenes-artesano-table').on('click', '.ds-action-btn[data-action="consumo"]', (e) => {
        const row = ordenesTable.row($(e.currentTarget).closest('tr')).data();
        openRegistrarConsumo(row);
    });
    $('#ordenes-artesano-table').on('click', '.ds-action-btn[data-action="terminar"]', (e) => {
        const row = ordenesTable.row($(e.currentTarget).closest('tr')).data();
        openRegistrarTerminada(row);
    });
});
</script>

<?php elseif ($legacy && $artesano_id): ?>
<script>
(function() {
    if (window.DedumTableSort) {
        DedumTableSort.init('ordenes-artesano-table');
    }

    var estadosOptions = <?php echo json_encode(array_map(function ($e) {
                return ['value' => $e['value'], 'label' => ucfirst(str_replace('_', ' ', $e['label']))];
            }, $estados_options)); ?>;

    function esc(s) {
        if (s === null || s === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(s)));
        return div.innerHTML;
    }

    function selectHtml(name, value, options, req) {
        var html = '<select name="' + esc(name) + '" class="ds-field ds-field--select"' + (req ? ' required' : '') +
            '>';
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            var sel = (String(opt.value) === String(value)) ? ' selected' : '';
            html += '<option value="' + esc(opt.value) + '"' + sel + '>' + esc(opt.label) + '</option>';
        }
        html += '</select>';
        return html;
    }

    function inputHtml(name, type, value, req, attrs) {
        return '<input type="' + esc(type) + '" name="' + esc(name) + '" value="' + esc(value || '') +
            '" class="ds-field"' + (req ? ' required' : '') + ' ' + (attrs || '') + '>';
    }

    function openLegacyModal(title, bodyHtml, onSubmit) {
        var overlay = document.createElement('div');
        overlay.className = 'ds-modal-overlay ds-modal--open';
        var modal = document.createElement('div');
        modal.className = 'ds-modal';
        modal.innerHTML = '<div class="ds-modal-header"><strong>' + esc(title) +
            '</strong><button type="button" class="ds-modal-close">' +
            '<img src="assets/icons/fatcow/16/prohibition_button.png" alt="Cerrar" style="display:block;border:0;width:16px;height:16px;">' +
            '</button></div>' +
            '<div class="ds-modal-body">' + bodyHtml + '</div>' +
            '<div class="ds-modal-footer"><button type="button" class="btn btn-secondary ds-modal-cancel">Cancelar</button><button type="submit" class="btn btn-primary ds-modal-save">Guardar</button></div>';
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        function close() {
            document.body.removeChild(overlay);
        }

        function validateForm(form) {
            if (!form) return true;
            var inputs = form.getElementsByTagName('input');
            var selects = form.getElementsByTagName('select');
            var textareas = form.getElementsByTagName('textarea');

            function check(el) {
                if (el.getAttribute('required') !== null || el.required) {
                    if (!el.value || el.value === '') {
                        el.style.borderColor = '#dc3545';
                        el.focus();
                        return false;
                    }
                    el.style.borderColor = '';
                }
                return true;
            }

            for (var i = 0; i < inputs.length; i++) {
                if (!check(inputs[i])) return false;
            }
            for (var j = 0; j < selects.length; j++) {
                if (!check(selects[j])) return false;
            }
            for (var k = 0; k < textareas.length; k++) {
                if (!check(textareas[k])) return false;
            }
            return true;
        }

        overlay.querySelector('.ds-modal-close').onclick = close;
        overlay.querySelector('.ds-modal-cancel').onclick = close;
        overlay.querySelector('.ds-modal-save').onclick = function() {
            var form = overlay.querySelector('form');
            if (form && !validateForm(form)) return;
            onSubmit(overlay, close);
        };
    }

    function getFormData(overlay) {
        var form = overlay.querySelector('form');
        var data = {};
        if (!form) return data;
        var inputs = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            if (el.name) data[el.name] = el.value;
        }
        return data;
    }

    // Polyfill para closest (IE8)
    function getClosest(el, selector) {
        while (el && el !== document) {
            if (el.className && el.className.indexOf(selector.replace('.', '')) !== -1) {
                return el;
            }
            if (el.tagName && el.tagName.toLowerCase() === selector.toLowerCase()) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }

    // Event delegation for actions (IE8 compatible)
    var table = document.getElementById('ordenes-artesano-table');
    if (table.addEventListener) {
        table.addEventListener('click', handleTableClick, false);
    } else if (table.attachEvent) {
        table.attachEvent('onclick', handleTableClick);
    }

    function handleTableClick(e) {
        e = e || window.event;
        var target = e.target || e.srcElement;

        // Buscar el boton de accion
        var btn = getClosest(target, 'ds-action-btn');
        if (!btn) {
            // Si clicamos en la imagen dentro del boton
            if (target.parentNode && target.parentNode.className &&
                target.parentNode.className.indexOf('ds-action-btn') !== -1) {
                btn = target.parentNode;
            }
        }
        if (!btn) return;

        var row = getClosest(btn, 'tr');
        if (!row) return;

        var ordenId = row.getAttribute('data-id');
        var action = btn.getAttribute('data-action');

        if (action === 'estado') {
            var body = '<form><div class="ds-form-group"><label>Nuevo estado</label>' + selectHtml(
                'estado_id', '', estadosOptions, true) + '</div></form>';
            openLegacyModal('Cambiar estado - Orden #' + ordenId, body, function(overlay, close) {
                var data = getFormData(overlay);
                data.id = ordenId;
                DsCrud.apiLegacy('api/produccion/artesano_ordenes.php', 'PATCH', data, function() {
                    alert('Estado actualizado');
                    close();
                    location.reload();
                }, function(e) {
                    alert(e);
                });
            });
        } else if (action === 'consumo') {
            var body = '<form>' +
                '<div class="ds-form-group"><label>Tipo de material</label>' + selectHtml('tipo_material',
                    'oro', [{
                        value: 'oro',
                        label: 'Oro'
                    }, {
                        value: 'insumo',
                        label: 'Insumo'
                    }], true) + '</div>' +
                '<div class="ds-form-group"><label>ID del material</label>' + inputHtml('material_id',
                    'number', '', true, 'min="1"') + '</div>' +
                '<div class="ds-form-group"><label>Cantidad</label>' + inputHtml('cantidad', 'number', '',
                    true, 'step="0.001" min="0.001"') + '</div>' +
                '</form>';
            openLegacyModal('Registrar consumo - Orden #' + ordenId, body, function(overlay, close) {
                var data = getFormData(overlay);
                data.orden_id = ordenId;
                data.cantidad = parseFloat(data.cantidad);
                DsCrud.apiLegacy('api/produccion/artesano_consumo.php', 'POST', data, function() {
                    alert('Consumo registrado');
                    close();
                }, function(e) {
                    alert(e);
                });
            });
        } else if (action === 'terminar') {
            var body = '<form>' +
                '<div class="ds-form-group"><label>Peso final (gramos)</label>' + inputHtml('peso_final',
                    'number', '', true, 'step="0.001" min="0.001"') + '</div>' +
                '<div class="ds-form-group"><label>Tiempo real (horas)</label>' + inputHtml('tiempo_real',
                    'number', '', false, 'step="0.25" min="0"') + '</div>' +
                '<div class="ds-form-group"><label>Observaciones</label><textarea name="observaciones" class="ds-field"></textarea></div>' +
                '</form>';
            openLegacyModal('Pieza terminada - Orden #' + ordenId, body, function(overlay, close) {
                var data = getFormData(overlay);
                data.orden_id = ordenId;
                data.peso_final = parseFloat(data.peso_final);
                if (data.tiempo_real) data.tiempo_real = parseFloat(data.tiempo_real);
                DsCrud.apiLegacy('api/produccion/artesano_terminada.php', 'POST', data, function() {
                    alert('Pieza registrada');
                    close();
                    location.reload();
                }, function(e) {
                    alert(e);
                });
            });
        }
    }
})();
</script>
<?php endif; ?>
    <?php
});
