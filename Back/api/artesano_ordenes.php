<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'PUT'])) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(3);

// ============================================
// GET - Obtener órdenes de un artesano
// ============================================
if ($method === 'GET') {
    $artesano_id = isset($_GET['artesano_id']) ? (int) $_GET['artesano_id'] : 0;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

    if ($artesano_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'artesano_id requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT * FROM fun_obtener_ordenes_artesano(:artesano_id, :offset, :limit)'
        );
        $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('artesano_ordenes GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// ============================================
// PUT - Actualizar estado de orden (artesano)
// ============================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    $id = isset($input['id']) ? (int) $input['id'] : 0;
    $estado_id = isset($input['estado_id']) ? (int) $input['estado_id'] : 0;

    if ($id <= 0 || $estado_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'id y estado_id son requeridos.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare('SELECT * FROM fun_actualizar_estado_orden(:orden_id, :estado_id)');
        $stmt->bindValue(':orden_id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $estado_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result['success']) {
            http_response_code(404);
            echo json_encode(['CODIGO' => 404, 'MENSAJE' => $result['mensaje']]);
            exit;
        }
    } catch (PDOException $e) {
        error_log('artesano_ordenes PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => $result['mensaje']]);
    exit;
}
