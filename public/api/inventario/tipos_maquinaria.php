<?php
/**
 * ============================================================================
 * API REST: TIPOS DE MAQUINARIA
 * ============================================================================
 *
 * Endpoint para obtener los tipos de maquinaria disponibles.
 *
 * Autenticacion: Requerida (JWT en sesion)
 * Autorizacion: Menu 2 (Inventario)
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

$repo = new CatalogoRepository($connLogic);
$method = api_init(2, ['GET']);

// =============================================================================
// GET: Listar tipos de maquinaria
// =============================================================================
if ($method === 'GET') {
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        $rows = $repo->obtenerTiposMaquinaria($activo);
    } catch (PDOException $e) {
        api_log_error('tipos_maquinaria', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}
