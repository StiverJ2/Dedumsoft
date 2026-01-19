<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'PUT'], true)) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(3);

if ($method === 'GET') {
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $estado = $_GET['estado'] ?? null;
    $estado = ($estado === '') ? null : $estado;

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones FROM fun_obtener_ordenes(:offset, :limit, :estado)'
        );
        $stmt->execute([':offset' => $offset, ':limit' => $limit, ':estado' => $estado]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ordenes GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    $id = isset($input['id']) ? (int) $input['id'] : 0;
    $artesano_id = isset($input['artesano_id']) ? (int) $input['artesano_id'] : 0;

    if ($id <= 0 || $artesano_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'id y artesano_id son requeridos.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_orden(:id, NULL, NULL, :artesano_id, NULL, NULL, NULL)'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        if (!$result) {
            http_response_code(404);
            echo json_encode(['CODIGO' => 404, 'MENSAJE' => 'Orden no encontrada.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log('ordenes PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al asignar artesano.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Orden actualizada.']);
    exit;
}
