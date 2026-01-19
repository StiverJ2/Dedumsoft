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

require_once __DIR__ . '/../../connection/guard.php';
$legacy = dedumsoft_is_legacy_browser();
?>
</main>
</div>
<?php if ($legacy): ?>
    <!-- Scripts para navegadores legacy (IE8/IE7) -->
    <script src="assets/table-sort.js"></script>
    <script src="assets/crud-legacy.js"></script>
<?php else: ?>
    <!-- Scripts para navegadores modernos -->
    <script src="assets/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="assets/jquery.dataTables.min.js"></script>
    <script src="assets/dataTables.bootstrap5.min.js"></script>
    <script src="../bootstrap/popper.min.js"></script>
    <script src="../bootstrap/bootstrap.min.js"></script>
    <script>
        // Indicador global para usar emojis en vez de imágenes
        window.DEDUMSOFT_ICON_MODE = 'emoji';
    </script>
    <script src="assets/crud.js"></script>
<?php endif; ?>