<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: GESTIÓN DE USUARIOS
 * ============================================================================
 *
 * Delega toda la lógica de negocio a UsuarioController y la vista a
 * views/pages/usuarios.php.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/UsuarioController.php';

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
page_init(5); // Menú: Usuarios
$legacy = page_is_legacy();

$current_user = get_session_user();
$current_user_id = (int) ($current_user['id_usuario'] ?? 0);

$ctrl = new UsuarioController($connLogic);
$pageData = $ctrl->pageData($_GET, $legacy, $current_user_id);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(5);
render_view('pages/usuarios', $pageData);
page_render_end();
?>
<?php if (!$legacy): ?>
<script>
    window._currentUserId = <?php echo json_encode($pageData['current_user_id'] ?? 0); ?>;
</script>
<?php endif; ?>
<script src="assets/js/pages/<?php echo basename(__FILE__, '.php') . ($legacy ? '-legacy' : '') . '.js'; ?>"></script>
