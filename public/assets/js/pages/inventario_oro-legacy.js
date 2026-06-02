/**
 * ============================================================================
 * INVENTARIO ORO — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function () {
    'use strict';

    if (window.DedumTableSort) {
        DedumTableSort.init('oro-table');
    }

    var oroTipoOptions = window._oroTipoOptions || [];
    var proveedorOptions = window._proveedorOptions || [];
    var oroInventoryOptions = window._oroInventoryOptions || [];

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

    function buildOroFormHtml(d) {
        d = d || {};
        var oroProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            if (proveedorOptions[i].value === '' || proveedorOptions[i].label.indexOf('(oro)') > -1) {
                oroProveedores.push(proveedorOptions[i]);
            }
        }
        if (oroProveedores.length === 1) oroProveedores = proveedorOptions;

        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
            selectHtml('tipo_oro_id', d.tipo_oro_id || 1, oroTipoOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Peso (gramos) <span style="color:red">*</span></label><input type="text" name="peso_gramos" value="' +
            esc(d.peso_gramos || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Precio/gramo <span style="color:red">*</span></label><input type="text" name="precio_gramo" value="' +
            esc(d.precio_gramo || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', oroProveedores, false) + '</div>';
    }

    function buildCompraOroFormHtml(d) {
        d = d || {};
        var invOpts = [{ value: '', label: '-- Seleccione --' }].concat(oroInventoryOptions);
        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Inventario de oro <span style="color:red">*</span></label>' +
            selectHtml('item_id', d.item_id || '', invOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad (gramos) <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
            esc(d.cantidad || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Motivo</label><input type="text" name="motivo" value="' +
            esc(d.motivo || 'Compra proveedor') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Referencia</label><input type="text" name="referencia" value="' +
            esc(d.referencia || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Fecha</label><input type="datetime-local" name="fecha" value="' +
            esc(d.fecha || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-oro'), 'click', function () {
        DsCrud.openModal({
            title: 'Nuevo Inventario Oro',
            body: '<form id="frm-oro">' + buildOroFormHtml() + '</form>',
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.apiLegacy('api/inventario_oro.php', 'POST', data, function () {
                    DsCrud.toast('Oro creado', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.addEvent(DsCrud.getById('btn-compra-oro'), 'click', function () {
        if (!oroInventoryOptions.length) {
            DsCrud.toast('No hay inventario de oro disponible', 'error');
            return;
        }
        DsCrud.openModal({
            title: 'Registrar compra de oro',
            body: '<form id="frm-compra-oro">' + buildCompraOroFormHtml() + '</form>',
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                data.tipo_inventario = 'oro';
                if (data.fecha) {
                    data.fecha = data.fecha.replace('T', ' ');
                }
                DsCrud.apiLegacy('api/compras.php', 'POST', data, function () {
                    DsCrud.toast('Compra registrada', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('oro-table', {
        onEdit: function (id) {
            DsCrud.apiLegacy('api/inventario_oro.php?id=' + id, 'GET', null, function (res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Oro #' + id,
                    body: '<form id="frm-oro">' + buildOroFormHtml(d) + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/inventario_oro.php', 'PATCH', data,
                            function () {
                                DsCrud.toast('Oro actualizado', 'success');
                                DsCrud.closeModal();
                                location.reload();
                            },
                            function (e) {
                                DsCrud.toast(e, 'error');
                            });
                    }
                });
            }, function (e) {
                DsCrud.toast('Error: ' + e, 'error');
            });
        },
        onDelete: function (id) {
            DsCrud.confirm('Eliminar oro #' + id + '?', function () {
                DsCrud.apiLegacy('api/inventario_oro.php', 'DELETE', {
                    id: id
                }, function () {
                    DsCrud.toast('Oro eliminado', 'success');
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });
})();
