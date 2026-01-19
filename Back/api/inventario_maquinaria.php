<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(2);

// ============================================
// GET - Listar inventario de maquinaria
// ============================================
if ($method === 'GET') {
    // Si se pide un ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            $stmt = $connLogic->prepare(
                'SELECT id, sku, nombre, tipo_maquinaria_id, tipo_nombre, marca, modelo, fecha_compra, valor_compra, estado_id, estado_nombre, estado_color, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo FROM fun_obtener_inventario_maquinaria(0, 1000, NULL, NULL) WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('inventario_maquinaria GET by id error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
            exit;
        }
        echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
        exit;
    }

    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $estado_id = isset($_GET['estado_id']) && $_GET['estado_id'] !== '' ? (int) $_GET['estado_id'] : null;
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, sku, nombre, tipo_maquinaria_id, tipo_nombre, marca, modelo, fecha_compra, valor_compra, estado_id, estado_nombre, estado_color, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado_id, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $estado_id, $estado_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// ============================================
// POST - Crear nueva maquinaria
// ============================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['nombre', 'sku', 'tipo_maquinaria_id'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_inventario_maquinaria(:nombre, :sku, :tipo_maquinaria_id, :marca, :modelo, :fecha_compra, :valor_compra, :estado_id, :ubicacion_id)'
        );
        $stmt->bindValue(':nombre', $input['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':sku', $input['sku'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo_maquinaria_id', (int) $input['tipo_maquinaria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':marca', $input['marca'] ?? null, isset($input['marca']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':modelo', $input['modelo'] ?? null, isset($input['modelo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_compra', $input['fecha_compra'] ?? null, isset($input['fecha_compra']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':valor_compra', $input['valor_compra'] ?? null);
        $stmt->bindValue(':estado_id', isset($input['estado_id']) ? (int) $input['estado_id'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':ubicacion_id', isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? (int) $input['ubicacion_id'] : null, isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Maquinaria creada.', 'ID' => (int) $result]);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear maquinaria.']);
    }
    exit;
}

// ============================================
// PUT - Actualizar maquinaria
// ============================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_inventario_maquinaria(:id, :nombre, :sku, :tipo_maquinaria_id, :marca, :modelo, :fecha_compra, :valor_compra, :estado_id, :ultima_mantenimiento, :proxima_mantenimiento, :ubicacion_id, :activo)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $input['nombre'] ?? null, isset($input['nombre']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':sku', $input['sku'] ?? null, isset($input['sku']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo_maquinaria_id', isset($input['tipo_maquinaria_id']) ? (int) $input['tipo_maquinaria_id'] : null, isset($input['tipo_maquinaria_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marca', $input['marca'] ?? null, isset($input['marca']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':modelo', $input['modelo'] ?? null, isset($input['modelo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_compra', $input['fecha_compra'] ?? null, isset($input['fecha_compra']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':valor_compra', $input['valor_compra'] ?? null);
        $stmt->bindValue(':estado_id', isset($input['estado_id']) ? (int) $input['estado_id'] : null, isset($input['estado_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ultima_mantenimiento', $input['ultima_mantenimiento'] ?? null, isset($input['ultima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':proxima_mantenimiento', $input['proxima_mantenimiento'] ?? null, isset($input['proxima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion_id', isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? (int) $input['ubicacion_id'] : null, isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', isset($input['activo']) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN) : null, isset($input['activo']) ? PDO::PARAM_BOOL : PDO::PARAM_NULL);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Maquinaria actualizada.']);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Maquinaria no encontrada.' : 'Error al actualizar.']);
    }
    exit;
}

// ============================================
// DELETE - Eliminar (soft-delete) maquinaria
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_maquinaria(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Maquinaria eliminada.']);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Maquinaria no encontrada.' : 'Error al eliminar.']);
    }
    exit;
}
