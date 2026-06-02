/**
 * ============================================================================
 * PRODUCCION — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function () {
    'use strict';

    if (window.DedumTableSort) DedumTableSort.init('ordenes-table');

    var artesanosOptions = window._artesanosOptions || [];
    var productosOptions = window._productosOptions || [];
    var prioridadesOptions = window._prioridadesOptions || [];

    function esc(s) {
        if (s === null || s === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(s)));
        return div.innerHTML;
    }

    function selectHtml(name, value, options, req) {
        var h = '<select name="' + esc(name) + '" class="ds-field ds-field--select"' + (req ? ' required' : '') + '>';
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            var sel = (String(opt.value) === String(value)) ? ' selected' : '';
            h += '<option value="' + esc(opt.value) + '"' + sel + '>' + esc(opt.label) + '</option>';
        }
        h += '</select>';
        return h;
    }

    function getDefaultPrioridadValue() {
        for (var i = 0; i < prioridadesOptions.length; i++) {
            if (String(prioridadesOptions[i].value) === '2') {
                return prioridadesOptions[i].value;
            }
        }
        if (prioridadesOptions.length) {
            return prioridadesOptions[0].value;
        }
        return '';
    }

    function buildCrearFormHtml(defaultPrioridad) {
        var prodOpts = [{ value: '', label: '-- Seleccione --' }].concat(productosOptions);
        var artOpts = [{ value: '', label: '-- Sin asignar --' }].concat(artesanosOptions);
        var prioOpts = [{ value: '', label: '-- Seleccione --' }].concat(prioridadesOptions);
        var html = '<div class="ds-form-group"><label>Producto</label>' +
            selectHtml('producto_id', '', prodOpts, true) + '</div>' +
            '<div class="ds-form-group"><label>Cantidad</label><input type="text" name="cantidad" value="1" class="ds-field" required></div>' +
            '<div class="ds-form-group"><label>Prioridad</label>' +
            selectHtml('prioridad_id', defaultPrioridad || '', prioOpts, false) + '</div>' +
            '<div class="ds-form-group"><label>Asignar artesano</label>' +
            selectHtml('artesano_id', '', artOpts, false) + '</div>' +
            '<div class="ds-form-group"><label>Observaciones</label>' +
            '<textarea name="observaciones" class="ds-field" rows="3"></textarea></div>';
        return html;
    }

    function openCrear() {
        if (!productosOptions.length) {
            DsCrud.toast('No hay productos disponibles', 'error');
            return;
        }
        var prioridadDefault = getDefaultPrioridadValue();
        DsCrud.openModal({
            title: 'Crear orden',
            body: '<form id="frm-crear-orden">' + buildCrearFormHtml(prioridadDefault) + '</form>',
            saveText: 'Crear',
            cancelText: 'Cancelar',
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                var productoId = parseInt(data.producto_id || 0, 10) || 0;
                var cantidad = parseInt(data.cantidad || 0, 10) || 0;
                var prioridadId = parseInt(data.prioridad_id || 0, 10) || 0;
                var artesanoId = parseInt(data.artesano_id || 0, 10) || 0;
                var observaciones = data.observaciones ? String(data.observaciones).replace(/^\s+|\s+$/g, '') : '';

                if (productoId <= 0 || cantidad <= 0) {
                    DsCrud.toast('Producto y cantidad son requeridos', 'error');
                    return;
                }

                var payload = {
                    producto_id: productoId,
                    cantidad: cantidad,
                    prioridad_id: prioridadId > 0 ? prioridadId : null,
                    artesano_id: artesanoId > 0 ? artesanoId : null,
                    observaciones: observaciones ? observaciones : null
                };

                DsCrud.apiLegacy('api/ordenes.php', 'POST', payload, function () {
                    DsCrud.toast('Orden creada', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openAsignar(ordenId, artesanoId) {
        if (!artesanosOptions.length) {
            alert('No hay artesanos disponibles');
            return;
        }
        var opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosOptions);
        var body = '<form id="frm-asignar">' +
            '<div class="ds-form-group"><label>Artesano</label>' +
            selectHtml('artesano_id', artesanoId || '', opts, true) + '</div>' +
            '</form>';
        DsCrud.openModal({
            title: 'Asignar artesano - Orden #' + ordenId,
            body: body,
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                data.id = ordenId;
                DsCrud.api('api/ordenes.php', 'PATCH', data, function () {
                    DsCrud.toast('Orden actualizada', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    }

    function openDetalles(row) {
        if (!row) return;
        var producto = row.getAttribute('data-producto') || '';
        var cantidad = row.getAttribute('data-cantidad') || '';
        var artesano = '';
        var cells = row.getElementsByTagName('td');
        if (cells.length > 2) {
            artesano = cells[2].innerText || cells[2].textContent || '';
        }
        var estado = row.getAttribute('data-estado') || '';
        var prioridad = row.getAttribute('data-prioridad') || '';
        var fechaCreacion = row.getAttribute('data-fecha-creacion') || '';
        var fechaInicio = row.getAttribute('data-fecha-inicio') || '';
        var fechaFinEst = row.getAttribute('data-fecha-fin-estimada') || '';
        var fechaFinReal = row.getAttribute('data-fecha-fin-real') || '';
        var observaciones = row.getAttribute('data-observaciones') || '';
        if (!observaciones) observaciones = '-';
        var body = '<div class="ds-detail-list">' +
            '<p><strong>Producto:</strong> ' + esc(producto) + '</p>' +
            '<p><strong>Cantidad:</strong> ' + esc(cantidad) + '</p>' +
            '<p><strong>Artesano:</strong> ' + esc(artesano) + '</p>' +
            '<p><strong>Estado:</strong> ' + esc(estado) + '</p>' +
            '<p><strong>Prioridad:</strong> ' + esc(prioridad) + '</p>' +
            '<p><strong>Fecha creacion:</strong> ' + esc(fechaCreacion) + '</p>' +
            '<p><strong>Fecha inicio:</strong> ' + esc(fechaInicio) + '</p>' +
            '<p><strong>Fecha fin estimada:</strong> ' + esc(fechaFinEst) + '</p>' +
            '<p><strong>Fecha fin real:</strong> ' + esc(fechaFinReal) + '</p>' +
            '<p><strong>Observaciones:</strong> ' + esc(observaciones) + '</p>' +
            '</div>';
        DsCrud.openModal({
            title: 'Detalles de orden #' + row.getAttribute('data-id'),
            body: body,
            saveText: 'Cerrar',
            cancelText: 'Cerrar',
            onSave: function () {
                DsCrud.closeModal();
            }
        });
    }

    function findActionButton(target) {
        while (target && target !== document) {
            if (target.getAttribute && target.getAttribute('data-action')) {
                return target;
            }
            target = target.parentNode;
        }
        return null;
    }

    var table = document.getElementById('ordenes-table');
    if (table) {
        var btnCrear = document.getElementById('btn-add-orden');
        if (btnCrear) {
            DsCrud.addEvent(btnCrear, 'click', function () {
                openCrear();
            });
        }
        DsCrud.addEvent(table, 'click', function (e) {
            e = e || window.event;
            var target = e.target || e.srcElement;
            var btn = findActionButton(target);
            if (!btn) return;

            var row = btn;
            while (row && row.tagName && row.tagName.toLowerCase() !== 'tr') {
                row = row.parentNode;
            }
            if (!row) return;

            var action = btn.getAttribute('data-action');
            if (action === 'detalles') {
                openDetalles(row);
                return;
            }
            var ordenId = row.getAttribute('data-id');
            var artesanoId = row.getAttribute('data-artesano-id');
            openAsignar(ordenId, artesanoId);
        });
    }
})();
