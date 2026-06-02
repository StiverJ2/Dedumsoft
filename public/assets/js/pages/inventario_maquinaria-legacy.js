/**
 * ============================================================================
 * INVENTARIO MAQUINARIA — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function() {
    'use strict';

    if (window.DedumTableSort) {
        DedumTableSort.init('maq-table');
    }

    var proveedorOptions = window._proveedorOptions || [];
    var ubicacionOptions = window._ubicacionOptions || [];
    var tipoMaquinariaOptions = window._tipoMaquinariaOptions || [];
    var maqEstadoOptions = window._maqEstadoOptions || [];
    var maqInventoryOptions = window._maqInventoryOptions || [];

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

    function buildMaqFormHtml(d, showCompra) {
        d = d || {};
        var maqProveedores = [];
        for (var i = 0; i < proveedorOptions.length; i++) {
            var label = String(proveedorOptions[i].label || '').toLowerCase();
            var isMaq = label.indexOf('(maquinaria)') > -1;
            var isCurrent = String(proveedorOptions[i].value) === String(d.proveedor_id || '');
            if (proveedorOptions[i].value === '' || isMaq || isCurrent) {
                maqProveedores.push(proveedorOptions[i]);
            }
        }
        if (maqProveedores.length === 1) maqProveedores = proveedorOptions;

        var html =
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">SKU / Serial <span style="color:red">*</span></label><input type="text" name="sku" value="' +
            esc(d.sku || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
            selectHtml('tipo_maquinaria_id', d.tipo_maquinaria_id || '', tipoMaquinariaOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Estado <span style="color:red">*</span></label>' +
            selectHtml('estado_id', d.estado_id || 1, maqEstadoOptions, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Ubicacion</label>' +
            selectHtml('ubicacion_id', d.ubicacion_id || '', ubicacionOptions, false) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Proveedor</label>' +
            selectHtml('proveedor_id', d.proveedor_id || '', maqProveedores, false) + '</div>';
        if (showCompra) {
            html +=
                '<div style="margin-bottom:12px;"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
        }
        return html;
    }

    function buildCompraMaqFormHtml(d) {
        d = d || {};
        var invOpts = [{
            value: '',
            label: '-- Seleccione --'
        }].concat(maqInventoryOptions);
        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Maquinaria <span style="color:red">*</span></label>' +
            selectHtml('item_id', d.item_id || '', invOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Cantidad <span style="color:red">*</span></label><input type="text" name="cantidad" value="' +
            esc(d.cantidad || '1') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Motivo</label><input type="text" name="motivo" value="' +
            esc(d.motivo || 'Compra proveedor') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Referencia</label><input type="text" name="referencia" value="' +
            esc(d.referencia || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Fecha</label><input type="datetime-local" name="fecha" value="' +
            esc(d.fecha || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-maquinaria'), 'click', function() {
        DsCrud.openModal({
            title: 'Nueva Maquinaria',
            body: '<form id="frm-maq">' + buildMaqFormHtml({}, true) + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                var registrarCompra = data.registrar_compra === true;
                delete data.registrar_compra;

                DsCrud.apiLegacy('api/inventario_maquinaria.php', 'POST', data, function(res) {
                    if (!registrarCompra) {
                        DsCrud.toast('Maquinaria creada', 'success');
                        DsCrud.closeModal();
                        location.reload();
                        return;
                    }
                    var compraPayload = {
                        tipo_inventario: 'maquinaria',
                        item_id: (res.DATOS && res.DATOS.id ? res.DATOS.id : res
                            .ID),
                        cantidad: 1
                    };
                    DsCrud.apiLegacy('api/compras.php', 'POST', compraPayload,
                    function() {
                        DsCrud.toast('Maquinaria creada y compra registrada',
                            'success');
                        DsCrud.closeModal();
                        location.reload();
                    }, function(e) {
                        DsCrud.toast(
                            'Maquinaria creada, pero no se pudo registrar la compra: ' +
                            e, 'error');
                        DsCrud.closeModal();
                        location.reload();
                    });
                }, function(e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.addEvent(DsCrud.getById('btn-compra-maquinaria'), 'click', function() {
        if (!maqInventoryOptions.length) {
            DsCrud.toast('No hay maquinaria disponible', 'error');
            return;
        }
        DsCrud.openModal({
            title: 'Registrar compra de maquinaria',
            body: '<form id="frm-compra-maq">' + buildCompraMaqFormHtml() + '</form>',
            onSave: function(modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                data.tipo_inventario = 'maquinaria';
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

    DsCrud.initLegacyTable('maq-table', {
        onEdit: function(id) {
            DsCrud.apiLegacy('api/inventario_maquinaria.php?id=' + id, 'GET', null, function(res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Maquinaria #' + id,
                    body: '<form id="frm-maq">' + buildMaqFormHtml(d, false) +
                        '</form>',
                    onSave: function(modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/inventario_maquinaria.php', 'PATCH',
                            data,
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
            DsCrud.confirm('Eliminar maquinaria #' + id + '?', function() {
                DsCrud.apiLegacy('api/inventario_maquinaria.php', 'DELETE', {
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
