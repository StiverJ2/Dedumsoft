<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

$mode_override = dedumsoft_ui_mode_override();
$ua_legacy = dedumsoft_is_legacy_ua();
$current_mode = $mode_override !== '' ? $mode_override : ($ua_legacy ? 'legacy' : 'normal');
$status_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: configuracion.php?error=csrf');
        exit;
    }
    $mode = strtolower(trim($_POST['ui_mode'] ?? ''));
    if ($mode !== 'legacy' && $mode !== 'normal') {
        $mode = $current_mode;
    }
    $secure = dedumsoft_cookie_secure();
    setcookie('dedumsoft_ui_mode', $mode, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/Back',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    header('Location: configuracion.php?updated=1');
    exit;
}

if (($_GET['updated'] ?? '') === '1') {
    $status_msg = 'Preferencia guardada.';
} elseif (($_GET['error'] ?? '') === 'csrf') {
    $status_msg = 'Error de seguridad. Intenta de nuevo.';
}

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
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
            <p class="ds-status"><?php echo htmlspecialchars($status_msg); ?></p>
        <?php endif; ?>
        <form method="post" action="configuracion.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(dedumsoft_csrf_token()); ?>">
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
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>