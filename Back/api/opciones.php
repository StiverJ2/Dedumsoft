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
    $tipo = $_GET['tipo'] ?? null;

    try {
        $opciones = [];

        if ($tipo === 'areas' || !$tipo) {
            // Consultar tabla de catálogo de áreas
            $stmt = $connLogic->prepare('SELECT id, codigo, nombre, descripcion FROM areas WHERE activo = true ORDER BY orden, nombre');
            $stmt->execute();
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $opciones['areas'] = array_map(function ($area) {
                return [
                    'value' => $area['id'],
                    'label' => $area['nombre'],
                    'codigo' => $area['codigo'],
                    'descripcion' => $area['descripcion']
                ];
            }, $areas);
        }

        if ($tipo === 'tipos_proveedor' || !$tipo) {
            // Consultar tabla de catálogo de tipos de proveedor
            $stmt = $connLogic->prepare('SELECT id, codigo, nombre, descripcion FROM tipos_proveedor WHERE activo = true ORDER BY orden, nombre');
            $stmt->execute();
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $opciones['tipos_proveedor'] = array_map(function ($tipo) {
                return [
                    'value' => $tipo['id'],
                    'label' => $tipo['nombre'],
                    'codigo' => $tipo['codigo'],
                    'descripcion' => $tipo['descripcion']
                ];
            }, $tipos);
        }

        if ($tipo === 'tipos_oro' || !$tipo) {
            // Consultar tabla de catálogo de tipos de oro
            $stmt = $connLogic->prepare('SELECT id, codigo, nombre, kilates, pureza_porcentaje, descripcion FROM tipos_oro WHERE activo = true ORDER BY orden, kilates');
            $stmt->execute();
            $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $opciones['tipos_oro'] = array_map(function ($tipo) {
                return [
                    'value' => $tipo['id'],
                    'label' => $tipo['nombre'],
                    'codigo' => $tipo['codigo'],
                    'kilates' => $tipo['kilates'],
                    'pureza' => $tipo['pureza_porcentaje'],
                    'descripcion' => $tipo['descripcion']
                ];
            }, $tipos);
        }

        if ($tipo === 'estados_maquinaria' || !$tipo) {
            // Estados de maquinaria - mantener como array hasta crear tabla si se necesita
            $opciones['estados_maquinaria'] = [
                ['value' => 'operativa', 'label' => 'Operativa'],
                ['value' => 'mantenimiento', 'label' => 'Mantenimiento'],
                ['value' => 'reparacion', 'label' => 'Reparación'],
                ['value' => 'inactiva', 'label' => 'Inactiva']
            ];
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
