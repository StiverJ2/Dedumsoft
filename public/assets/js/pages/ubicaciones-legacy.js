/**
 * ============================================================================
 * UBICACIONES — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function () {
    'use strict';

    if (window.DedumTableSort) DedumTableSort.init('ubicaciones-table');

    var areaOptions = window._areaOptions || [];

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

    function buildUbicacionFormHtml(d) {
        d = d || {};
        return '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Nombre <span style="color:red">*</span></label><input type="text" name="nombre" value="' +
            esc(d.nombre || '') +
            '" placeholder="Ej: Bodega Principal" style="width:100%;padding:6px;font-size:14px;" required></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Descripción</label><textarea name="descripcion" placeholder="Descripción opcional..." style="width:100%;padding:6px;font-size:14px;min-height:60px;">' +
            esc(d.descripcion || '') + '</textarea></div>' +
            '<div style="margin-bottom:12px;"><label style="display:block;margin-bottom:4px;font-weight:bold;">Área</label>' +
            selectHtml('area_id', d.area_id || 1, areaOptions, false) + '</div>';
    }

    DsCrud.addEvent(DsCrud.getById('btn-add-ubicacion'), 'click', function () {
        DsCrud.openModal({
            title: 'Nueva Ubicación',
            body: '<form id="frm-ubicacion">' + buildUbicacionFormHtml() + '</form>',
            saveText: 'Crear',
            onSave: function (modal) {
                if (!DsCrud.validateForm(modal)) return;
                var data = DsCrud.getFormData(modal);
                DsCrud.apiLegacy('api/ubicaciones.php', 'POST', data, function () {
                    DsCrud.toast('Ubicación creada', 'success');
                    DsCrud.closeModal();
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    });

    DsCrud.initLegacyTable('ubicaciones-table', {
        onEdit: function (id) {
            DsCrud.apiLegacy('api/ubicaciones.php?id=' + id, 'GET', null, function (res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar Ubicación #' + id,
                    body: '<form id="frm-ubicacion">' + buildUbicacionFormHtml(d) +
                        '</form>',
                    saveText: 'Guardar',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/ubicaciones.php', 'PATCH', data,
                            function () {
                                DsCrud.toast('Ubicación actualizada',
                                    'success');
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
            DsCrud.confirm('¿Eliminar ubicación #' + id + '?', function () {
                DsCrud.apiLegacy('api/ubicaciones.php', 'DELETE', {
                    id: id
                }, function () {
                    DsCrud.toast('Ubicación eliminada', 'success');
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e, 'error');
                });
            });
        }
    });
})();
