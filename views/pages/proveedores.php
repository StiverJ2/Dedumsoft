<?php
/**
 * View: Proveedores
 *
 * Variables disponibles:
 * @var array  $tipo_proveedor_options
 * @var array  $proveedores_rows
 * @var string $tipo_value
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Proveedores</h1>
        <p>Gestion de proveedores de materiales</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <strong>Listado de proveedores</strong>
            <div class="ds-toolbar-actions">
                <button type="button" class="btn-add" id="btn-add-proveedor">+ Nuevo Proveedor</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="prov-filtros-modern">
                <div>
                    <label class="form-label muted" for="prov-tipo-modern">Tipo</label>
                    <select id="prov-tipo-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($tipo_proveedor_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>">
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="prov-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="prov-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="proveedores.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="prov-tipo">Tipo</label>
                    <select id="prov-tipo" name="tipo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($tipo_proveedor_options as $opt): ?>
                            <option value="<?php echo (int) $opt['value']; ?>" <?php echo (string) $tipo_value === (string) $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php foreach ($proveedores_rows as $row): ?>
                            <tr>
                                <td><?php echo v_e((string) $row['id']); ?></td>
                                <td><?php echo v_e((string) $row['nombre']); ?></td>
                                <td><?php echo v_e((string) $row['tipo_nombre']); ?></td>
                                <td><?php echo v_e((string) ($row['contacto'] ?? '')); ?></td>
                                <td><?php echo v_e((string) ($row['telefono'] ?? '')); ?></td>
                                <td class="ds-actions-col"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
