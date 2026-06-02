/**
 * ============================================================================
 * PROVEEDORES — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 *
 * Este archivo reemplaza el bloque <script> inline de proveedores.php.
 * Depende de: jQuery, DataTables, Axios, DsCrud (crud.js)
 */

document.addEventListener('DOMContentLoaded', async () => {
    'use strict';

    let proveedoresTable;
    let tipoOptions = [];
    let tipoFilter = '';

    // ========================================================================
    // FILTROS
    // ========================================================================

    const applyProveedorFilters = () => {
        const tipoEl = document.getElementById('prov-tipo-modern');
        tipoFilter = tipoEl ? (tipoEl.value || '') : '';
        if (proveedoresTable) {
            proveedoresTable.ajax.reload();
        }
    };

    // ========================================================================
    // CARGAR OPCIONES DE TIPO
    // ========================================================================

    try {
        const response = await axios.get('api/catalogos/opciones.php?tipo=tipos_proveedor');
        tipoOptions = response.data.DATOS || [];
    } catch (error) {
        console.error('Error cargando opciones:', error);
        tipoOptions = [
            { value: 'oro', label: 'Oro' },
            { value: 'insumos', label: 'Insumos' },
            { value: 'maquinaria', label: 'Maquinaria' }
        ];
    }

    // ========================================================================
    // FORMULARIO
    // ========================================================================

    const buildProveedorForm = (data) => {
        data = data || {};
        return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre,
                required: true
            }) +
            DsCrud.field({
                name: 'tipo_proveedor_id',
                label: 'Tipo',
                type: 'select',
                value: data.tipo_proveedor_id,
                options: tipoOptions,
                required: true
            }) +
            DsCrud.field({
                name: 'contacto',
                label: 'Contacto',
                value: data.contacto
            }) +
            DsCrud.field({
                name: 'telefono',
                label: 'Teléfono',
                value: data.telefono
            }) +
            DsCrud.field({
                name: 'email',
                label: 'Email',
                type: 'email',
                value: data.email
            }) +
            DsCrud.field({
                name: 'direccion',
                label: 'Dirección',
                value: data.direccion
            });
    };

    // ========================================================================
    // MODALES
    // ========================================================================

    const openCreateModal = () => {
        DsCrud.openModal({
            title: 'Nuevo Proveedor',
            body: '<form id="frm-proveedor">' + buildProveedorForm() + '</form>',
            onSave: (modalEl) => {
                const form = modalEl.querySelector('#frm-proveedor');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                const fd = new FormData(form);
                const payload = Object.fromEntries(fd);
                DsCrud.api('api/catalogos/proveedores.php', 'POST', payload, (res) => {
                    DsCrud.toast('Proveedor creado', 'success');
                    proveedoresTable.ajax.reload();
                    DsCrud.closeModal();
                }, (err) => {
                    DsCrud.toast(err, 'error');
                });
            }
        });
    };

    const openEditModal = (row) => {
        DsCrud.api('api/catalogos/proveedores.php?id=' + row.id, 'GET', null, (res) => {
            const prov = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Proveedor #' + prov.id,
                body: '<form id="frm-proveedor">' + buildProveedorForm(prov) + '</form>',
                onSave: (modalEl) => {
                    const form = modalEl.querySelector('#frm-proveedor');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    const fd = new FormData(form);
                    const payload = {
                        id: prov.id,
                        ...Object.fromEntries(fd)
                    };
                    DsCrud.api('api/catalogos/proveedores.php', 'PATCH', payload, (res) => {
                        DsCrud.toast('Proveedor actualizado', 'success');
                        proveedoresTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (err) => {
                        DsCrud.toast(err, 'error');
                    });
                }
            });
        });
    };

    const openDeleteConfirm = (row) => {
        DsCrud.confirm('¿Eliminar proveedor "' + row.nombre + '"?', () => {
            DsCrud.api('api/catalogos/proveedores.php', 'DELETE', {
                id: row.id
            }, (res) => {
                DsCrud.toast('Proveedor eliminado', 'success');
                proveedoresTable.ajax.reload();
            }, (err) => {
                DsCrud.toast(err, 'error');
            });
        });
    };

    // ========================================================================
    // DATATABLE
    // ========================================================================

    proveedoresTable = $('#proveedores-table').DataTable({
        ajax: {
            url: 'api/catalogos/proveedores.php',
            data: (d) => {
                d.limit = 500;
                d.offset = 0;
                if (tipoFilter) d.tipo_id = tipoFilter;
            },
            dataSrc: 'DATOS'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'tipo_nombre' },
            { data: 'contacto', defaultContent: '' },
            { data: 'telefono', defaultContent: '' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (data, type, row) => {
                    if (type !== 'display') return '';
                    return DsCrud.actionButtons(row.id);
                }
            }
        ],
        language: {
            url: 'assets/dataTables.es-ES.json'
        }
    });

    // ========================================================================
    // EVENT LISTENERS
    // ========================================================================

    document.getElementById('btn-add-proveedor').addEventListener('click', openCreateModal);

    const tipoFilterBtn = document.getElementById('prov-filtrar-modern');
    const tipoClearBtn = document.getElementById('prov-limpiar-modern');
    const tipoSelect = document.getElementById('prov-tipo-modern');

    if (tipoFilterBtn) {
        tipoFilterBtn.addEventListener('click', applyProveedorFilters);
    }
    if (tipoClearBtn) {
        tipoClearBtn.addEventListener('click', () => {
            if (tipoSelect) tipoSelect.value = '';
            applyProveedorFilters();
        });
    }
    if (tipoSelect) {
        tipoSelect.addEventListener('change', applyProveedorFilters);
    }

    document.getElementById('proveedores-table').addEventListener('click', (e) => {
        const editBtn = e.target.closest('.ds-action-btn[data-action="edit"]');
        if (editBtn) {
            const row = proveedoresTable.row(editBtn.closest('tr')).data();
            openEditModal(row);
            return;
        }

        const deleteBtn = e.target.closest('.ds-action-btn[data-action="delete"]');
        if (deleteBtn) {
            const row = proveedoresTable.row(deleteBtn.closest('tr')).data();
            openDeleteConfirm(row);
        }
    });
});
