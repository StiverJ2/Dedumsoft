/**
 * ============================================================================
 * INVENTARIO INSUMOS — JavaScript Moderno (DataTables + Axios)
 * ============================================================================
 */

window.DEDUMSOFT_ICON_MODE = 'emoji';

const esc = (value) => DsCrud.escapeHtml(value === null || value === undefined ? '' : String(value));

const formatCategoria = (cat) => {
    if (!cat) return '';
    const key = String(cat).toLowerCase();
    const labelRaw = String(cat).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    const label = esc(labelRaw);
    let cls = 'ds-badge--neutral';
    if (key.indexOf('piedra') > -1 || key.indexOf('gema') > -1) cls = 'ds-badge--info';
    else if (key.indexOf('metal') > -1 || key.indexOf('oro') > -1) cls = 'ds-badge--warning';
    else if (key.indexOf('herramienta') > -1 || key.indexOf('equipo') > -1) cls = 'ds-badge--muted';
    else if (key.indexOf('quimico') > -1 || key.indexOf('limpieza') > -1) cls = 'ds-badge--danger';
    else if (key.indexOf('empaque') > -1 || key.indexOf('caja') > -1) cls = 'ds-badge--success';
    return `<span class="ds-badge ${cls}">${label}</span>`;
};

$(() => {
    const dtLang = {
        url: 'assets/dataTables.es-ES.json'
    };
    let insumosTable;
    let proveedoresCache = [];
    let categoriaFilter = '';
    let stockBajoFilter = false;

    const applyInsumoFilters = () => {
        const categoriaEl = $('#insumo-categoria-modern');
        const stockEl = $('#insumo-stock-bajo-modern');
        categoriaFilter = categoriaEl.length ? categoriaEl.val() : '';
        stockBajoFilter = stockEl.length ? stockEl.is(':checked') : false;
        if (insumosTable) {
            insumosTable.ajax.reload();
        }
    };

    $.getJSON('api/proveedores.php?limit=500', (res) => {
        proveedoresCache = (res.DATOS || []).map((p) => {
            const tipoRaw = p.tipo_nombre || p.tipo || '';
            const tipoLower = String(tipoRaw || '').toLowerCase();
            return {
                value: p.id,
                label: `${p.nombre}${tipoRaw ? ' (' + tipoRaw + ')' : ''}`,
                tipo: tipoLower
            };
        });
    });

    const buildInsumoForm = (data) => {
        data = data || {};
        const provOpts = [{ value: '', label: '-- Sin proveedor --' }].concat(
            proveedoresCache.filter((p) => {
                const isInsumo = p.tipo === 'insumos';
                const isCurrent = String(p.value) === String(data.proveedor_id || '');
                return isInsumo || !data.id || isCurrent;
            })
        );
        return DsCrud.field({
                name: 'nombre',
                label: 'Nombre',
                value: data.nombre,
                required: true
            }) +
            DsCrud.field({
                name: 'categoria',
                label: 'Categoria',
                value: data.categoria
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
                type: 'number',
                value: data.cantidad,
                required: true,
                attrs: 'min="0"'
            }) +
            DsCrud.field({
                name: 'stock_minimo',
                label: 'Stock minimo',
                type: 'number',
                value: data.stock_minimo,
                attrs: 'min="0"'
            }) +
            DsCrud.field({
                name: 'proveedor_id',
                label: 'Proveedor',
                type: 'select',
                value: data.proveedor_id,
                options: provOpts
            });
    };

    const openInsumoCreate = () => {
        const compraToggle = '<div class="ds-form-group"><label><input type="checkbox" name="registrar_compra"> Registrar compra inicial</label></div>';
        DsCrud.openModal({
            title: 'Nuevo Insumo',
            body: '<form id="frm-insumo">' + buildInsumoForm() + compraToggle + '</form>',
            onSave: (m) => {
                const f = m.querySelector('#frm-insumo');
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

                const compraCantidad = parseFloat(payload.cantidad || 0);
                if (registrarCompra) {
                    if (!isFinite(compraCantidad) || compraCantidad <= 0) {
                        DsCrud.toast('Cantidad debe ser mayor a 0 para registrar compra', 'error');
                        return;
                    }
                    payload.cantidad = 0;
                }

                DsCrud.api('api/inventario_insumos.php', 'POST', payload, (success, resp) => {
                    if (!registrarCompra) {
                        DsCrud.toast('Insumo creado', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                        return;
                    }
                    const newId = resp.DATOS && resp.DATOS.id ? resp.DATOS.id : resp.ID;
                    const compraPayload = {
                        tipo_inventario: 'insumos',
                        item_id: newId,
                        cantidad: compraCantidad
                    };
                    DsCrud.api('api/compras.php', 'POST', compraPayload, () => {
                        DsCrud.toast('Insumo creado y compra registrada', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (e) => {
                        DsCrud.toast('Insumo creado, pero no se pudo registrar la compra: ' + e, 'error');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    });
                }, (e) => {
                    DsCrud.toast(e, 'error');
                });
            }
        });
    };

    const openInsumoEdit = (row) => {
        DsCrud.api('api/inventario_insumos.php?id=' + row.id, 'GET', null, (res) => {
            const d = res.DATOS && res.DATOS[0] ? res.DATOS[0] : row;
            DsCrud.openModal({
                title: 'Editar Insumo #' + d.id,
                body: '<form id="frm-insumo">' + buildInsumoForm(d) + '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-insumo');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { id: d.id };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    DsCrud.api('api/inventario_insumos.php', 'PATCH', payload, () => {
                        DsCrud.toast('Insumo actualizado', 'success');
                        insumosTable.ajax.reload();
                        DsCrud.closeModal();
                    }, (e) => {
                        DsCrud.toast(e, 'error');
                    });
                }
            });
        });
    };

    const openInsumoDelete = (row) => {
        DsCrud.confirm('Eliminar insumo "' + row.nombre + '"?', () => {
            DsCrud.api('api/inventario_insumos.php', 'DELETE', { id: row.id }, () => {
                DsCrud.toast('Insumo eliminado', 'success');
                insumosTable.ajax.reload();
            }, (e) => {
                DsCrud.toast(e, 'error');
            });
        });
    };

    const buildCompraInsumoForm = (options, data) => {
        data = data || {};
        return DsCrud.field({
                name: 'item_id',
                label: 'Insumo',
                type: 'select',
                value: data.item_id,
                options: options,
                required: true
            }) +
            DsCrud.field({
                name: 'cantidad',
                label: 'Cantidad',
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

    const openInsumoCompra = () => {
        axios.get('api/inventario_insumos.php?limit=500').then((res) => {
            const items = (res.data && res.data.DATOS) ? res.data.DATOS : [];
            if (!items.length) {
                DsCrud.toast('No hay insumos disponibles', 'warning');
                return;
            }
            const options = [{ value: '', label: '-- Seleccione --' }].concat(items.map((it) => {
                let label = it.nombre || ('Insumo #' + it.id);
                if (it.categoria) {
                    label += ' (' + it.categoria + ')';
                }
                label += ' #' + it.id;
                return { value: it.id, label: label };
            }));

            DsCrud.openModal({
                title: 'Registrar compra de insumos',
                body: '<form id="frm-compra-insumo">' + buildCompraInsumoForm(options) + '</form>',
                onSave: (m) => {
                    const f = m.querySelector('#frm-compra-insumo');
                    if (!f.checkValidity()) {
                        f.reportValidity();
                        return;
                    }
                    const fd = new FormData(f);
                    const payload = { tipo_inventario: 'insumos' };
                    fd.forEach((v, k) => {
                        payload[k] = v;
                    });
                    if (payload.fecha) {
                        payload.fecha = payload.fecha.replace('T', ' ');
                    }
                    DsCrud.api('api/compras.php', 'POST', payload, () => {
                        DsCrud.toast('Compra registrada', 'success');
                        insumosTable.ajax.reload();
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

    insumosTable = $('#insumos-table').DataTable({
        ajax: {
            url: 'api/inventario_insumos.php',
            data: (d) => {
                d.limit = 500;
                d.offset = 0;
                if (categoriaFilter) d.categoria = categoriaFilter;
                if (stockBajoFilter) d.stock_bajo = true;
            },
            dataSrc: 'DATOS'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'categoria', render: formatCategoria },
            { data: 'cantidad' },
            { data: 'proveedor_nombre', defaultContent: '' },
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

    $('#btn-add-insumo').on('click', openInsumoCreate);
    $('#btn-compra-insumo').on('click', openInsumoCompra);
    $('#insumo-filtrar-modern').on('click', applyInsumoFilters);
    $('#insumo-limpiar-modern').on('click', () => {
        const categoriaEl = $('#insumo-categoria-modern');
        const stockEl = $('#insumo-stock-bajo-modern');
        if (categoriaEl.length) categoriaEl.val('');
        if (stockEl.length) stockEl.prop('checked', false);
        applyInsumoFilters();
    });
    $('#insumo-categoria-modern').on('change', applyInsumoFilters);
    $('#insumo-stock-bajo-modern').on('change', applyInsumoFilters);
    $('#insumos-table').on('click', '.ds-action-btn[data-action="edit"]', (e) => {
        const row = insumosTable.row($(e.currentTarget).closest('tr')).data();
        openInsumoEdit(row);
    });
    $('#insumos-table').on('click', '.ds-action-btn[data-action="delete"]', (e) => {
        const row = insumosTable.row($(e.currentTarget).closest('tr')).data();
        openInsumoDelete(row);
    });
});
