/**
 * ============================================================================
 * INVENTARIO INSUMOS — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function() {
    'use strict';

    if (window.DedumTableSort) {
        DedumTableSort.init('insumos-table');
    }

    var categoriaOptions = window._categoriaOptions || [];
    var proveedorOptions = window._proveedorOptions || [];
    var insumoInventoryOptions = window._insumoInventoryOptions || [];

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
                        item_id: (res.DATOS && res.DATOS.id ? res.DATOS.id : res.ID),
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
