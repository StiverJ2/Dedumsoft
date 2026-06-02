/**
 * ============================================================================
 * INVENTARIO ORO — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 */

window.DEDUMSOFT_ICON_MODE = 'emoji';

$(() => {
    const dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    let oroTable;
    let proveedoresCache = [];
    let oroTipoOptions = [];
    let tipoFilter = '';

    const applyOroFilters = () => {
        const tipoEl = $('#oro-tipo-modern');
        tipoFilter = tipoEl.length ? tipoEl.val() : '';
        if (oroTable) {
            oroTable.ajax.reload();
        }
    };

    // Cargar opciones y proveedores en paralelo
    Promise.all([
        axios.get('api/catalogos/opciones.php?tipo=tipos_oro'),
        axios.get('api/catalogos/proveedores.php?limit=500')
    ]).then(([resOpciones, resProv]) => {
        oroTipoOptions = resOpciones.data.DATOS || [{
            value: '10k',
            label: '10k'
        },
        {
            value: '14k',
            label: '14k'
        },
        {
            value: '18k',
            label: '18k'
        },
        {
            value: '22k',
            label: '22k'
        },
        {
            value: '24k',
            label: '24k'
        }
        ];

        proveedoresCache = (resProv.data.DATOS || []).map(p => ({
            value: p.id,
            label: `${p.nombre} (${p.tipo_nombre})`
        }));
    }).catch((error) => {
        console.error('Error cargando datos:', error);
        oroTipoOptions = [{
            value: '10k',
            label: '10k'
        },
        {
            value: '14k',
            label: '14k'
        },
        {
            value: '18k',
            label: '18k'
        },
        {
            value: '22k',
            label: '22k'
        },
        {
            value: '24k',
            label: '24k'
        }
        ];
    });

    const buildOroForm = (data) => {
        data = data || {};
        const provOpts = [{
            value: '',
            label: '-- Sin proveedor --'
        }].concat(
            proveedoresCache.filter((p) => p.label.indexOf('(oro)') > -1 || !data.id)
        );
        return DsCrud.field({
            name: 'tipo_oro_id',
            label: 'Tipo de Oro',
            type: 'select',
            value: data.tipo_oro_id,
            options: oroTipoOptions,
            required: true
        }) +
            DsCrud.field({
                name: 'peso_gramos',
                label: 'Peso (gramos)',
                type: 'number',
                value: data.peso_gramos,
                required: true,
                attrs: 'step="0.01" min="0"'
            }) +
            DsCrud.field({
                name: 'precio_gramo',
                label: 'Precio/gramo',
                type: 'number',
                value: data.precio_gramo,
                required: true,
                attrs: 'step="0.01" min="0"'
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    };

    const openOroCreate = () => {
        DsCrud.openModal({
            title: 'Nuevo Inventario Oro',
            body: '<form id="frm-oro">' + buildOroForm() + '</form>',
            onSave: (m) => {
                const f = m.querySelector('#frm-oro');
                if (!f.checkValidity()) {
                    f.reportValidity();
                    return;
                }
                const fd = new FormData(f);
                const payload = {};
                fd.forEach((v, k) => {
                    payload[k] = v;
                });
                DsCrud.api('api/inventario/oro.php', 'POST', payload, () => {
                    DsCrud.toast('Oro creado', 'success');
                    oroTable.ajax.reload();
                    DsCrud.closeModal();
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    const openOroEdit = (row) => {
        DsCrud.api('api/inventario/oro.php?id=' + row.id, 'GET', null, (res) => {
            const d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Oro #' + d.id,
                body: '<form id="frm-oro">' + buildOroForm(d) + '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-oro');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { id: d.id };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    DsCrud.api('api/inventario/oro.php', 'PATCH', payload, () => {
                        DsCrud.toast('Oro actualizado', 'success');
                        oroTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (e) => {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        });
    };

    const openOroDelete = (row) => {
        DsCrud.confirm('Eliminar registro de oro #' + row.id + '?', () => {
            DsCrud.api('api/inventario/oro.php', 'DELETE', {
                id: row.id
            }, () => {
                DsCrud.toast('Oro eliminado', 'success');
                oroTable.ajax.reload();
            }, (e) => {
                DsCrud.toast(e, 'error');
            });
        });
    };

    const buildCompraOroForm = (options, data) => {
        data = data || {};
        return DsCrud.field({
            name: 'item_id',
            label: 'Inventario de oro',
            type: 'select',
            value: data.item_id,
            options: options,
            required: true
        }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad (gramos)',
                type: 'number',
                value: data.cantidad,
                required: true,
                attrs: 'step="0.01" min="0"'
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

    const openOroCompra = () => {
        axios.get('api/inventario/oro.php?limit=500').then((res) => {
            const items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
            if (!items.length) {
                DsCrud.toast('No hay inventario de oro disponible', 'warning');
                return;
            }
            const options = [{
                value: '',
                label: '-- Seleccione --'
            }].concat(items.map((it) => ({
                value: it.id,
                label: `${it.tipo_oro_nombre || 'Oro'} #${it.id}`
            })));

            DsCrud.openModal({
                title: 'Registrar compra de oro',
                body: '<form id="frm-compra-oro">' + buildCompraOroForm(options) + '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-compra-oro');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { tipo_inventario: 'oro' };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    if (payload.fecha) {
                        payload.fecha = payload.fecha.replace('T', ' ');
                    }
                    DsCrud.api('api/produccion/compras.php', 'POST', payload, () => {
                        DsCrud.toast('Compra registrada', 'success');
                        oroTable.ajax.reload();
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

    oroTable = $('#oro-table').DataTable({
        ajax: {
            url: 'api/inventario/oro.php',
            data: (d) => {
                d.limit = 500;
                d.offset = 0;
                if (tipoFilter) d.tipo_id = tipoFilter;
            },
            dataSrc: 'DATOS'
        },
        columns: [{
            data: 'id'
        },
        {
            data: 'tipo_oro_nombre'
        },
        {
            data: 'peso_gramos'
        },
        {
            data: 'precio_gramo'
        },
        {
            data: 'proveedor_nombre',
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

    $('#btn-add-oro').on('click', openOroCreate);
    $('#btn-compra-oro').on('click', openOroCompra);
    $('#oro-filtrar-modern').on('click', applyOroFilters);
    $('#oro-limpiar-modern').on('click', () => {
        const tipoEl = $('#oro-tipo-modern');
        if (tipoEl.length) tipoEl.val('');
        applyOroFilters();
    });
    $('#oro-tipo-modern').on('change', applyOroFilters);
    $('#oro-table').on('click', '.ds-action-btn[data-action="edit"]', (e) => {
        const row = oroTable.row($(e.currentTarget).closest('tr')).data();
        openOroEdit(row);
    });
    $('#oro-table').on('click', '.ds-action-btn[data-action="delete"]', (e) => {
        const row = oroTable.row($(e.currentTarget).closest('tr')).data();
        openOroDelete(row);
    });
});
