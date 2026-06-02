<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE CONSUMO DE MATERIALES (ARTESANO)
 * ============================================================================
 *
 * Endpoint para registrar el consumo de materiales en una orden de produccion.
 *
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/ArtesanoRepository.php';

$repo = new ArtesanoRepository($connLogic);

api_init(3, ['POST']);

// =============================================================================
// POST: Registrar consumo de material
// =============================================================================
$input = api_json_body();
if ($input === null) {
    api_error(400, 'Datos JSON invalidos.');
}

api_require_fields($input, ['orden_id', 'tipo_material', 'material_id', 'cantidad']);

$orden_id      = (int) $input['orden_id'];
$tipo_material = strtolower(trim((string) $input['tipo_material']));
$material_id   = (int) $input['material_id'];
$cantidad      = (float) $input['cantidad'];

if (!in_array($tipo_material, ['oro', 'insumo'], true)) {
    api_error(400, 'tipo_material debe ser "oro" o "insumo".');
}

if ($cantidad <= 0) {
    api_error(400, 'cantidad debe ser mayor a 0.');
}

$user       = get_session_user();
$usuario_id = $user['id'] ?? null;

try {
    $result = $repo->registrarConsumo($orden_id, $tipo_material, $material_id, $cantidad, $usuario_id);

    if (!$result['success']) {
        api_error(400, $result['mensaje']);
    }

    api_ok(['id' => (int) $result['consumo_id']], 201, $result['mensaje']);
} catch (PDOException $e) {
    api_log_error('artesano_consumo', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}
