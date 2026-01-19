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
// GET - Listar inventario de insumos
// ============================================
if ($method === 'GET') {
    // Si se pide un ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            $stmt = $connLogic->prepare(
                'SELECT id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, ubicacion_nombre, fecha_registro, proveedor_nombre, activo FROM fun_obtener_inventario_insumos(0, 1000, NULL, FALSE, NULL) WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('inventario_insumos GET by id error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
            exit;
        }
        echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
        exit;
    }

    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $categoria = $_GET['categoria'] ?? null;
    $categoria = ($categoria === '') ? null : $categoria;
    $stock_bajo = filter_var($_GET['stock_bajo'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($stock_bajo === null) {
        $stock_bajo = false;
    }
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, ubicacion_nombre, fecha_registro, proveedor_nombre, activo FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoria, $categoria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':stock_bajo', $stock_bajo, PDO::PARAM_BOOL);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario_insumos GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// ============================================
// POST - Crear nuevo insumo
// ============================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['nombre', 'categoria', 'unidad_medida', 'precio_unitario'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_inventario_insumos(:nombre, :categoria, :unidad_medida, :precio_unitario, :descripcion, :cantidad, :stock_minimo, :proveedor_id, :ubicacion_id)'
        );
        $stmt->bindValue(':nombre', $input['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $input['categoria'], PDO::PARAM_STR);
        $stmt->bindValue(':unidad_medida', $input['unidad_medida'], PDO::PARAM_STR);
        $stmt->bindValue(':precio_unitario', $input['precio_unitario']);
        $stmt->bindValue(':descripcion', $input['descripcion'] ?? null, isset($input['descripcion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':cantidad', $input['cantidad'] ?? 0);
        $stmt->bindValue(':stock_minimo', $input['stock_minimo'] ?? 0);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion_id', $input['ubicacion_id'] ?? null, isset($input['ubicacion_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Insumo creado.', 'ID' => (int) $result]);
    } catch (PDOException $e) {
        error_log('inventario_insumos POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear insumo.']);
    }
    exit;
}

// ============================================
// PUT - Actualizar insumo
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
            'SELECT fun_actualizar_inventario_insumos(:id, :nombre, :categoria, :descripcion, :cantidad, :unidad_medida, :precio_unitario, :stock_minimo, :proveedor_id, :ubicacion_id)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $input['nombre'] ?? null, isset($input['nombre']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':categoria', $input['categoria'] ?? null, isset($input['categoria']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':descripcion', $input['descripcion'] ?? null, isset($input['descripcion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':cantidad', $input['cantidad'] ?? null);
        $stmt->bindValue(':unidad_medida', $input['unidad_medida'] ?? null, isset($input['unidad_medida']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':precio_unitario', $input['precio_unitario'] ?? null);
        $stmt->bindValue(':stock_minimo', $input['stock_minimo'] ?? null);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion_id', $input['ubicacion_id'] ?? null, isset($input['ubicacion_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Insumo actualizado.']);
    } catch (PDOException $e) {
        error_log('inventario_insumos PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Insumo no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// ============================================
// DELETE - Eliminar (soft-delete) insumo
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_insumos(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Insumo eliminado.']);
    } catch (PDOException $e) {
        error_log('inventario_insumos DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Insumo no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
