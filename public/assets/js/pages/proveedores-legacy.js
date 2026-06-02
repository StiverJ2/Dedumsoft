/**
 * ============================================================================
 * PROVEEDORES — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Este archivo reemplaza el bloque <script> inline de proveedores.php
 * para navegadores legacy (IE8).
 *
 * Depende de: DsCrud (crud-legacy.js)
 * Se asume que window._tipoProveedorOptions fue inyectado por el PHP.
 */

(function () {
    'use strict';

    if (window.DedumTableSort) {
        DedumTableSort.init('proveedores-table');
    }

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

    function buildProveedorFormHtml(d) {
        d = d || {};
        var tipoOpts = window._tipoProveedorOptions || [];
        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') + '" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Tipo <span style="color:red">*</span></label>' +
            selectHtml('tipo_proveedor_id', d.tipo_proveedor_id || 1, tipoOpts, true) + '</div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Contacto</label><input type="text" name="contacto" value="' +
            esc(d.contacto || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Telefono</label><input type="text" name="telefono" value="' +
            esc(d.telefono || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Email</label><input type="text" name="email" value="' +
            esc(d.email || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Direccion</label><input type="text" name="direccion" value="' +
            esc(d.direccion || '') + '" style="width:100%;padding:6px;font-size:14px;"></div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-proveedor'), 'click', function () {
        DsCrud.openModal({
            title: 'Nuevo Proveedor',
            body: '<form id="frm-proveedor">' + buildProveedorFormHtml() + '</form>',
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.apiLegacy('api/catalogos/proveedores.php', 'POST', data, function () {
                    DsCrud.toast('Proveedor creado', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('proveedores-table', {
        onEdit: function (id) {
            DsCrud.apiLegacy('api/catalogos/proveedores.php?id=' + id, 'GET', null, function (res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Proveedor #' + id,
                    body: '<form id="frm-proveedor">' + buildProveedorFormHtml(d) + '</form>',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/catalogos/proveedores.php', 'PATCH', data,
                            function () {
                                DsCrud.toast('Proveedor actualizado', 'success');
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
            DsCrud.confirm('¿Eliminar proveedor #' + id + '?', function () {
                DsCrud.apiLegacy('api/catalogos/proveedores.php', 'DELETE', {
                    id: id
                }, function () {
                    DsCrud.toast('Proveedor eliminado', 'success');
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });
})();
