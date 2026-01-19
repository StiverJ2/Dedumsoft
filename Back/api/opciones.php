<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!require_api_auth()) {
    exit;
}

if ($method === 'GET') {
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
            'artesanos'
        ]);
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
            if ($requested === 'areas') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM areas WHERE activo = true ORDER BY orden, nombre');
                $stmt->execute();
                $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['areas'] = array_map(function ($area) {
                    return [
                        'value' => $area['id'],
                        'label' => $area['nombre'],
                        'descripcion' => $area['descripcion']
                    ];
                }, $areas);
            } elseif ($requested === 'tipos_proveedor') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM tipos_proveedor WHERE activo = true ORDER BY orden, nombre');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_proveedor'] = array_map(function ($tipo) {
                    return [
                        'value' => $tipo['id'],
                        'label' => $tipo['nombre'],
                        'descripcion' => $tipo['descripcion']
                    ];
                }, $tipos);
            } elseif ($requested === 'tipos_oro') {
                $stmt = $connLogic->prepare('SELECT id, nombre, kilates, pureza_porcentaje, descripcion FROM tipos_oro WHERE activo = true ORDER BY orden, kilates');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_oro'] = array_map(function ($tipo) {
                    return [
                        'value' => $tipo['id'],
                        'label' => $tipo['nombre'],
                        'kilates' => $tipo['kilates'],
                        'pureza' => $tipo['pureza_porcentaje'],
                        'descripcion' => $tipo['descripcion']
                    ];
                }, $tipos);
            } elseif ($requested === 'estados_maquinaria') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM estados_maquinaria WHERE activo = true ORDER BY orden');
                $stmt->execute();
                $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['estados_maquinaria'] = array_map(function ($est) {
                    return [
                        'value' => $est['id'],
                        'label' => $est['nombre'],
                        'descripcion' => $est['descripcion'],
                        'color' => $est['color']
                    ];
                }, $estados);
            } elseif ($requested === 'estados_orden') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM estados_orden WHERE activo = true ORDER BY orden');
                $stmt->execute();
                $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['estados_orden'] = array_map(function ($est) {
                    return [
                        'value' => $est['id'],
                        'label' => $est['nombre'],
                        'descripcion' => $est['descripcion'],
                        'color' => $est['color']
                    ];
                }, $estados);
            } elseif ($requested === 'prioridades') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM prioridades WHERE activo = true ORDER BY orden DESC');
                $stmt->execute();
                $prioridades = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['prioridades'] = array_map(function ($p) {
                    return [
                        'value' => $p['id'],
                        'label' => $p['nombre'],
                        'descripcion' => $p['descripcion'],
                        'color' => $p['color']
                    ];
                }, $prioridades);
            } elseif ($requested === 'tipos_material') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM tipos_material WHERE activo = true ORDER BY id');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_material'] = array_map(function ($t) {
                    return [
                        'value' => $t['id'],
                        'label' => $t['nombre'],
                        'descripcion' => $t['descripcion']
                    ];
                }, $tipos);
            } elseif ($requested === 'niveles_calidad') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM niveles_calidad WHERE activo = true ORDER BY orden');
                $stmt->execute();
                $niveles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['niveles_calidad'] = array_map(function ($n) {
                    return [
                        'value' => $n['id'],
                        'label' => $n['nombre'],
                        'descripcion' => $n['descripcion']
                    ];
                }, $niveles);
            } elseif ($requested === 'artesanos') {
                $stmt = $connLogic->prepare('SELECT id, nombre, apellido FROM artesanos WHERE activo = true ORDER BY nombre, apellido');
                $stmt->execute();
                $artesanos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['artesanos'] = array_map(function ($a) {
                    return [
                        'value' => $a['id'],
                        'label' => trim($a['nombre'] . ' ' . $a['apellido'])
                    ];
                }, $artesanos);
            }
        }

        // Retornar solo el tipo solicitado o todas
        if ($tipo && isset($opciones[$tipo])) {
            echo json_encode(['CODIGO' => 200, 'DATOS' => $opciones[$tipo]]);
        } else {
            echo json_encode(['CODIGO' => 200, 'DATOS' => $opciones]);
        }
    } catch (PDOException $e) {
        error_log('opciones GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
