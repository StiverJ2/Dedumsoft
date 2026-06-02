<?php
/**
 * View: Inventario de Insumos
 *
 * Variables disponibles:
 * @var array  $categoria_options
 * @var array  $insumo_rows
 * @var string $insumo_categoria
 * @var bool   $insumo_stock_bajo
 * @var array  $proveedor_options
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Insumos</h1>
        <p>Control de materiales y consumibles</p>
    </div>

    <div class="card" id="inv-insumos">
        <div class="ds-toolbar">
            <strong>Insumos</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-insumo">+ Nuevo Insumo</button>
                <button type="button" class="btn-add" id="btn-compra-insumo">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
        <form class="d-flex flex-wrap gap-3 align-items-end" id="insumo-filtros-modern">
            <div>
                <label class="form-label muted" for="insumo-categoria-modern">Categoria</label>
                <select id="insumo-categoria-modern" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                    <option value="<?php echo v_e((string) $categoria); ?>">
                        <?php echo v_e(ucwords(str_replace('_', ' ', (string) $categoria))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo-modern">
                <label class="form-check-label muted" for="insumo-stock-bajo-modern">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" type="button" id="insumo-filtrar-modern">Aplicar</button>
            <button class="btn btn-sm btn-secondary" type="button" id="insumo-limpiar-modern">Limpiar</button>
        </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
        <form method="get" action="inventario_insumos.php#inv-insumos" class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label muted" for="insumo-categoria">Categoria</label>
                <select id="insumo-categoria" name="insumo_categoria" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($categoria_options as $categoria): ?>
                    <option value="<?php echo v_e((string) $categoria); ?>"
                        <?php echo $insumo_categoria === $categoria ? 'selected' : ''; ?>>
                        <?php echo v_e((string) $categoria); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input ds-field" type="checkbox" id="insumo-stock-bajo" name="insumo_stock_bajo"
                    value="1" <?php echo $insumo_stock_bajo ? 'checked' : ''; ?>>
                <label class="form-check-label muted" for="insumo-stock-bajo">Solo stock bajo</label>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario_insumos.php#inv-insumos" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($insumo_rows as $row): ?>
                    <tr>
                        <td><?php echo v_e((string) $row['id']); ?></td>
                        <td><?php echo v_e((string) $row['nombre']); ?></td>
                        <td><?php echo v_e((string) $row['categoria']); ?></td>
                        <td><?php echo v_e((string) $row['cantidad']); ?></td>
                        <td><?php echo v_e((string) ($row['proveedor_nombre'] ?? '')); ?></td>
                        <td class="ds-actions-col"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
