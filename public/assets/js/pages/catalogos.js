/**
 * ============================================================================
 * CATALOGOS — JavaScript Moderno (DataTables)
 * ============================================================================
 */

const catalogs = window._catalogs || {};
let currentKey = window._currentCatalogKey || '';
let table = null;

const isTrue = (v) => v === true || v === 1 || v === '1' || v === 't' || v === 'true';

const formatNumber = (value, decimals) => {
    const num = parseFloat(value);
    if (isNaN(num)) return '';
    if (typeof decimals === 'number') {
        return num.toFixed(decimals);
    }
    return num.toString();
};

const buildHeader = (cols) => {
    const thead = document.querySelector('#catalogos-table thead');
    if (!thead) return;
    let html = '<tr>';
    for (let i = 0; i < cols.length; i++) {
        html += '<th>' + DsCrud.escapeHtml(cols[i].label) + '</th>';
    }
    html += '<th>Acciones</th></tr>';
    thead.innerHTML = html;
};

const resetTable = () => {
    if ($.fn.dataTable.isDataTable('#catalogos-table')) {
        $('#catalogos-table').DataTable().clear().destroy();
    }
    $('#catalogos-table thead').empty();
    $('#catalogos-table tbody').empty();
};

const buildColumns = (cols) => {
    const dtCols = cols.map((col) => ({
        data: col.key,
        render: (data, type) => {
            if (type !== 'display') return data;
            if (col.type === 'bool') {
                return isTrue(data) ? '<span class="ds-badge ds-badge--success">Activo</span>' :
                    '<span class="ds-badge ds-badge--muted">Inactivo</span>';
            }
            if (col.type === 'color') {
                const colorVal = data || '';
                return '<span class="ds-color-dot" style="background:' + DsCrud.escapeHtml(colorVal) + '"></span>' +
                    DsCrud.escapeHtml(colorVal);
            }
            if (col.type === 'money') {
                return formatNumber(data, 2);
            }
            if (col.type === 'number') {
                return formatNumber(data);
            }
            return DsCrud.escapeHtml(data);
        }
    }));
    dtCols.push({
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => {
            if (type !== 'display') return '';
            return DsCrud.actionButtons(row.id);
        }
    });
    return dtCols;
};

const buildForm = (fields, data = {}) => {
    let html = '';
    for (let i = 0; i < fields.length; i++) {
        const field = fields[i];
        let value = data[field.name];
        if (value === null || value === undefined) value = '';
        const opts = {
            name: field.name,
            label: field.label,
            required: !!field.required,
            value: value,
            placeholder: field.placeholder || ''
        };
        if (field.input === 'textarea') {
            opts.type = 'textarea';
        } else if (field.input) {
            opts.type = field.input;
        }
        if (field.attrs) {
            opts.attrs = field.attrs;
        }
        html += DsCrud.field(opts);
    }
    return html;
};

const buildPayload = (fields, formData) => {
    const payload = {};
    for (let i = 0; i < fields.length; i++) {
        const name = fields[i].name;
        let value = formData[name];
        if (typeof value === 'string') {
            value = value.replace(/^\s+|\s+$/g, '');
        }
        if (value === '') value = null;
        payload[name] = value;
    }
    return payload;
};

const openCreate = () => {
    const cfg = catalogs[currentKey];
    if (!cfg) return;
    DsCrud.openModal({
        title: 'Nuevo - ' + cfg.label,
        body: buildForm(cfg.fields),
        saveText: 'Crear',
        cancelText: 'Cancelar',
        onSave: (_modal, close, formData) => {
            const payload = buildPayload(cfg.fields, formData);
            for (let i = 0; i < cfg.fields.length; i++) {
                const f = cfg.fields[i];
                if (f.required && (!payload[f.name] && payload[f.name] !== 0)) {
                    DsCrud.toast('Campo requerido: ' + f.label, 'error');
                    return;
                }
            }
            DsCrud.api('api/catalogos/maestros.php?catalog=' + currentKey, 'POST', payload, (ok, resp) => {
                if (ok) {
                    DsCrud.toast(resp.MENSAJE || 'Registro creado');
                    if (table) table.ajax.reload(null, false);
                    close();
                } else {
                    DsCrud.toast(resp.MENSAJE || 'Error al crear', 'error');
                }
            });
        }
    });
};

const openEdit = (row) => {
    if (!row) return;
    const cfg = catalogs[currentKey];
    DsCrud.openModal({
        title: 'Editar - ' + cfg.label + ' #' + DsCrud.escapeHtml(row.id),
        body: buildForm(cfg.fields, row),
        saveText: 'Guardar',
        cancelText: 'Cancelar',
        onSave: (_modal, close, formData) => {
            const payload = buildPayload(cfg.fields, formData);
            payload.id = row.id;
            DsCrud.api('api/catalogos/maestros.php?catalog=' + currentKey, 'PATCH', payload, (ok, resp) => {
                if (ok) {
                    DsCrud.toast(resp.MENSAJE || 'Registro actualizado');
                    if (table) table.ajax.reload(null, false);
                    close();
                } else {
                    DsCrud.toast(resp.MENSAJE || 'Error al actualizar', 'error');
                }
            });
        }
    });
};

const handleDelete = (row) => {
    if (!row) return;
    DsCrud.confirm('¿Eliminar registro #' + row.id + '?', () => {
        DsCrud.api('api/catalogos/maestros.php?catalog=' + currentKey, 'DELETE', { id: row.id }, (ok, resp) => {
            if (ok) {
                DsCrud.toast(resp.MENSAJE || 'Registro eliminado');
                if (table) table.ajax.reload(null, false);
            } else {
                DsCrud.toast(resp.MENSAJE || 'Error al eliminar', 'error');
            }
        });
    });
};

const initTable = (key) => {
    const cfg = catalogs[key];
    if (!cfg) return;
    currentKey = key;
    const titleEl = document.getElementById('catalog-title');
    if (titleEl) {
        titleEl.textContent = (cfg.emoji ? cfg.emoji + ' ' : '') + cfg.label;
    }

    resetTable();
    buildHeader(cfg.list_columns);

    table = $('#catalogos-table').DataTable({
        ajax: {
            url: 'api/catalogos/maestros.php',
            data: (d) => {
                d.catalog = key;
                d.limit = 500;
                d.offset = 0;
            },
            dataSrc: (json) => {
                if (!json || json.CODIGO !== 200 || !json.DATOS) {
                    DsCrud.toast((json && json.MENSAJE) ? json.MENSAJE : 'Error al cargar catalogo', 'error');
                    return [];
                }
                return json.DATOS;
            }
        },
        columns: buildColumns(cfg.list_columns),
        language: { url: 'assets/dataTables.es-ES.json' }
    });
};

$('#catalog-list').on('click', 'button', (e) => {
    const key = $(e.currentTarget).data('catalog');
    if (!catalogs[key]) return;
    $('#catalog-list button').removeClass('active');
    $(e.currentTarget).addClass('active');
    initTable(key);
});

$('#catalog-create-btn').on('click', () => {
    openCreate();
});

$('#catalogos-table').on('click', '.ds-action-btn[data-action="edit"]', (e) => {
    const row = table.row($(e.currentTarget).closest('tr')).data();
    openEdit(row);
});

$('#catalogos-table').on('click', '.ds-action-btn[data-action="delete"]', (e) => {
    const row = table.row($(e.currentTarget).closest('tr')).data();
    handleDelete(row);
});

initTable(currentKey);
