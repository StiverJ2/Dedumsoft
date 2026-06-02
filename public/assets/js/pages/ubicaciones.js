/**
 * ============================================================================
 * UBICACIONES — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 */

let ubicacionesTable = null;
let areaOptions = [];

const formatAreaBadge = (area) => {
    const a = (area || 'General').toLowerCase();
    let badge = 'neutral';
    switch (a) {
        case 'produccion':
        case 'producción':
            badge = 'warning';
            break;
        case 'almacen':
        case 'almacén':
            badge = 'info';
            break;
        case 'ventas':
            badge = 'success';
            break;
        case 'oficina':
            badge = 'muted';
            break;
        case 'taller':
            badge = 'danger';
            break;
    }
    const display = DsCrud.escapeHtml(a.charAt(0).toUpperCase() + a.slice(1));
    return `<span class="ds-badge ds-badge--${badge}">${display}</span>`;
};

const formatActivoBadge = (activo) => {
    if (activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
};

const buildUbicacionForm = (data) => {
    data = data || {};
    return DsCrud.field({
        name: 'nombre',
        label: 'Nombre',
        value: data.nombre || '',
        required: true,
        placeholder: 'Ej: Bodega Principal'
    }) +
        DsCrud.field({
            name: 'descripcion',
            label: 'Descripción',
            type: 'textarea',
            value: data.descripcion || '',
            placeholder: 'Descripción opcional...'
        }) +
        DsCrud.field({
            name: 'area_id',
            label: 'Área',
            type: 'select',
            value: data.area_id || 1,
            options: areaOptions
        });
};

const reloadTable = () => {
    if (ubicacionesTable) {
        ubicacionesTable.ajax.reload(null, false);
    }
};

const openCreateModal = () => {
    DsCrud.openModal({
        title: 'Nueva Ubicación',
        body: `<form id="frm-ubicacion">${buildUbicacionForm()}</form>`,
        saveText: 'Crear',
        onSave: (modalEl, close) => {
            const form = modalEl.querySelector('#frm-ubicacion');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const fd = new FormData(form);
            const payload = {};
            fd.forEach((value, key) => {
                payload[key] = value;
            });
            DsCrud.api('api/ubicaciones.php', 'POST', payload, (success, resp) => {
                if (success) {
                    DsCrud.toast('Ubicación creada');
                    reloadTable();
                    close();
                } else {
                    DsCrud.toast(resp.MENSAJE || 'Error al crear', 'error');
                }
            });
        }
    });
};

const openEditModal = (row) => {
    DsCrud.openModal({
        title: 'Editar Ubicación',
        body: `<form id="frm-ubicacion">${buildUbicacionForm(row)}</form>`,
        saveText: 'Guardar',
        onSave: (modalEl, close) => {
            const form = modalEl.querySelector('#frm-ubicacion');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const fd = new FormData(form);
            const payload = {
                id: row.id,
                ...Object.fromEntries(fd)
            };
            DsCrud.api('api/ubicaciones.php', 'PATCH', payload, (success, resp) => {
                if (success) {
                    DsCrud.toast('Ubicación actualizada');
                    reloadTable();
                    close();
                } else {
                    DsCrud.toast(resp.MENSAJE || 'Error al actualizar', 'error');
                }
            });
        }
    });
};

const openDeleteConfirm = (row) => {
    DsCrud.confirm({
        title: 'Eliminar Ubicación',
        message: `¿Desea eliminar la ubicación "${row.nombre}"?`,
        warning: 'Esta acción desactivará la ubicación.',
        confirmText: 'Eliminar',
        onConfirm: () => {
            DsCrud.api('api/ubicaciones.php', 'DELETE', {
                id: row.id
            }, (success, resp) => {
                if (success) {
                    DsCrud.toast('Ubicación eliminada');
                    reloadTable();
                } else {
                    DsCrud.toast(resp.MENSAJE || 'Error al eliminar', 'error');
                }
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', async () => {
    // Cargar opciones desde la API
    try {
        const response = await axios.get('api/opciones.php?tipo=areas');
        areaOptions = response.data.DATOS || [];
    } catch (error) {
        console.error('Error cargando opciones:', error);
        areaOptions = [{
            value: 'General',
            label: 'General'
        },
        {
            value: 'Produccion',
            label: 'Producción'
        },
        {
            value: 'Almacen',
            label: 'Almacén'
        },
        {
            value: 'Ventas',
            label: 'Ventas'
        },
        {
            value: 'Oficina',
            label: 'Oficina'
        },
        {
            value: 'Taller',
            label: 'Taller'
        }
        ];
    }

    let areaFilter = '';
    const applyAreaFilter = () => {
        const areaEl = document.getElementById('ubic-area-modern');
        areaFilter = areaEl ? (areaEl.value || '') : '';
        if (ubicacionesTable) {
            ubicacionesTable.ajax.reload();
        }
    };

    ubicacionesTable = $('#ubicaciones-table').DataTable({
        ajax: {
            url: 'api/ubicaciones.php',
            data: (d) => {
                d.limit = 500;
                d.offset = 0;
                if (areaFilter) d.area_id = areaFilter;
            },
            dataSrc: 'DATOS'
        },
        columns: [{
            data: 'id'
        },
        {
            data: 'nombre'
        },
        {
            data: 'descripcion',
            defaultContent: ''
        },
        {
            data: 'area_nombre',
            render: (data) => formatAreaBadge(data || 'General')
        },
        {
            data: 'activo',
            render: (data) => formatActivoBadge(data)
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (data, type, row) => type === 'display' ? DsCrud.actionButtons(row.id) :
                ''
        }
        ],
        language: {
            url: 'assets/dataTables.es-ES.json'
        }
    });

    // Add button
    document.getElementById('btn-add-ubicacion').addEventListener('click', openCreateModal);
    const areaFilterBtn = document.getElementById('ubic-filtrar-modern');
    const areaClearBtn = document.getElementById('ubic-limpiar-modern');
    const areaSelect = document.getElementById('ubic-area-modern');
    if (areaFilterBtn) {
        areaFilterBtn.addEventListener('click', applyAreaFilter);
    }
    if (areaClearBtn) {
        areaClearBtn.addEventListener('click', () => {
            if (areaSelect) areaSelect.value = '';
            applyAreaFilter();
        });
    }
    if (areaSelect) {
        areaSelect.addEventListener('change', applyAreaFilter);
    }

    // Edit/Delete buttons (delegated)
    document.getElementById('ubicaciones-table').addEventListener('click', (e) => {
        const editBtn = e.target.closest('.ds-action-btn[data-action="edit"]');
        if (editBtn) {
            const row = ubicacionesTable.row(editBtn.closest('tr')).data();
            openEditModal(row);
            return;
        }

        const deleteBtn = e.target.closest('.ds-action-btn[data-action="delete"]');
        if (deleteBtn) {
            const row = ubicacionesTable.row(deleteBtn.closest('tr')).data();
            openDeleteConfirm(row);
        }
    });
});
