<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: CONFIGURACIÓN DEL SISTEMA
 * ============================================================================
 *
 * Delega toda la lógica de negocio a CatalogoController y la vista a
 * views/pages/configuracion.php.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';
require_once PRIVATE_PATH . '/Controllers/CatalogoController.php';

// =============================================================================
// INICIALIZACIÓN
// =============================================================================
page_init(7); // Menú: Configuración
$legacy = page_is_legacy();

$ctrl = new CatalogoController($connLogic);

// =============================================================================
// HTTP-LEVEL POST HANDLING (cookie + redirect se mantienen en la página)
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: ' . base_url() . '/configuracion.php?error=csrf');
        exit;
    }

    $result = $ctrl->guardarConfiguracion($_POST['ui_mode'] ?? '');
    $mode = $result['data']['mode'];

    $secure = dedumsoft_cookie_secure();
    setcookie('dedumsoft_ui_mode', $mode, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);

    header('Location: ' . base_url() . '/configuracion.php?updated=1');
    exit;
}

// =============================================================================
// CONTROLLER → OBTENER DATOS
// =============================================================================
$pageData = $ctrl->pageDataConfiguracion($_GET, $legacy);

// =============================================================================
// RENDER
// =============================================================================
page_render_start(7);
render_view('pages/configuracion', $pageData);
page_render_end();
?>
