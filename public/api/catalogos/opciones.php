<?php
/**
 * ============================================================================
 * API REST: OPCIONES DINAMICAS PARA FORMULARIOS
 * ============================================================================
 *
 * Delega toda la lógica de negocio a CatalogoController.
 * Este archivo solo maneja HTTP: autenticación, método y respuesta JSON.
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Controllers/CatalogoController.php';

$ctrl = new CatalogoController($connLogic);

// Opciones usa GET pero valida permisos de forma granular
if (!validateHttpMethod('GET')) {
    exit;
}

if (!require_api_auth()) {
    exit;
}

$tipo_raw = $_GET['tipo'] ?? null;
$tipo = $tipo_raw !== null ? trim((string) $tipo_raw) : null;
if ($tipo === '') {
    $tipo = null;
}

$allowed_types = [];

if (dedumsoft_user_can_menu(2)) {
    $allowed_types = array_merge($allowed_types, ['areas', 'tipos_oro', 'estados_maquinaria']);
}

if (dedumsoft_user_can_menu(6)) {
    $allowed_types[] = 'tipos_proveedor';
}

if (dedumsoft_user_can_menu(3)) {
    $allowed_types = array_merge($allowed_types, [
        'estados_orden',
        'prioridades',
        'tipos_material',
        'niveles_calidad',
        'artesanos',
        'productos'
    ]);
}

if (dedumsoft_user_can_menu(5) || dedumsoft_user_can_menu(7)) {
    $allowed_types[] = 'especialidades';
}

$allowed_types = array_values(array_unique($allowed_types));

if ($tipo !== null && !in_array($tipo, $allowed_types, true)) {
    dedumsoft_forbidden();
}

$requested_types = $tipo !== null ? [$tipo] : $allowed_types;
if (!$requested_types) {
    dedumsoft_forbidden();
}

$result = $ctrl->listarOpciones($requested_types);

if (!$result['success']) {
    api_error($result['code'], $result['message']);
}

if ($tipo && isset($result['data'][$tipo])) {
    api_ok($result['data'][$tipo]);
} else {
    api_ok($result['data']);
}
