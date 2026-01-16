<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$area = $_GET['area'] ?? '';
$ubicaciones_rows = [];

function format_area_badge($area)
{
    $a = strtolower($area);
    $badge = 'neutral';
    switch ($a) {
        case 'produccion':
        case 'producción':
            $badge = 'warning';
            break;
        case 'almacen':
        case 'almacén':
            $badge = 'info';
            break;
        case 'ventas':
            $badge = 'success';
            break;
        case 'oficina':
            $badge = 'muted';
            break;
        case 'taller':
            $badge = 'danger';
            break;
    }
    $display = ucfirst($a);
    return '<span class="ds-badge ds-badge--' . $badge . '">' . htmlspecialchars($display) . '</span>';
}

function format_activo_badge($activo)
{
    if ($activo) {
        return '<span class="ds-badge ds-badge--success">Activo</span>';
    }
    return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, descripcion, area, activo FROM fun_obtener_ubicaciones(:offset, :limit, :area, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 50, PDO::PARAM_INT);
        $stmt->bindValue(':area', $area !== '' ? $area : null, $area !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $ubicaciones_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ubicaciones legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Ubicaciones</h1>
        <p>Gestión de ubicaciones físicas de maquinaria e insumos</p>
    </div>

    <div class="card">
        <strong>Listado de ubicaciones</strong>
        <?php if ($legacy): ?>
            <form method="get" action="ubicaciones.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="ubic-area">Área</label>
                    <select id="ubic-area" name="area" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <option value="Produccion" <?php echo $area === 'Produccion' ? 'selected' : ''; ?>>Producción</option>
                        <option value="Almacen" <?php echo $area === 'Almacen' ? 'selected' : ''; ?>>Almacén</option>
                        <option value="Ventas" <?php echo $area === 'Ventas' ? 'selected' : ''; ?>>Ventas</option>
                        <option value="Oficina" <?php echo $area === 'Oficina' ? 'selected' : ''; ?>>Oficina</option>
                        <option value="Taller" <?php echo $area === 'Taller' ? 'selected' : ''; ?>>Taller</option>
                        <option value="General" <?php echo $area === 'General' ? 'selected' : ''; ?>>General</option>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="ubicaciones.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="ubicaciones-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Área</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($ubicaciones_rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['descripcion'] ?? '')); ?></td>
                                <td><?php echo format_area_badge($row['area'] ?? 'General'); ?></td>
                                <td><?php echo format_activo_badge($row['activo']); ?></td>
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
        function formatAreaBadge(area) {
            var a = (area || 'General').toLowerCase();
            var badge = 'neutral';
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
            var display = a.charAt(0).toUpperCase() + a.slice(1);
            return '<span class="ds-badge ds-badge--' + badge + '">' + display + '</span>';
        }

        function formatActivoBadge(activo) {
            if (activo) {
                return '<span class="ds-badge ds-badge--success">Activo</span>';
            }
            return '<span class="ds-badge ds-badge--muted">Inactivo</span>';
        }

        $(function () {
            $.getJSON('../api/ubicaciones.php?limit=100&offset=0', function (data) {
                $('#ubicaciones-table').DataTable({
                    data: data.DATOS || [],
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
                        data: 'area',
                        render: function (data) {
                            return formatAreaBadge(data);
                        }
                    },
                    {
                        data: 'activo',
                        render: function (data) {
                            return formatActivoBadge(data);
                        }
                    }
                    ],
                    language: {
                        url: 'assets/dataTables.es-ES.json'
                    }
                });
            });
        });
    </script>
<?php elseif ($legacy): ?>
    <script>
        if (window.DedumTableSort) DedumTableSort.init('ubicaciones-table');
    </script>
<?php endif; ?>