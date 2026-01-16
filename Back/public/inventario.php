<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$legacy = dedumsoft_is_legacy_browser();
$oro_tipo = $_GET['oro_tipo'] ?? '';
$insumo_categoria = $_GET['insumo_categoria'] ?? '';
$insumo_stock_bajo = isset($_GET['insumo_stock_bajo']) && $_GET['insumo_stock_bajo'] !== '0';
$maq_estado = $_GET['maq_estado'] ?? '';
$oro_rows = [];
$insumo_rows = [];
$maq_rows = [];
$categoria_options = [];

try {
    $stmt = $connLogic->query(
        "SELECT DISTINCT categoria FROM inventario_insumos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
    );
    $categoria_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('inventario categorias error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
}

if ($legacy) {
    try {
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $oro_tipo !== '' ? $oro_tipo : null, $oro_tipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $oro_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, categoria, cantidad, proveedor_nombre FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_categoria !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':stock_bajo', $insumo_stock_bajo, PDO::PARAM_BOOL);
        $stmt->execute();
        $insumo_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo, estado, ubicacion FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado)'
        );
        $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', 20, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $maq_estado !== '' ? $maq_estado : null, $maq_estado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $maq_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario legacy maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="content-header">
        <h1>Inventario</h1>
        <p>Control de metales, insumos y maquinaria</p>
    </div>

    <div class="card" id="inv-oro">
        <strong>Inventario de oro</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2 align-items-end">
        <?php endif; ?>
            <div>
                <label class="form-label muted" for="oro-tipo">Tipo</label>
                <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="10k" <?php echo $oro_tipo === '10k' ? 'selected' : ''; ?>>10k</option>
                    <option value="14k" <?php echo $oro_tipo === '14k' ? 'selected' : ''; ?>>14k</option>
                    <option value="18k" <?php echo $oro_tipo === '18k' ? 'selected' : ''; ?>>18k</option>
                    <option value="22k" <?php echo $oro_tipo === '22k' ? 'selected' : ''; ?>>22k</option>
                    <option value="24k" <?php echo $oro_tipo === '24k' ? 'selected' : ''; ?>>24k</option>
                </select>
            </div>
            <?php if ($legacy): ?>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <?php else: ?>
            <button class="btn btn-sm" type="button" onclick="loadOro()">Actualizar</button>
            <?php endif; ?>
        <?php if ($legacy): ?>
        </form>
        <?php else: ?>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="oro-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Peso</th>
                    <th>Precio</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($legacy): ?>
                <?php foreach ($oro_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['tipo_oro']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['peso_gramos']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['precio_gramo']); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['proveedor_nombre'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-insumos">
        <strong>Insumos</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-insumos" class="d-flex flex-wrap gap-3 align-items-end">
        <?php else: ?>
        <div class="d-flex flex-wrap gap-3 align-items-end">
        <?php endif; ?>
            <div>
                <label class="form-label muted" for="insumo-categoria">Categoria</label>
                <select id="insumo-categoria" name="insumo_categoria" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                        <option value="<?php echo htmlspecialchars((string)$categoria); ?>" <?php echo $insumo_categoria === $categoria ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$categoria); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo" name="insumo_stock_bajo" value="1" <?php echo $insumo_stock_bajo ? 'checked' : ''; ?>>
                <label class="form-check-label muted" for="insumo-stock-bajo">Solo stock bajo</label>
            </div>
            <?php if ($legacy): ?>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <?php else: ?>
            <button class="btn btn-sm" type="button" onclick="loadInsumos()">Actualizar</button>
            <?php endif; ?>
        <?php if ($legacy): ?>
        </form>
        <?php else: ?>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="insumos-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Cantidad</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($legacy): ?>
                <?php foreach ($insumo_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['categoria']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['proveedor_nombre'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="inv-maquinaria">
        <strong>Maquinaria</strong>
        <?php if ($legacy): ?>
        <form method="get" action="inventario.php#inv-maquinaria" class="d-flex flex-wrap gap-2 align-items-end">
        <?php else: ?>
        <div class="d-flex flex-wrap gap-2 align-items-end">
        <?php endif; ?>
            <div>
                <label class="form-label muted" for="maq-estado">Estado</label>
                <select id="maq-estado" name="maq_estado" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <option value="operativa" <?php echo $maq_estado === 'operativa' ? 'selected' : ''; ?>>Operativa</option>
                    <option value="mantenimiento" <?php echo $maq_estado === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="averiada" <?php echo $maq_estado === 'averiada' ? 'selected' : ''; ?>>Averiada</option>
                    <option value="fuera_servicio" <?php echo $maq_estado === 'fuera_servicio' ? 'selected' : ''; ?>>Fuera de servicio</option>
                </select>
            </div>
            <?php if ($legacy): ?>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <?php else: ?>
            <button class="btn btn-sm" type="button" onclick="loadMaquinaria()">Actualizar</button>
            <?php endif; ?>
        <?php if ($legacy): ?>
        </form>
        <?php else: ?>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="maq-table" class="table table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Ubicacion</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($legacy): ?>
                <?php foreach ($maq_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['tipo']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['estado']); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['ubicacion'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!$legacy): ?>
<script>
async function loadOro() {
    const tipo = document.getElementById('oro-tipo').value;
    const url = '../api/inventario_oro.php?limit=20&offset=0&tipo=' + encodeURIComponent(tipo);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#oro-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.tipo_oro}</td><td>${row.peso_gramos}</td><td>${row.precio_gramo}</td><td>${row.proveedor_nombre || ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function loadInsumos() {
    const categoria = document.getElementById('insumo-categoria').value;
    const stockBajo = document.getElementById('insumo-stock-bajo').checked ? '1' : '0';
    const url = '../api/inventario_insumos.php?limit=20&offset=0&categoria=' + encodeURIComponent(categoria) + '&stock_bajo=' + stockBajo;
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#insumos-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.nombre}</td><td>${row.categoria}</td><td>${row.cantidad}</td><td>${row.proveedor_nombre || ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function loadMaquinaria() {
    const estado = document.getElementById('maq-estado').value;
    const url = '../api/inventario_maquinaria.php?limit=20&offset=0&estado=' + encodeURIComponent(estado);
    const res = await fetch(url);
    const data = await res.json();
    const tbody = document.querySelector('#maq-table tbody');
    tbody.innerHTML = '';
    (data.DATOS || []).forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.id}</td><td>${row.nombre}</td><td>${row.tipo}</td><td>${row.estado}</td><td>${row.ubicacion || ''}</td>`;
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadOro();
    loadInsumos();
    loadMaquinaria();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
