<?php
/**
 * View: Configuracion
 *
 * Variables disponibles:
 * @var string $current_mode
 * @var string $status_msg
 * @var bool   $legacy
 */
?>
<div class="content">
    <div class="content-header">
        <h1>Configuracion</h1>
        <p>Ajustes generales del sistema</p>
    </div>
    <div class="card">
        <strong>Modo de compatibilidad</strong>
        <p>Selecciona la vista preferida para este equipo.</p>
        <?php if ($status_msg !== ''): ?>
            <p class="ds-status"><?php echo v_e($status_msg); ?></p>
        <?php endif; ?>
        <form method="post" action="configuracion.php">
            <input type="hidden" name="csrf_token" value="<?php echo v_e(dedumsoft_csrf_token()); ?>">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="ui_mode" id="ui-normal" value="normal" <?php echo $current_mode === 'normal' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ui-normal">Normal (recomendado)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="ui_mode" id="ui-legacy" value="legacy" <?php echo $current_mode === 'legacy' ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ui-legacy">Legacy (IE8)</label>
            </div>
            <button type="submit" class="btn btn-sm mt-2">Guardar</button>
        </form>
    </div>
</div>
