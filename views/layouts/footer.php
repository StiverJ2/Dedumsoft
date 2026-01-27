<?php
/**
 * ============================================================================
 * PARTIAL: PIE DE PÁGINA COMÚN
 * ============================================================================
 * 
 * Archivo parcial que cierra las etiquetas HTML y carga los scripts
 * JavaScript necesarios según el modo del navegador.
 * 
 * Para navegadores MODERNOS:
 * - jQuery 3.7.1
 * - Axios (peticiones HTTP)
 * - Notyf (notificaciones toast)
 * - DataTables + Bootstrap 5 integration
 * - Popper.js + Bootstrap.js
 * - crud.js (funcionalidad CRUD principal)
 * 
 * Para navegadores LEGACY (IE8):
 * - table-sort.js (ordenamiento simple de tablas)
 * - crud-legacy.js (CRUD con JSONP y sin ES6)
 * 
 * @package Dedumsoft\Partials
 * @author  Equipo Dedumsoft
 */

require_once PRIVATE_PATH . '/Database/Guard.php';
$legacy = dedumsoft_is_legacy_browser();
?>
</main>
</div>
<?php if ($legacy): ?>
    <!-- Scripts para navegadores legacy (IE8/IE7) -->
    <script src="assets/js/table-sort.js"></script>
    <script src="assets/js/crud-legacy.js"></script>
<?php else: ?>
    <!-- Scripts para navegadores modernos -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/axios.min.js"></script>
    <link rel="stylesheet" href="assets/css/notyf.min.css">
    <script src="assets/js/notyf.min.js"></script>
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        // Indicador global para usar emojis en vez de imágenes
        window.DEDUMSOFT_ICON_MODE = 'emoji';
    </script>
    <script src="assets/js/crud.js"></script>
    <script src="assets/js/sidebar.js"></script>
<?php endif; ?>
