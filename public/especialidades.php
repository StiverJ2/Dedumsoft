<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: ESPECIALIDADES (REDIRIGIDA)
 * ============================================================================
 *
 * Las especialidades ahora se administran en Ajustes > Catalogos.
 * Se conserva esta ruta para compatibilidad.
 *
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../private/page_helper.php';

page_init(7); // Menú: Configuración

header('Location: ' . base_url() . '/catalogos.php?catalog=especialidades');
exit;
