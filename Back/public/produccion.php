<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');
require_menu_access(3);

$legacy = dedumsoft_is_legacy_browser();
$estado = $_GET['estado'] ?? '';
$ordenes_rows = [];
$artesanos_options = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, producto_nombre, artesano_id, artesano_nombre, estado, fecha_inicio FROM fun_obtener_ordenes(:offset, :limit, :estado)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado !== '' ? $estado : null, $estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $ordenes_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('produccion legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->query("SELECT id, nombre, apellido FROM artesanos WHERE activo = TRUE ORDER BY nombre, apellido");
        $artesanos_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('artesanos legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Produccion</h1>
        <p>Seguimiento de ordenes y estado de taller</p>
    </div>

    <div class="card">
        <strong>Ordenes de produccion</strong>
        <?php if ($legacy): ?>
            <form method="get" action="produccion.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="orden-estado">Estado</label>
                    <select id="orden-estado" name="estado" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="en_proceso" <?php echo $estado === 'en_proceso' ? 'selected' : ''; ?>>En proceso
                        </option>
                        <option value="terminada" <?php echo $estado === 'terminada' ? 'selected' : ''; ?>>Terminada</option>
                        <option value="cancelada" <?php echo $estado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        <option value="pausada" <?php echo $estado === 'pausada' ? 'selected' : ''; ?>>Pausada</option>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="produccion.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="ordenes-table" class="table table-sm">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Producto</th>
                            <th>Artesano</th>
                            <th>Estado</th>
                            <th>Fecha inicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($legacy): ?>
                        <?php foreach ($ordenes_rows as $row): ?>
                            <?php
                            $estado_raw = (string) ($row['estado'] ?? '');
                            $estado_label = strtoupper(str_replace('_', ' ', $estado_raw));
                            $fecha_inicio = $row['fecha_inicio'] ? date('Y-m-d H:i', strtotime((string) $row['fecha_inicio'])) : '';
                            ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>" data-artesano-id="<?php echo (int) ($row['artesano_id'] ?? 0); ?>">
                                <td><?php echo htmlspecialchars((string) ($row['id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['producto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['artesano_nombre'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($estado_label); ?></td>
                                <td><?php echo htmlspecialchars($fecha_inicio); ?></td>
                                <td class="ds-actions-col">
                                    <button type="button" class="ds-action-btn" data-action="asignar" title="Asignar artesano">
                                        <img src="assets/icons/fatcow/16/user_add.png" alt="Asignar" class="ds-icon-img">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php if (!$legacy): ?>
    <script>
        function formatStatus(v) {
            var raw = (v || '').toString();
            var label = raw.replace(/_/g, ' ').toUpperCase();
            var key = raw.toLowerCase();
            var cls = 'ds-badge--neutral';
            if (key === 'pendiente') cls = 'ds-badge--warning';
            else if (key === 'en_proceso') cls = 'ds-badge--info';
            else if (key === 'terminada') cls = 'ds-badge--success';
            else if (key === 'cancelada') cls = 'ds-badge--danger';
            else if (key === 'pausada') cls = 'ds-badge--muted';
            return '<span class="ds-badge ' + cls + '">' + label + '</span>';
        }
        $(function () {
            var ordenesTable;
            var artesanosCache = [];

            axios.get('../api/opciones.php?tipo=artesanos').then(function (res) {
                artesanosCache = (res.data.DATOS || []).map(function (a) {
                    return {
                        value: a.value || a.id,
                        label: a.label || a.nombre
                    };
                });
            }).catch(function () {
                artesanosCache = [];
            });

            ordenesTable = $('#ordenes-table').DataTable({
                ajax: {
                    url: '../api/ordenes.php?limit=100&offset=0',
                    dataSrc: 'DATOS'
                },
                columns: [
                    { data: 'id' },
                    { data: 'producto_nombre' },
                    { data: 'artesano_nombre', defaultContent: '' },
                    { data: 'estado', render: formatStatus },
                    { data: 'fecha_inicio', render: function (v) { return v ? v.replace('T', ' ').split('.')[0] : ''; } },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type) {
                            if (type !== 'display') return '';
                            return '<button type="button" class="ds-action-btn" data-action="asignar" title="Asignar artesano">👤➕</button>';
                        }
                    }
                ],
                language: { url: 'assets/dataTables.es-ES.json' }
            });

            function openAsignar(row) {
                if (!artesanosCache.length) {
                    DsCrud.toast('No hay artesanos disponibles', 'error');
                    return;
                }
                var opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosCache);
                var body = '<form id="frm-asignar">' +
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
                    onSave: function (m) {
                        var f = m.querySelector('#frm-asignar');
                        if (!f.checkValidity()) {
                            f.reportValidity();
                            return;
                        }
                        var fd = new FormData(f);
                        var payload = {
                            id: row.id,
                            artesano_id: fd.get('artesano_id')
                        };
                        DsCrud.api('../api/ordenes.php', 'PUT', payload, function () {
                            DsCrud.toast('Orden actualizada', 'success');
                            ordenesTable.ajax.reload();
                            DsCrud.closeModal();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            $('#ordenes-table').on('click', '.ds-action-btn[data-action="asignar"]', function () {
                var row = ordenesTable.row($(this).closest('tr')).data();
                if (row) {
                    openAsignar(row);
                }
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        (function () {
            if (window.DedumTableSort) DedumTableSort.init('ordenes-table');

            var artesanosOptions = <?php echo json_encode(array_map(function ($a) {
                return [
                    'value' => $a['id'],
                    'label' => trim($a['nombre'] . ' ' . $a['apellido'])
                ];
            }, $artesanos_options)); ?>;

            function esc(s) {
                if (s === null || s === undefined) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(String(s)));
                return div.innerHTML;
            }

            function selectHtml(name, value, options, req) {
                var h = '<select name="' + esc(name) + '" class="ds-field ds-field--select"' + (req ? ' required' : '') + '>';
                for (var i = 0; i < options.length; i++) {
                    var opt = options[i];
                    var sel = (String(opt.value) === String(value)) ? ' selected' : '';
                    h += '<option value="' + esc(opt.value) + '"' + sel + '>' + esc(opt.label) + '</option>';
                }
                h += '</select>';
                return h;
            }

            function openAsignar(ordenId, artesanoId) {
                if (!artesanosOptions.length) {
                    alert('No hay artesanos disponibles');
                    return;
                }
                var opts = [{ value: '', label: '-- Seleccione --' }].concat(artesanosOptions);
                var body = '<form id="frm-asignar">' +
                    '<div class="ds-form-group"><label>Artesano</label>' +
                    selectHtml('artesano_id', artesanoId || '', opts, true) + '</div>' +
                    '</form>';
                DsCrud.openModal({
                    title: 'Asignar artesano - Orden #' + ordenId,
                    body: body,
                    onSave: function (modal) {
                        if (!DsCrud.validateForm(modal)) return;
                        var data = DsCrud.getFormData(modal);
                        data.id = ordenId;
                        DsCrud.api('../api/ordenes.php', 'PUT', data, function () {
                            DsCrud.toast('Orden actualizada', 'success');
                            DsCrud.closeModal();
                            location.reload();
                        }, function (e) {
                            DsCrud.toast(e, 'error');
                        });
                    }
                });
            }

            function findActionButton(target) {
                while (target && target !== document) {
                    if (target.getAttribute && target.getAttribute('data-action') === 'asignar') {
                        return target;
                    }
                    target = target.parentNode;
                }
                return null;
            }

            var table = document.getElementById('ordenes-table');
            if (table) {
                DsCrud.addEvent(table, 'click', function (e) {
                    e = e || window.event;
                    var target = e.target || e.srcElement;
                    var btn = findActionButton(target);
                    if (!btn) return;

                    var row = btn;
                    while (row && row.tagName && row.tagName.toLowerCase() !== 'tr') {
                        row = row.parentNode;
                    }
                    if (!row) return;

                    var ordenId = row.getAttribute('data-id');
                    var artesanoId = row.getAttribute('data-artesano-id');
                    openAsignar(ordenId, artesanoId);
                });
            }
        })();
    </script>
<?php endif; ?>
</body>

</html>
