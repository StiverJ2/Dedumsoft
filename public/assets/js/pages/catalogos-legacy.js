/**
 * ============================================================================
 * CATALOGOS — JavaScript Legacy (IE8 Compatible)
 * ============================================================================
 *
 * Depende de: DsCrud (crud-legacy.js)
 */

(function () {
    'use strict';

    if (!window.DsCrud) return;

    var catalogKey = window._catalogKey || '';
    var cfg = window._catalogConfig || {};

    function buildForm(d) {
        d = d || {};
        var html = '';
        for (var i = 0; i < cfg.fields.length; i++) {
            var f = cfg.fields[i];
            var value = d[f.name];
            if (value === null || value === undefined) value = '';
            var opts = {
                name: f.name,
                label: f.label,
                required: !!f.required,
                value: value,
                placeholder: f.placeholder || ''
            };
            if (f.input === 'textarea') {
                opts.type = 'textarea';
            } else if (f.input) {
                opts.type = f.input;
            }
            if (f.attrs) {
                opts.attrs = f.attrs;
            }
            html += DsCrud.field(opts);
        }
        return html;
    }

    var createBtn = DsCrud.getById('catalog-create-btn');
    if (createBtn) {
        DsCrud.addEvent(createBtn, 'click', function () {
            DsCrud.openModal({
                title: 'Nuevo - ' + cfg.label,
                body: '<form id="frm-catalog">' + buildForm() + '</form>',
                saveText: 'Crear',
                onSave: function (modal) {
                    if (!DsCrud.validateForm(modal)) return;
                    var data = DsCrud.getFormData(modal);
                    DsCrud.apiLegacy('api/catalogos/maestros.php?catalog=' + catalogKey, 'POST', data, function () {
                        DsCrud.toast('Registro creado', 'success');
                        DsCrud.closeModal();
                        location.reload();
                    }, function (e) {
                        DsCrud.toast(e || 'Error al crear', 'error');
                    });
                }
            });
        });
    }

    DsCrud.initLegacyTable('catalogos-table', {
        onEdit: function (id) {
            DsCrud.apiLegacy('api/catalogos/maestros.php?catalog=' + catalogKey, 'GET', { id: id }, function (res) {
                var d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : {};
                DsCrud.openModal({
                    title: 'Editar - ' + cfg.label + ' #' + id,
                    body: '<form id="frm-catalog">' + buildForm(d) + '</form>',
                    saveText: 'Guardar',
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = id;
                        DsCrud.apiLegacy('api/catalogos/maestros.php?catalog=' + catalogKey, 'PATCH', data, function () {
                            DsCrud.toast('Registro actualizado', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e || 'Error al actualizar', 'error');
                        });
                    }
                });
            }, function (e) {
                DsCrud.toast(e || 'Error al cargar', 'error');
            });
        },
        onDelete: function (id) {
            DsCrud.confirm('¿Eliminar registro #' + id + '?', function () {
                DsCrud.apiLegacy('api/catalogos/maestros.php?catalog=' + catalogKey, 'DELETE', { id: id }, function () {
                    DsCrud.toast('Registro eliminado', 'success');
                    location.reload();
                }, function (e) {
                    DsCrud.toast(e || 'Error al eliminar', 'error');
                });
            });
        }
    });
})();
