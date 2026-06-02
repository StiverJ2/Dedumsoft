<?php
/**
 * ============================================================================
 * API REST: REGISTRO DE COMPRAS
 * ============================================================================
 *
 * Endpoint para registrar entradas de inventario (compras).
 *
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/CompraRepository.php';

// Solo aceptar POST
if (!validateHttpMethod(['POST'])) {
    exit;
}

if (!require_api_auth()) {
    exit;
}

// Autorizacion: menu 2 (Inventario) o menu 6 (Proveedores)
if (!dedumsoft_user_can_menu(2) && !dedumsoft_user_can_menu(6)) {
    dedumsoft_forbidden();
}

// =============================================================================
// POST: Registrar compra/entrada de inventario
// =============================================================================
$input = api_json_body();
if ($input === null) {
    api_error(400, 'Datos JSON invalidos.');
}

$tipo       = strtolower(trim((string) ($input['tipo_inventario'] ?? '')));
$item_id    = $input['item_id'] ?? null;
$cantidad   = $input['cantidad'] ?? null;
$motivo     = $input['motivo'] ?? null;
$referencia = $input['referencia'] ?? null;
$fecha      = $input['fecha'] ?? null;

if ($tipo === '' || $item_id === null || $item_id === '') {
    api_error(400, 'Tipo e item_id requeridos.');
}

if ($cantidad === null || !is_numeric($cantidad) || (float) $cantidad <= 0) {
    api_error(400, 'Cantidad invalida.');
}

if ($fecha === '') {
    $fecha = null;
}

$user       = get_session_user();
$usuario_id = $user['id_usuario'] ?? null;

$repo = new CompraRepository($connLogic);

try {
    $mov_id = $repo->registrar($tipo, (int) $item_id, (float) $cantidad, $motivo, $referencia, $usuario_id, $fecha);
    api_ok(['id' => $mov_id], 201, 'Compra registrada.');
} catch (InvalidArgumentException $e) {
    api_error(400, $e->getMessage());
} catch (PDOException $e) {
    api_log_error('compras', 'POST', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
    api_error($code, $code === 404 ? 'Item no encontrado.' : 'Error al registrar compra.');
}
