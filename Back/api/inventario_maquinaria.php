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

// ============================================
// GET - Listar inventario de maquinaria
// ============================================
if ($method === 'GET') {
    // Si se pide un ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            $stmt = $connLogic->prepare(
                'SELECT id, nombre, tipo_maquinaria_id, tipo_codigo, tipo_nombre, marca, modelo, numero_serie, fecha_compra, valor_compra, estado, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo FROM fun_obtener_inventario_maquinaria(0, 1000, NULL, NULL) WHERE id = :id'
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
    $estado = $_GET['estado'] ?? null;
    $estado = ($estado === '') ? null : $estado;
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo_maquinaria_id, tipo_codigo, tipo_nombre, marca, modelo, numero_serie, fecha_compra, valor_compra, estado, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado, $estado === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
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
    $required = ['nombre', 'tipo_maquinaria_id', 'fecha_compra', 'valor_compra'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_inventario_maquinaria(:nombre, :tipo_maquinaria_id, :fecha_compra, :valor_compra, :marca, :modelo, :numero_serie, :estado, :ultima_mantenimiento, :proxima_mantenimiento, :ubicacion_id)'
        );
        $stmt->bindValue(':nombre', $input['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo_maquinaria_id', (int) $input['tipo_maquinaria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':fecha_compra', $input['fecha_compra'], PDO::PARAM_STR);
        $stmt->bindValue(':valor_compra', $input['valor_compra']);
        $stmt->bindValue(':marca', $input['marca'] ?? null, isset($input['marca']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':modelo', $input['modelo'] ?? null, isset($input['modelo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':numero_serie', $input['numero_serie'] ?? null, isset($input['numero_serie']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':estado', $input['estado'] ?? 'operativa', PDO::PARAM_STR);
        $stmt->bindValue(':ultima_mantenimiento', $input['ultima_mantenimiento'] ?? null, isset($input['ultima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':proxima_mantenimiento', $input['proxima_mantenimiento'] ?? null, isset($input['proxima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion_id', $input['ubicacion_id'] ?? null, isset($input['ubicacion_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
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
            'SELECT fun_actualizar_inventario_maquinaria(:id, :nombre, :tipo_maquinaria_id, :marca, :modelo, :numero_serie, :fecha_compra, :valor_compra, :estado, :ultima_mantenimiento, :proxima_mantenimiento, :ubicacion_id)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $input['nombre'] ?? null, isset($input['nombre']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo_maquinaria_id', isset($input['tipo_maquinaria_id']) ? (int) $input['tipo_maquinaria_id'] : null, isset($input['tipo_maquinaria_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marca', $input['marca'] ?? null, isset($input['marca']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':modelo', $input['modelo'] ?? null, isset($input['modelo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':numero_serie', $input['numero_serie'] ?? null, isset($input['numero_serie']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_compra', $input['fecha_compra'] ?? null, isset($input['fecha_compra']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':valor_compra', $input['valor_compra'] ?? null);
        $stmt->bindValue(':estado', $input['estado'] ?? null, isset($input['estado']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ultima_mantenimiento', $input['ultima_mantenimiento'] ?? null, isset($input['ultima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':proxima_mantenimiento', $input['proxima_mantenimiento'] ?? null, isset($input['proxima_mantenimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion_id', $input['ubicacion_id'] ?? null, isset($input['ubicacion_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
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