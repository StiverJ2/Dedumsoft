<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: CATÁLOGOS MAESTROS
 * ============================================================================
 *
 * Administración de catálogos base del sistema:
 * áreas, tipos, estados, prioridades, productos y niveles de calidad.
 *
 * Autenticación: Requerida
 * Autorización: Menú 7 (Configuración)
 *
 * API utilizada:
 * - GET/POST/PATCH/DELETE /api/catalogos/maestros.php?catalog=...
 *
 * @package Dedumsoft\Public
 */

require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Database/CatalogConfig.php';

require_login('login.php');
require_menu_access(7);

$legacy = dedumsoft_is_legacy_browser();
$catalogs = dedumsoft_catalogs_config();
$catalog_keys = array_keys($catalogs);
$default_catalog = count($catalog_keys) ? $catalog_keys[0] : '';
$selected_catalog = isset($_GET['catalog']) && isset($catalogs[$_GET['catalog']]) ? $_GET['catalog'] : $default_catalog;
$ui_catalogs = [];
foreach ($catalogs as $key => $cfg) {
    $ui_catalogs[$key] = [
        'label' => $cfg['label'],
        'emoji' => $cfg['emoji'] ?? '',
        'list_columns' => $cfg['list_columns'],
        'fields' => $cfg['fields']
    ];
}

$legacy_rows = [];
if ($legacy && $selected_catalog !== '') {
    $cfg = $catalogs[$selected_catalog];
    $list_cols = $cfg['list_columns'];
    $select_cols = [];
    foreach ($list_cols as $col) {
        $select_cols[] = $col['key'];
    }

    $sql = 'SELECT ' . implode(', ', $select_cols) . ' FROM ' . $cfg['table'];
    if (!empty($cfg['filter_activo'])) {
        $sql .= ' WHERE activo = true';
    }
    $sql .= ' ORDER BY ' . $cfg['order_by'];

    try {
        $stmt = $connLogic->prepare($sql);
        $stmt->execute();
        $legacy_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('catalogos legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

function format_activo_badge($activo)
{
    if ($activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Catalogos</h1>
        <p>Administracion de catalogos base del sistema</p>
    </div>

    <?php if ($legacy): ?>
        <div class="card">
            <div class="ds-toolbar">
                <div>
                    <strong>Catalogo activo</strong>
                    <?php if ($selected_catalog !== '' && isset($catalogs[$selected_catalog])): ?>
                        <div class="ds-catalog-active">
                            <img class="ds-catalog-icon" src="assets/icons/fatcow/16/<?php echo htmlspecialchars($catalogs[$selected_catalog]['icon']); ?>" alt="">
                            <span><?php echo htmlspecialchars($catalogs[$selected_catalog]['label']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ds-toolbar-actions">
                    <button type="button" class="btn-add" id="catalog-create-btn">+ Nuevo registro</button>
                </div>
            </div>
            <form method="get" action="catalogos.php" class="mb-2">
                <label class="form-label muted" for="catalog-select">Catalogo</label>
                <select id="catalog-select" name="catalog" class="form-select form-select-sm ds-field"
                    onchange="this.form.submit()">
                    <?php foreach ($catalogs as $key => $cfg): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $key === $selected_catalog ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cfg['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="table-responsive">
                <table id="catalogos-table" class="table table-sm">
                    <thead>
                        <tr>
                            <?php foreach ($catalogs[$selected_catalog]['list_columns'] as $col): ?>
                                <th><?php echo htmlspecialchars($col['label']); ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($legacy_rows as $row): ?>
                            <tr data-id="<?php echo (int) ($row['id'] ?? 0); ?>">
                                <?php foreach ($catalogs[$selected_catalog]['list_columns'] as $col): ?>
                                    <?php
                                    $key = $col['key'];
                                    $type = $col['type'] ?? '';
                                    $value = $row[$key] ?? '';
                                    ?>
                                    <td>
                                        <?php if ($type === 'bool'): ?>
                                            <?php echo format_activo_badge($value); ?>
                                        <?php elseif ($type === 'color'): ?>
                                            <span class="ds-color-dot" style="background: <?php echo htmlspecialchars((string) $value); ?>"></span>
                                            <?php echo htmlspecialchars((string) $value); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars((string) $value); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="ds-actions-col"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <div class="col-lg-3">
                <div class="card">
                    <strong>Catalogos</strong>
                    <div class="list-group mt-2" id="catalog-list">
                        <?php foreach ($catalogs as $key => $cfg): ?>
                            <button type="button"
                                class="list-group-item list-group-item-action <?php echo $key === $selected_catalog ? 'active' : ''; ?>"
                                data-catalog="<?php echo htmlspecialchars($key); ?>">
                                <span class="ds-emoji" aria-hidden="true"><?php echo htmlspecialchars($cfg['emoji'] ?? ''); ?></span>
                                <span><?php echo htmlspecialchars($cfg['label']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="ds-toolbar">
                        <strong id="catalog-title"></strong>
                        <div class="ds-toolbar-actions">
                            <button type="button" class="btn-add" id="catalog-create-btn">+ Nuevo registro</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="catalogos-table" class="table table-sm">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        (() => {
            const catalogs = <?php echo json_encode($ui_catalogs); ?>;

            let currentKey = '<?php echo $selected_catalog; ?>';
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
        })();
    </script>
<?php else: ?>
    <script>
        (function () {
            if (!window.DsCrud) return;

            var catalogKey = '<?php echo $selected_catalog; ?>';
            var cfg = <?php echo json_encode($catalogs[$selected_catalog]); ?>;

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
    </script>
<?php endif; ?>
</body>

</html>
