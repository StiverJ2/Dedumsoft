<?php
/**
 * ============================================================================
 * API REST: OPCIONES DINAMICAS PARA FORMULARIOS
 * ============================================================================
 *
 * Endpoint centralizado para obtener listas de opciones para dropdowns.
 *
 * @package Dedumsoft\API
 */

require_once __DIR__ . '/../../../private/api_helper.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

$repo = new CatalogoRepository($connLogic);

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

try {
    $opciones = [];
    foreach ($requested_types as $requested) {
        $opciones[$requested] = $repo->obtenerOpciones($requested);
    }

    if ($tipo && isset($opciones[$tipo])) {
        api_ok($opciones[$tipo]);
    } else {
        api_ok($opciones);
    }
} catch (InvalidArgumentException $e) {
    api_error(400, $e->getMessage());
} catch (PDOException $e) {
    api_log_error('opciones', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}
