<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$tipo = $_GET['tipo'] ?? '';
$proveedores_rows = [];

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo, contacto, telefono FROM fun_obtener_proveedores(:offset, :limit, :tipo, :activo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo !== '' ? $tipo : null, $tipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
        $stmt->execute();
        $proveedores_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('proveedores legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Proveedores</h1>
        <p>Gestion de proveedores de materiales</p>
    </div>

    <div class="card">
        <strong>Listado de proveedores</strong>
        <?php if ($legacy): ?>
        <form method="get" action="proveedores.php" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="prov-tipo">Tipo</label>
                <select id="prov-tipo" name="tipo" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="oro" <?php echo $tipo === 'oro' ? 'selected' : ''; ?>>Oro</option>
                    <option value="insumos" <?php echo $tipo === 'insumos' ? 'selected' : ''; ?>>Insumos</option>
                    <option value="maquinaria" <?php echo $tipo === 'maquinaria' ? 'selected' : ''; ?>>Maquinaria
                    </option>
                </select>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="proveedores.php" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="proveedores-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Contacto</th>
                        <th>Telefono</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($proveedores_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['tipo']); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['contacto'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['telefono'] ?? '')); ?></td>
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
$(function() {
    $.getJSON('../api/proveedores.php?limit=100&offset=0', function(data) {
        $('#proveedores-table').DataTable({
            data: data.DATOS || [],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'nombre'
                },
                {
                    data: 'tipo'
                },
                {
                    data: 'contacto',
                    defaultContent: ''
                },
                {
                    data: 'telefono',
                    defaultContent: ''
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
if (window.DedumTableSort) DedumTableSort.init('proveedores-table');
</script>
<?php endif; ?>