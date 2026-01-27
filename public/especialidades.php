<?php
/**
 * =========================================================================
 * PÁGINA PÚBLICA: ESPECIALIDADES (REDIRIGIDA)
 * =========================================================================
 *
 * Las especialidades ahora se administran en Ajustes > Catalogos.
 * Se conserva esta ruta para compatibilidad.
 */

require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

require_login('login.php');
require_menu_access(7); // Configuracion

header('Location: ' . base_url() . '/catalogos.php?catalog=especialidades');
exit;
