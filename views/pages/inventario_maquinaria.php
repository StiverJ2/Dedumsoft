<?php
/**
 * View: Inventario de Maquinaria
 *
 * Variables disponibles:
 * @var array  $maq_estado_options
 * @var array  $maq_rows
 * @var int|null $maq_estado_id
 * @var array  $proveedor_options
 * @var array  $ubicacion_options
 * @var array  $tipo_maquinaria_options
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Maquinaria</h1>
        <p>Control de equipos y mantenimiento</p>
    </div>

    <div class="card" id="inv-maquinaria">
        <div class="ds-toolbar">
            <strong>Maquinaria</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-maquinaria">+ Nueva Maquinaria</button>
                <button type="button" class="btn-add" id="btn-compra-maquinaria">+ Registrar Compra</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
        <form class="d-flex flex-wrap gap-2 align-items-end" id="maq-filtros-modern">
            <div>
                <label class="form-label muted" for="maq-estado-modern">Estado</label>
                <select id="maq-estado-modern" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($maq_estado_options as $est): ?>
                    <option value="<?php echo (int) $est['id']; ?>">
                        <?php echo v_e((string) $est['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-sm" type="button" id="maq-filtrar-modern">Aplicar</button>
            <button class="btn btn-sm btn-secondary" type="button" id="maq-limpiar-modern">Limpiar</button>
        </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
        <form method="get" action="inventario_maquinaria.php#inv-maquinaria"
            class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label muted" for="maq-estado-id">Estado</label>
                <select id="maq-estado-id" name="maq_estado_id" class="form-select form-select-sm ds-field">
                    <option value="">Todos</option>
                    <?php foreach ($maq_estado_options as $est): ?>
                    <option value="<?php echo (int) $est['id']; ?>"
                        <?php echo $maq_estado_id === (int) $est['id'] ? 'selected' : ''; ?>>
                        <?php echo v_e((string) $est['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-sm" type="submit">Actualizar</button>
            <a href="inventario_maquinaria.php#inv-maquinaria" class="btn btn-sm btn-secondary">Limpiar</a>
        </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="maq-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Ubicacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                    <?php foreach ($maq_rows as $row): ?>
                    <tr>
                        <td><?php echo v_e((string) $row['id']); ?></td>
                        <td><?php echo v_e((string) ($row['sku'] ?? '')); ?></td>
                        <td><?php echo v_e((string) $row['nombre']); ?></td>
                        <td><?php echo v_e((string) ($row['tipo_nombre'] ?? '')); ?></td>
                        <td><?php echo v_e((string) ($row['estado_nombre'] ?? '')); ?></td>
                        <td><?php echo v_e((string) ($row['ubicacion_nombre'] ?? '')); ?></td>
                        <td class="ds-actions-col"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
