<?php
/**
 * View: Ubicaciones
 *
 * Variables disponibles:
 * @var array  $area_options
 * @var string $area_value
 * @var array  $ubicaciones_rows
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Ubicaciones</h1>
        <p>Gestion de ubicaciones fisicas de maquinaria e insumos</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Listado de ubicaciones</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-ubicacion">+ Nueva Ubicacion</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="ubic-filtros-modern">
                <div>
                    <label class="form-label muted" for="ubic-area-modern">Area</label>
                    <select id="ubic-area-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <?php foreach ($area_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>">
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="ubic-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="ubic-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="ubicaciones.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="ubic-area">Area</label>
                    <select id="ubic-area" name="area" class="form-select form-select-sm ds-field">
                        <option value="">Todas</option>
                        <?php foreach ($area_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>" <?php echo (string) $area_value === (string) $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
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
                        <th>Descripcion</th>
                        <th>Area</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($ubicaciones_rows as $row): ?>
                            <tr data-id="<?php echo (int) $row['id']; ?>">
                                <td><?php echo v_e((string) $row['id']); ?></td>
                                <td><?php echo v_e((string) $row['nombre']); ?></td>
                                <td><?php echo v_e((string) ($row['descripcion'] ?? '')); ?></td>
                                <td><?php echo v_e((string) ($row['area_nombre'] ?? 'General')); ?></td>
                                <td><?php echo !empty($row['activo']) ? 'Activo' : 'Inactivo'; ?></td>
                                <td class="ds-actions-col"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
