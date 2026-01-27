<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: CONFIGURACIÓN DEL SISTEMA
 * ============================================================================
 * 
 * Página de configuración y preferencias del usuario.
 * Permite cambiar entre modo normal y modo legacy (IE8).
 * 
 * Características:
 * - Selector de modo de compatibilidad
 * - Modo Normal: CSS moderno, ES6, DataTables, gráficos interactivos
 * - Modo Legacy: Compatible con IE8, tablas simples, imágenes PNG
 * - Preferencia guardada en cookie por 1 año
 * 
 * Autenticación: Requerida
 * Autorización: Menú 7 (Configuración)
 * 
 * Parámetros GET:
 * - updated=1: Muestra mensaje de éxito
 * - error=csrf: Muestra error de seguridad
 * 
 * Métodos:
 * - GET: Muestra formulario de configuración
 * - POST: Guarda preferencia de modo en cookie
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';

require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';

// Verificar autenticación y autorización
require_login('login.php');
require_menu_access(7); // Menú: Configuración

// Determinar modo actual
$mode_override = dedumsoft_ui_mode_override();
$ua_legacy = dedumsoft_is_legacy_ua();
$current_mode = $mode_override !== '' ? $mode_override : ($ua_legacy ? 'legacy' : 'normal');
$status_msg = '';

// =============================================================================
// PROCESAMIENTO DEL FORMULARIO (POST)
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: ' . base_url() . '/configuracion.php?error=csrf');
        exit;
    }

    // Validar modo seleccionado
    $mode = strtolower(trim($_POST['ui_mode'] ?? ''));
    if ($mode !== 'legacy' && $mode !== 'normal') {
        $mode = $current_mode;
    }

    // Guardar preferencia en cookie (1 año)
    $secure = dedumsoft_cookie_secure();
    setcookie('dedumsoft_ui_mode', $mode, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,  // Necesario para leer desde JS
        'samesite' => 'Lax'
    ]);

    header('Location: ' . base_url() . '/configuracion.php?updated=1');
    exit;
}

// Mensajes de estado
if (($_GET['updated'] ?? '') === '1') {
    $status_msg = 'Preferencia guardada.';
} elseif (($_GET['error'] ?? '') === 'csrf') {
    $status_msg = 'Error de seguridad. Intenta de nuevo.';
}

include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
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
<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
</body>

</html>
