<?php
/**
 * View: Catalogos
 *
 * Variables disponibles:
 * @var array  $catalogs
 * @var string $selected_catalog
 * @var array  $ui_catalogs
 * @var array  $legacy_rows
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Catalogos</h1>
        <p>Administracion de catalogos base del sistema</p>
    </div>

    <?php if ($legacy): ?>
        <div class="card">
            <div class="ds-toolbar">
                <div>
                    <strong>Catalogo activo</strong>
                    <?php if ($selected_catalog !== '' && isset($catalogs[$selected_catalog])): ?>
                        <div class="ds-catalog-active">
                            <img class="ds-catalog-icon" src="assets/icons/fatcow/16/<?php echo v_e($catalogs[$selected_catalog]['icon']); ?>" alt="">
                            <span><?php echo v_e($catalogs[$selected_catalog]['label']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ds-toolbar-actions">
                    <button type="button" class="btn-add" id="catalog-create-btn">+ Nuevo registro</button>
                </div>
            </div>
            <form method="get" action="catalogos.php" class="mb-2">
                <label class="form-label muted" for="catalog-select">Catalogo</label>
                <select id="catalog-select" name="catalog" class="form-select form-select-sm ds-field"
                    onchange="this.form.submit()">
                    <?php foreach ($catalogs as $key => $cfg): ?>
                        <option value="<?php echo v_e($key); ?>" <?php echo $key === $selected_catalog ? 'selected' : ''; ?>>
                            <?php echo v_e($cfg['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="table-responsive">
                <table id="catalogos-table" class="table table-sm">
                    <thead>
                        <tr>
                            <?php foreach ($catalogs[$selected_catalog]['list_columns'] as $col): ?>
                                <th><?php echo v_e($col['label']); ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($legacy_rows as $row): ?>
                            <tr data-id="<?php echo (int) ($row['id'] ?? 0); ?>">
                                <?php foreach ($catalogs[$selected_catalog]['list_columns'] as $col): ?>
                                    <?php
                                    $key = $col['key'];
                                    $type = $col['type'] ?? '';
                                    $value = $row[$key] ?? '';
                                    ?>
                                    <td>
                                        <?php if ($type === 'bool'): ?>
                                            <?php echo !empty($value) ? 'Activo' : 'Inactivo'; ?>
                                        <?php elseif ($type === 'color'): ?>
                                            <span class="ds-color-dot" style="background: <?php echo v_e((string) $value); ?>"></span>
                                            <?php echo v_e((string) $value); ?>
                                        <?php else: ?>
                                            <?php echo v_e((string) $value); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="ds-actions-col"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <div class="col-lg-3">
                <div class="card">
                    <strong>Catalogos</strong>
                    <div class="list-group mt-2" id="catalog-list">
                        <?php foreach ($catalogs as $key => $cfg): ?>
                            <button type="button"
                                class="list-group-item list-group-item-action <?php echo $key === $selected_catalog ? 'active' : ''; ?>"
                                data-catalog="<?php echo v_e($key); ?>">
                                <span class="ds-emoji" aria-hidden="true"><?php echo v_e($cfg['emoji'] ?? ''); ?></span>
                                <span><?php echo v_e($cfg['label']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="ds-toolbar">
                        <strong id="catalog-title"></strong>
                        <div class="ds-toolbar-actions">
                            <button type="button" class="btn-add" id="catalog-create-btn">+ Nuevo registro</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="catalogos-table" class="table table-sm">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
