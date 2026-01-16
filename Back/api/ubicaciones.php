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
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $area = $_GET['area'] ?? null;
    $area = ($area === '') ? null : $area;
    $activo = isset($_GET['activo']) ? ($_GET['activo'] === '1' || $_GET['activo'] === 'true') : true;

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, descripcion, area, activo, created_at FROM fun_obtener_ubicaciones(:offset, :limit, :area, :activo)'
        );
        $stmt->execute([':offset' => $offset, ':limit' => $limit, ':area' => $area, ':activo' => $activo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ubicaciones GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $nombre = $input['nombre'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    $area = $input['area'] ?? 'General';

    if (!$nombre) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Nombre es requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare('SELECT fun_crear_ubicacion(:nombre, :descripcion, :area)');
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':area' => $area]);
        $result = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('ubicaciones POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Ubicación creada.', 'id' => $result]);
    exit;
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    $nombre = $input['nombre'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    $area = $input['area'] ?? null;
    $activo = isset($input['activo']) ? (bool) $input['activo'] : null;

    try {
        $stmt = $connLogic->prepare('SELECT fun_actualizar_ubicacion(:id, :nombre, :descripcion, :area, :activo)');
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':area' => $area,
            ':activo' => $activo
        ]);
    } catch (PDOException $e) {
        error_log('ubicaciones PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Ubicación actualizada.']);
    exit;
}

// ============================================
// DELETE - Eliminar (soft-delete) ubicación
// ============================================
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare('SELECT fun_eliminar_ubicacion(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Ubicación eliminada.']);
    } catch (PDOException $e) {
        error_log('ubicaciones DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Ubicación no encontrada.' : 'Error al eliminar.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
