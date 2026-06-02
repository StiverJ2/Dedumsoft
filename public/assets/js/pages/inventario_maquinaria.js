/**
 * ============================================================================
 * INVENTARIO MAQUINARIA — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 */

window.DEDUMSOFT_ICON_MODE = 'emoji';

const esc = (value) => DsCrud.escapeHtml(value === null || value === undefined ? '' : String(value));
const sanitizeColor = (value) => {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(raw)) return raw;
    if (/^(rgb|rgba)\([0-9.,\s%]+\)$/i.test(raw)) return raw;
    if (/^[a-z]+$/i.test(raw)) return raw;
    return '';
};

const formatMaqTipo = (tipo) => {
    if (!tipo) return '';
    const key = String(tipo).toLowerCase();
    const labelRaw = String(tipo).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const label = esc(labelRaw);
    let cls = 'ds-badge--neutral';
    if (key.indexOf('corte') > -1 || key.indexOf('sierra') > -1) cls = 'ds-badge--danger';
    else if (key.indexOf('pulido') > -1 || key.indexOf('acabado') > -1) cls = 'ds-badge--info';
    else if (key.indexOf('fundicion') > -1 || key.indexOf('horno') > -1) cls = 'ds-badge--warning';
    else if (key.indexOf('soldadura') > -1) cls = 'ds-badge--success';
    return `<span class="ds-badge ${cls}">${label}</span>`;
};

const formatMaqEstado = (estadoNombre, estadoColor) => {
    if (!estadoNombre) return '';
    const labelRaw = String(estadoNombre).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const label = esc(labelRaw);
    const safeColor = sanitizeColor(estadoColor);
    if (safeColor) {
        return `<span class="ds-badge" style="background-color:${safeColor}">${label}</span>`;
    }
    const key = String(estadoNombre).toLowerCase();
    let cls = 'ds-badge--neutral';
    if (key === 'operativa') cls = 'ds-badge--success';
    else if (key === 'mantenimiento') cls = 'ds-badge--warning';
    else if (key === 'averiada') cls = 'ds-badge--danger';
    else if (key === 'fuera de servicio' || key.indexOf('fuera') > -1) cls = 'ds-badge--muted';
    return `<span class="ds-badge ${cls}">${label}</span>`;
};

