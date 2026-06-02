/**
 * ============================================================================
 * PRODUCCION — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 */

const esc = (value) => DsCrud.escapeHtml(value === null || value === undefined ? '' : String(value));
const formatStatus = (v) => {
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
const formatFecha = (v) => {
    if (!v) return '';
    return String(v).replace('T', ' ').split('.')[0];
};

$(() => {
    let ordenesTable;
    let artesanosCache = [];
    let productosCache = [];
    let prioridadesCache = [];
    let estadoFilter = '';

    const applyOrdenFilters = () => {
        const estadoEl = $('#orden-estado-modern');
        estadoFilter = estadoEl.length ? estadoEl.val() : '';
        if (ordenesTable) {
            ordenesTable.ajax.reload();
        }
    };

    axios.get('api/catalogos/opciones.php?tipo=artesanos').then((res) => {
        artesanosCache = (res.data.DATOS || []).map((a) => {
            let label = a.label || '';
            if (!label) {
                const fullName = ((a.nombre || '') + ' ' + (a.apellido || '')).replace(/\s+/g, ' ').trim();
                label = fullName || (a.nombre || '');
                if (a.especialidades) {
                    label += ' - ' + a.especialidades;
                }
            }
            return {
                value: a.value || a.id,
                label: label
            };
        });
    }).catch(() => {
        artesanosCache = [];
    });

    axios.get('api/catalogos/opciones.php?tipo=productos').then((res) => {
        productosCache = (res.data.DATOS || []).map((p) => {
            return {
                value: p.value || p.id,
                label: p.label || p.nombre || ''
            };
        });
    }).catch(() => {
        productosCache = [];
    });

    axios.get('api/catalogos/opciones.php?tipo=prioridades').then((res) => {
        prioridadesCache = (res.data.DATOS || []).map((p) => {
            return {
                value: p.value || p.id,
                label: p.label || p.nombre || ''
            };
        });
    }).catch(() => {
        prioridadesCache = [];
    });

    ordenesTable = $('#ordenes-table').DataTable({
        ajax: {
            url: 'api/produccion/ordenes.php',
            data: (d) => {
                d.limit = 100;
                d.offset = 0;
                if (estadoFilter) d.estado = estadoFilter;
            },
            dataSrc: 'DATOS'
        },
        columns: [
            { data: 'id' },
            { data: 'producto_nombre' },
            { data: 'artesano_nombre', defaultContent: '' },
            { data: 'estado', render: formatStatus },
            { data: 'fecha_inicio', render: formatFecha },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (data, type) => {
                    if (type !== 'display') return '';
                    return '<button type="button" class="ds-action-btn" data-action="detalles" title="Ver detalles">ℹ️</button>' +
                        '<button type="button" class="ds-action-btn" data-action="asignar" title="Asignar artesano">👤➕</button>';
                }
            }
        ],
        language: { url: 'assets/dataTables.es-ES.json' }
    });

    const openDetalles = (row) => {
        if (!row) return;
        const producto = DsCrud.escapeHtml(row.producto_nombre || '');
        const artesano = DsCrud.escapeHtml(row.artesano_nombre || '');
        const estado = DsCrud.escapeHtml((row.estado || '').toString().replace(/_/g, ' ').toUpperCase());
        const prioridad = DsCrud.escapeHtml(row.prioridad || '');
        const observaciones = DsCrud.escapeHtml(row.observaciones_terminada || row.observaciones || '');
        const body = '<div class="ds-detail-list">' +
            '<p><strong>Producto:</strong> ' + producto + '</p>' +
            '<p><strong>Cantidad:</strong> ' + DsCrud.escapeHtml(row.cantidad || '') + '</p>' +
            '<p><strong>Artesano:</strong> ' + artesano + '</p>' +
            '<p><strong>Estado:</strong> ' + estado + '</p>' +
            '<p><strong>Prioridad:</strong> ' + prioridad + '</p>' +
            '<p><strong>Fecha creación:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_creacion)) + '</p>' +
            '<p><strong>Fecha inicio:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_inicio)) + '</p>' +
            '<p><strong>Fecha fin estimada:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_fin_estimada)) + '</p>' +
            '<p><strong>Fecha fin real:</strong> ' + DsCrud.escapeHtml(formatFecha(row.fecha_fin_real)) + '</p>' +
            '<p><strong>Observaciones:</strong> ' + (observaciones || '-') + '</p>' +
            '</div>';
        DsCrud.openModal({
            title: 'Detalles de orden #' + row.id,
            body: body,
            saveText: 'Cerrar',
            cancelText: 'Cerrar',
            onSave: () => {
                DsCrud.closeModal();
            }
        });
    };

    const getDefaultPrioridad = () => {
        let valor = '';
        for (let i = 0; i < prioridadesCache.length; i++) {
            if (String(prioridadesCache[i].value) === '2') {
                valor = prioridadesCache[i].value;
                break;
            }
        }
        if (!valor && prioridadesCache.length) {
            valor = prioridadesCache[0].value;
        }
        return valor;
    };

    const openCrear = () => {
        if (!productosCache.length) {
            DsCrud.toast('No hay productos disponibles', 'error');
            return;
        }
        const productoOpts = [{ value: '', label: '-- Seleccione --' }].concat(productosCache);
        const prioridadOpts = [{ value: '', label: '-- Seleccione --' }].concat(prioridadesCache);
        const artesanoOpts = [{ value: '', label: '-- Sin asignar --' }].concat(artesanosCache);
        const prioridadDefault = getDefaultPrioridad();

        const body = '<form id="frm-crear-orden">' +
            DsCrud.field({
                name: 'producto_id',
                label: 'Producto',
                type: 'select',
                options: productoOpts,
                required: true
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
                type: 'number',
                value: 1,
                required: true,
                attrs: 'min="1" step="1"'
            }) +
            DsCrud.field({
                name: 'prioridad_id',
                label: 'Prioridad',
                type: 'select',
                value: prioridadDefault,
                options: prioridadOpts
            }) +
            DsCrud.field({
                name: 'artesano_id',
                label: 'Asignar artesano',
                type: 'select',
                options: artesanoOpts
            }) +
            DsCrud.field({
                name: 'observaciones',
                label: 'Observaciones',
                type: 'textarea',
                placeholder: 'Opcional'
            }) +
            '</form>';

        DsCrud.openModal({
            title: 'Crear orden',
            body: body,
            saveText: 'Crear',
            cancelText: 'Cancelar',
            onSave: (m) => {
                const f = m.querySelector('#frm-crear-orden');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const productoId = parseInt(fd.get('producto_id'), 10) || 0;
                const cantidad = parseInt(fd.get('cantidad'), 10) || 0;
                const prioridadId = parseInt(fd.get('prioridad_id'), 10) || 0;
                const artesanoId = parseInt(fd.get('artesano_id'), 10) || 0;
                const observaciones = (fd.get('observaciones') || '').toString().trim();

                if (productoId <= 0 || cantidad <= 0) {
                    DsCrud.toast('Producto y cantidad son requeridos', 'error');
                    return;
                }

                const payload = {
                    producto_id: productoId,
                    cantidad: cantidad,
                    prioridad_id: prioridadId > 0 ? prioridadId : null,
                    artesano_id: artesanoId > 0 ? artesanoId : null,
                    observaciones: observaciones ? observaciones : null
                };

                DsCrud.api('api/produccion/ordenes.php', 'POST', payload, () => {
                    DsCrud.toast('Orden creada', 'success');
                    ordenesTable.ajax.reload();
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    const openAsignar = (row) => {
        if (!artesanosCache.length) {
            DsCrud.toast('No hay artesanos disponibles', 'error');
            return;
        }
        const opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosCache);
        const body = '<form id="frm-asignar">' +
            DsCrud.field({
                name: 'artesano_id',
                label: 'Artesano',
                type: 'select',
                value: row.artesano_id || '',
                options: opts,
                required: true
            }) +
            '</form>';
        DsCrud.openModal({
            title: 'Asignar artesano - Orden #' + row.id,
            body: body,
            onSave: (m) => {
                const f = m.querySelector('#frm-asignar');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {
                    id: row.id,
                    artesano_id: fd.get('artesano_id')
                };
                DsCrud.api('api/produccion/ordenes.php', 'PATCH', payload, () => {
                    DsCrud.toast('Orden actualizada', 'success');
                    ordenesTable.ajax.reload();
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    $('#btn-add-orden').on('click', openCrear);
    $('#orden-filtrar-modern').on('click', applyOrdenFilters);
    $('#orden-limpiar-modern').on('click', () => {
        const estadoEl = $('#orden-estado-modern');
        if (estadoEl.length) estadoEl.val('');
        applyOrdenFilters();
    });
    $('#orden-estado-modern').on('change', applyOrdenFilters);

    $('#ordenes-table').on('click', '.ds-action-btn[data-action="asignar"]', (e) => {
        const row = ordenesTable.row($(e.currentTarget).closest('tr')).data();
        if (row) {
            openAsignar(row);
        }
    });

    $('#ordenes-table').on('click', '.ds-action-btn[data-action="detalles"]', (e) => {
        const row = ordenesTable.row($(e.currentTarget).closest('tr')).data();
        if (row) {
            openDetalles(row);
        }
    });
});
