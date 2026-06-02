<?php
/**
 * View: Usuarios
 *
 * Variables disponibles:
 * @var array  $rol_options
 * @var string $rol_filter_value
 * @var string $activo_filter
 * @var array  $usuarios_rows
 * @var int    $current_user_id
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Usuarios</h1>
        <p>Administracion de usuarios del sistema</p>
    </div>

    <div class="card">
        <div class="ds-toolbar">
            <div>
                <strong>Listado de usuarios</strong>
            </div>
            <div class="ds-toolbar-actions">
                <button type="button" id="usuarios-create-btn" class="btn-add">Nuevo usuario</button>
            </div>
        </div>
        <?php if (!$legacy): ?>
            <form class="d-flex flex-wrap gap-2 align-items-end" id="usuarios-filtros-modern">
                <div>
                    <label class="form-label muted" for="usuario-rol-modern">Rol</label>
                    <select id="usuario-rol-modern" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($rol_options as $opt): ?>
                            <option value="<?php echo v_e((string) $opt['value']); ?>">
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-sm" type="button" id="usuarios-filtrar-modern">Aplicar</button>
                <button class="btn btn-sm btn-secondary" type="button" id="usuarios-limpiar-modern">Limpiar</button>
            </form>
        <?php endif; ?>
        <?php if ($legacy): ?>
            <form method="get" action="usuarios.php" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label muted" for="usuario-rol">Rol</label>
                    <select id="usuario-rol" name="rol" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <?php foreach ($rol_options as $opt): ?>
                            <option value="<?php echo v_e((string) $opt['value']); ?>" <?php echo (string) $rol_filter_value === (string) $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo v_e((string) $opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label muted" for="usuario-activo">Estado</label>
                    <select id="usuario-activo" name="activo" class="form-select form-select-sm ds-field">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $activo_filter === '1' ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo $activo_filter === '0' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <button class="btn btn-sm" type="submit">Actualizar</button>
                <a href="usuarios.php" class="btn btn-sm btn-secondary">Limpiar</a>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="usuarios-table" class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($legacy): ?>
                        <?php if (empty($usuarios_rows)): ?>
                            <tr>
                                <td colspan="6">Sin usuarios para los filtros seleccionados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios_rows as $row): ?>
                                <tr>
                                    <td><?php echo v_e((string) $row['id_usuario']); ?></td>
                                    <td><?php echo v_e((string) $row['username']); ?></td>
                                    <td><?php echo v_e((string) $row['nombre']); ?></td>
                                    <td><?php echo v_e((string) $row['rol']); ?></td>
                                    <td><?php echo !empty($row['activo']) ? 'Activo' : 'Inactivo'; ?></td>
                                    <td class="ds-actions-col">
                                        <?php
                                        $is_active = !empty($row['activo']);
                                        $action_label = $is_active ? 'Desactivar' : 'Activar';
                                        $action_icon = $is_active ? 'cross.png' : 'arrow_refresh.png';
                                        $action_class = $is_active ? 'ds-action-btn--delete' : 'ds-action-btn--edit';
                                        $is_self = (int) $row['id_usuario'] === $current_user_id;
                                        ?>
                                        <div class="ds-actions-cell">
                                            <button type="button"
                                                class="ds-action-btn <?php echo $action_class; ?>"
                                                data-action="toggle"
                                                data-id="<?php echo v_e((string) $row['id_usuario']); ?>"
                                                data-activo="<?php echo $is_active ? '1' : '0'; ?>"
                                                title="<?php echo $action_label; ?>"
                                                aria-label="<?php echo $action_label; ?>"
                                                <?php echo $is_self && $is_active ? 'disabled' : ''; ?>>
                                                <img src="assets/icons/fatcow/16/<?php echo $action_icon; ?>"
                                                    alt="<?php echo $action_label; ?>">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
