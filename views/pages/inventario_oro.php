<?php
/**
 * View: Inventario de Oro
 *
 * Variables disponibles:
 * @var array  $oro_tipo_options
 * @var array  $oro_rows
 * @var string $oro_tipo_value
 * @var array  $proveedor_options
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Inventario de oro</h1>
        <p>Control de metales</p>
    </div>

    <div class="card" id="inv-oro">
        <div class="ds-toolbar">
            <strong>Inventario de oro</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-oro">+ Nuevo Oro</button>
                <button type="button" class="btn-add" id="btn-compra-oro">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="oro-filtros-modern">
                <div>
                    <label class="form-label muted" for="oro-tipo-modern">Tipo</label>
                    <select id="oro-tipo-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($oro_tipo_options as $tipo): ?>
                            <option value="<?php echo (int) $tipo['value']; ?>">
                                <?php echo v_e((string) $tipo['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="oro-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="oro-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="inventario_oro.php#inv-oro" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="oro-tipo">Tipo</label>
                    <select id="oro-tipo" name="oro_tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($oro_tipo_options as $tipo): ?>
                            <option value="<?php echo (int) $tipo['value']; ?>" <?php echo (string) $oro_tipo_value === (string) $tipo['value'] ? 'selected' : ''; ?>>
                                <?php echo v_e((string) $tipo['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="inventario_oro.php#inv-oro" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($oro_rows as $row): ?>
                            <tr>
                                <td><?php echo v_e((string) $row['id']); ?></td>
                                <td><?php echo v_e((string) $row['tipo_oro_nombre']); ?></td>
                                <td><?php echo v_e((string) $row['peso_gramos']); ?></td>
                                <td><?php echo v_e((string) $row['precio_gramo']); ?></td>
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
