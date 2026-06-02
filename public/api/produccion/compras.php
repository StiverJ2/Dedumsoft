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
require_once PRIVATE_PATH . '/Controllers/CompraController.php';

$ctrl = new CompraController($connLogic);

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

$user       = get_session_user();
$usuario_id = $user['id_usuario'] ?? null;

// Inyectar usuario_id en input para que el controller lo use
$input['usuario_id'] = $usuario_id;

$result = $ctrl->registrar($input);
if (!$result['success']) api_error($result['code'], $result['message']);
api_ok($result['data'], $result['code'], $result['message']);
