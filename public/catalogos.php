<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: CATÁLOGOS MAESTROS
 * ============================================================================
 *
 * Administración de catálogos base del sistema:
 * áreas, tipos, estados, prioridades, productos y niveles de calidad.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Database/CatalogConfig.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(7); // Menú: Configuración
$legacy = page_is_legacy();

// =============================================================================
// DATA LAYER
// =============================================================================

$catRepo = new CatalogoRepository($connLogic);

$catalogs = dedumsoft_catalogs_config();
$catalog_keys = array_keys($catalogs);
$default_catalog = count($catalog_keys) ? $catalog_keys[0] : '';
$selected_catalog = isset($_GET['catalog']) && isset($catalogs[$_GET['catalog']]) ? $_GET['catalog'] : $default_catalog;
$ui_catalogs = [];
foreach ($catalogs as $key => $cfg) {
    $ui_catalogs[$key] = [
        'label' => $cfg['label'],
        'emoji' => $cfg['emoji'] ?? '',
        'list_columns' => $cfg['list_columns'],
        'fields' => $cfg['fields']
    ];
}

$legacy_rows = [];
if ($legacy && $selected_catalog !== '') {
    try {
        $legacy_rows = $catRepo->obtenerMaestros($selected_catalog, null, 0, 200, null);
    } catch (Exception $e) {
        error_log('catalogos legacy error: ' . $e->getMessage());
    }
}

// =============================================================================
// RENDER LAYER
// =============================================================================

page_render_start(7);
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
                            <img class="ds-catalog-icon" src="assets/icons/fatcow/16/<?php echo page_e($catalogs[$selected_catalog]['icon']); ?>" alt="">
                            <span><?php echo page_e($catalogs[$selected_catalog]['label']); ?></span>
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
                        <option value="<?php echo page_e($key); ?>" <?php echo $key === $selected_catalog ? 'selected' : ''; ?>>
                            <?php echo page_e($cfg['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="table-responsive">
                <table id="catalogos-table" class="table table-sm">
                    <thead>
                        <tr>
                            <?php foreach ($catalogs[$selected_catalog]['list_columns'] as $col): ?>
                                <th><?php echo page_e($col['label']); ?></th>
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
                                            <span class="ds-color-dot" style="background: <?php echo page_e((string) $value); ?>"></span>
                                            <?php echo page_e((string) $value); ?>
                                        <?php else: ?>
                                            <?php echo page_e((string) $value); ?>
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
                                data-catalog="<?php echo page_e($key); ?>">
                                <span class="ds-emoji" aria-hidden="true"><?php echo page_e($cfg['emoji'] ?? ''); ?></span>
                                <span><?php echo page_e($cfg['label']); ?></span>
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
<?php
page_render_end();
?>
<?php if ($legacy): ?>
<script>
    window._catalogKey = '<?php echo page_e($selected_catalog); ?>';
    window._catalogConfig = <?php echo json_encode($catalogs[$selected_catalog]); ?>;
</script>
<?php else: ?>
<script>
    window._catalogs = <?php echo json_encode($ui_catalogs); ?>;
    window._currentCatalogKey = '<?php echo page_e($selected_catalog); ?>';
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