$(() => {
    const dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    let maqTable;
    let proveedoresCache = [];
    let ubicacionesCache = [];
    let tipoMaquinariaCache = [];
    let maqEstadoOptions = [];
    let estadoFilter = '';

    const applyMaqFilter = () => {
        const estadoEl = $('#maq-estado-modern');
        estadoFilter = estadoEl.length ? estadoEl.val() : '';
        if (maqTable) {
            maqTable.ajax.reload();
        }
    };

    // Cargar todas las opciones en paralelo
    Promise.all([
        axios.get('api/catalogos/opciones.php?tipo=estados_maquinaria'),
        axios.get('api/catalogos/proveedores.php?limit=500'),
        axios.get('api/catalogos/ubicaciones.php?limit=500'),
        axios.get('api/inventario/tipos_maquinaria.php')
    ]).then(([resEstados, resProv, resUbi, resTipos]) => {
        maqEstadoOptions = (resEstados.data.DATOS || []).map(e => ({
            value: e.value,
            label: e.label,
            color: e.color
        }));

        proveedoresCache = (resProv.data.DATOS || []).map(p => {
            const tipoRaw = p.tipo_nombre || p.tipo || '';
            return {
                value: p.id,
                label: `${p.nombre}${tipoRaw ? ' (' + tipoRaw + ')' : ''}`,
                tipo: String(tipoRaw || '').toLowerCase()
            };
        });

        ubicacionesCache = (resUbi.data.DATOS || []).map(u => ({
            value: u.id,
            label: u.nombre
        }));

        tipoMaquinariaCache = (resTipos.data.DATOS || []).map(t => ({
            value: t.id,
            label: t.nombre
        }));

        initMaqTable();
    }).catch(error => {
        console.error('Error cargando datos:', error);
        initMaqTable();
    });

    const buildMaqForm = (data) => {
        data = data || {};
        const provOpts = [{
            value: '',
            label: '-- Sin proveedor --'
        }].concat(
            proveedoresCache.filter(p => {
                const isMaq = p.tipo === 'maquinaria';
                const isCurrent = String(p.value) === String(data.proveedor_id || '');
                return isMaq || !data.id || isCurrent;
            })
        );
        const ubOpts = [{
            value: '',
            label: '-- Sin ubicacion --'
        }].concat(ubicacionesCache);
        const tipoOpts = [{
            value: '',
            label: '-- Seleccione tipo --'
        }].concat(tipoMaquinariaCache);
        return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre,
                required: true
            }) +
            DsCrud.field({
                name: 'sku',
                label: 'SKU / Serial',
                value: data.sku,
                required: true
            }) +
            DsCrud.field({
                name: 'tipo_maquinaria_id',
                label: 'Tipo',
                type: 'select',
                value: data.tipo_maquinaria_id,
                options: tipoOpts,
                required: true
            }) +
            DsCrud.field({
                name: 'estado_id',
                label: 'Estado',
                type: 'select',
                value: data.estado_id,
                options: maqEstadoOptions,
                required: true
            }) +
            DsCrud.field({
                name: 'ubicacion_id',
                label: 'Ubicacion',
                type: 'select',
                value: data.ubicacion_id,
                options: ubOpts
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    };

    const openMaqCreate = () => {
        const compraToggle =
            '<div class="ds-form-group"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
        DsCrud.openModal({
            title: 'Nueva Maquinaria',
            body: '<form id="frm-maq">' + buildMaqForm() + compraToggle + '</form>',
            onSave: (m) => {
                const f = m.querySelector('#frm-maq');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {};
                fd.forEach((v, k) => {
                    payload[k] = v;
                });
                const registrarCompra = payload.registrar_compra === 'on';
                delete payload.registrar_compra;

                DsCrud.api('api/inventario/maquinaria.php', 'POST', payload, (success, resp) => {
                    if (!registrarCompra) {
                        DsCrud.toast('Maquinaria creada', 'success');
                        maqTable.ajax.reload();
                        DsCrud.closeModal();
                        return;
                    }
                    const compraPayload = {
                        tipo_inventario: 'maquinaria',
                        item_id: (resp.DATOS && resp.DATOS.id ? resp.DATOS.id : resp.ID),
                        cantidad: 1
                    };
                    DsCrud.api('api/produccion/compras.php', 'POST', compraPayload, () => {
                        DsCrud.toast('Maquinaria creada y compra registrada',
                            'success');
                        maqTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (e) => {
                        DsCrud.toast(
                            'Maquinaria creada, pero no se pudo registrar la compra: ' +
                            e, 'error');
                        maqTable.ajax.reload();
                        DsCrud.closeModal();
                    });
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    const openMaqEdit = (row) => {
        DsCrud.api('api/inventario/maquinaria.php?id=' + row.id, 'GET', null, (res) => {
            const d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Maquinaria #' + d.id,
                body: '<form id="frm-maq">' + buildMaqForm(d) + '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-maq');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { id: d.id };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    DsCrud.api('api/inventario/maquinaria.php', 'PATCH', payload,
                        () => {
                            DsCrud.toast('Maquinaria actualizada', 'success');
                            maqTable.ajax.reload();
                            DsCrud.closeModal();
                        },
                        (e) => {
                            DsCrud.toast(e, 'error');
                        });
                }
            });
        });
    };

    const openMaqDelete = (row) => {
        DsCrud.confirm('Eliminar maquinaria "' + row.nombre + '"?', () => {
            DsCrud.api('api/inventario/maquinaria.php', 'DELETE', {
                id: row.id
            }, () => {
                DsCrud.toast('Maquinaria eliminada', 'success');
                maqTable.ajax.reload();
            }, (e) => {
                DsCrud.toast(e, 'error');
            });
        });
    };

    const buildCompraMaqForm = (options, data) => {
        data = data || {};
        return DsCrud.field({
                name: 'item_id',
                label: 'Maquinaria',
                type: 'select',
                value: data.item_id,
                options: options,
                required: true
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
                type: 'number',
                value: data.cantidad || 1,
                required: true,
                attrs: 'step="1" min="1"'
            }) +
            DsCrud.field({
                name: 'motivo',
                label: 'Motivo',
                value: data.motivo || 'Compra proveedor'
            }) +
            DsCrud.field({
                name: 'referencia',
                label: 'Referencia',
                value: data.referencia
            }) +
            DsCrud.field({
                name: 'fecha',
                label: 'Fecha',
                type: 'datetime-local',
                value: data.fecha
            });
    };

    const openMaqCompra = () => {
        axios.get('api/inventario/maquinaria.php?limit=500').then((res) => {
            const items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
            if (!items.length) {
                DsCrud.toast('No hay maquinaria disponible', 'warning');
                return;
            }
            const options = [{
                value: '',
                label: '-- Seleccione --'
            }].concat(items.map((it) => {
                let label = (it.sku ? it.sku + ' - ' : '') + (it.nombre || 'Maquinaria');
                label += ' #' + it.id;
                return {
                    value: it.id,
                    label: label
                };
            }));

            DsCrud.openModal({
                title: 'Registrar compra de maquinaria',
                body: '<form id="frm-compra-maq">' + buildCompraMaqForm(options) +
                    '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-compra-maq');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { tipo_inventario: 'maquinaria' };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    if (payload.fecha) {
                        payload.fecha = payload.fecha.replace('T', ' ');
                    }
                    DsCrud.api('api/produccion/compras.php', 'POST', payload, () => {
                        DsCrud.toast('Compra registrada', 'success');
                        maqTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (e) => {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        }).catch(() => {
            DsCrud.toast('Error cargando inventario', 'error');
        });
    };

    const initMaqTable = () => {
        maqTable = $('#maq-table').DataTable({
            ajax: {
                url: 'api/inventario/maquinaria.php',
                data: (d) => {
                    d.limit = 500;
                    d.offset = 0;
                    if (estadoFilter) d.estado_id = estadoFilter;
                },
                dataSrc: 'DATOS'
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'sku',
                    defaultContent: ''
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'tipo_nombre',
                    render: formatMaqTipo
                },
                {
                    data: null,
                    render: (data, type, row) => {
                        return formatMaqEstado(row.estado_nombre, row.estado_color);
                    }
                },
                {
                    data: 'ubicacion_nombre',
                    defaultContent: ''
                },
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
            language: dtLang
        });

        $('#btn-add-maquinaria').on('click', openMaqCreate);
        $('#btn-compra-maquinaria').on('click', openMaqCompra);
        $('#maq-filtrar-modern').on('click', applyMaqFilter);
        $('#maq-limpiar-modern').on('click', () => {
            const estadoEl = $('#maq-estado-modern');
            if (estadoEl.length) estadoEl.val('');
            applyMaqFilter();
        });
        $('#maq-estado-modern').on('change', applyMaqFilter);
        $('#maq-table').on('click', '.ds-action-btn[data-action="edit"]', (e) => {
            const row = maqTable.row($(e.currentTarget).closest('tr')).data();
            openMaqEdit(row);
        });
        $('#maq-table').on('click', '.ds-action-btn[data-action="delete"]', (e) => {
            const row = maqTable.row($(e.currentTarget).closest('tr')).data();
            openMaqDelete(row);
        });
    };
});
