<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$estado = $_GET['estado'] ?? '';
$ordenes_rows = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT codigo_orden, producto_nombre, artesano_nombre, estado, fecha_inicio FROM fun_obtener_ordenes(:offset, :limit, :estado)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado !== '' ? $estado : null, $estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $ordenes_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('produccion legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
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
                        <th>Codigo</th>
                        <th>Producto</th>
                        <th>Artesano</th>
                        <th>Estado</th>
                        <th>Fecha inicio</th>
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
                            <tr>
                                <td><?php echo htmlspecialchars((string) $row['codigo_orden']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['producto_nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['artesano_nombre'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($estado_label); ?></td>
                                <td><?php echo htmlspecialchars($fecha_inicio); ?></td>
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
            $.getJSON('../api/ordenes.php?limit=100&offset=0', function (data) {
                $('#ordenes-table').DataTable({
                    data: data.DATOS || [],
                    columns: [
                        { data: 'codigo_orden' },
                        { data: 'producto_nombre' },
                        { data: 'artesano_nombre', defaultContent: '' },
                        { data: 'estado', render: formatStatus },
                        { data: 'fecha_inicio', render: function (v) { return v ? v.replace('T', ' ').split('.')[0] : ''; } }
                    ],
                    language: { url: 'assets/dataTables.es-ES.json' }
                });
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>if (window.DedumTableSort) DedumTableSort.init('ordenes-table');</script>
<?php endif; ?>
</body>

</html>