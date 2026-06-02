<?php
/**
 * ============================================================================
 * API REST: CATALOGOS MAESTROS
 * ============================================================================
 *
 * CRUD generico para catalogos del sistema.
 *
 * Autenticacion: Requerida
 * Autorizacion: Menu 7 (Configuracion)
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

$repo = new CatalogoRepository($connLogic);
$method = api_init(7, ['GET', 'POST', 'PATCH', 'DELETE']);

$catalog = $_GET['catalog'] ?? '';
if ($catalog === '') {
    api_error(400, 'Catalogo requerido.');
}

// =============================================================================
// GET: Listar/buscar
// =============================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;
    $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    try {
        $rows = $repo->obtenerMaestros($catalog, $id, $offset, $limit, $_GET['activo'] ?? null);
    } catch (InvalidArgumentException $e) {
        api_error(400, $e->getMessage());
    } catch (PDOException $e) {
        api_log_error('catalogos_maestros', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok($rows);
}

// =============================================================================
// POST: Crear
// =============================================================================
if ($method === 'POST') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }

    try {
        $id = $repo->crearMaestro($catalog, $input);
    } catch (InvalidArgumentException $e) {
        api_error(400, $e->getMessage());
    } catch (PDOException $e) {
        api_log_error('catalogos_maestros', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    api_ok(['id' => $id], 201, 'Registro creado.');
}

// =============================================================================
// PATCH: Actualizar
// =============================================================================
if ($method === 'PATCH') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    if (!isset($input['id']) || (int) $input['id'] <= 0) {
        api_error(400, 'ID requerido.');
    }

    $id = (int) $input['id'];

    try {
        $ok = $repo->actualizarMaestro($catalog, $id, $input);
    } catch (InvalidArgumentException $e) {
        api_error(400, $e->getMessage());
    } catch (PDOException $e) {
        api_log_error('catalogos_maestros', 'PATCH', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo actualizar el registro.');
    }

    api_ok(null, 200, 'Registro actualizado.');
}

// =============================================================================
// DELETE: Eliminar
// =============================================================================
if ($method === 'DELETE') {
    $input = api_json_body();
    if ($input === null) {
        api_error(400, 'Datos JSON invalidos.');
    }
    if (!isset($input['id']) || (int) $input['id'] <= 0) {
        api_error(400, 'ID requerido.');
    }

    $id = (int) $input['id'];

    try {
        $ok = $repo->eliminarMaestro($catalog, $id);
    } catch (InvalidArgumentException $e) {
        api_error(400, $e->getMessage());
    } catch (PDOException $e) {
        api_log_error('catalogos_maestros', 'DELETE', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        api_error(500, 'Error interno del servidor.');
    }

    if (!$ok) {
        api_error(422, 'No se pudo eliminar el registro.');
    }

    api_ok(null, 200, 'Registro eliminado.');
}
