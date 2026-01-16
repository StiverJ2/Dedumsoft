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
// GET - Listar inventario de oro
// ============================================
if ($method === 'GET') {
    // Si se pide un ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            $stmt = $connLogic->prepare(
                'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, lote, fecha_registro, valor_total, proveedor_nombre, activo FROM fun_obtener_inventario_oro(0, 1000, NULL, NULL) WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('inventario_oro GET by id error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
            exit;
        }
        echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
        exit;
    }

    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $tipo = $_GET['tipo'] ?? null;
    $tipo = ($tipo === '') ? null : $tipo;
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, lote, fecha_registro, valor_total, proveedor_nombre, activo FROM fun_obtener_inventario_oro(:offset, :limit, :tipo, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, $tipo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('inventario_oro GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// ============================================
// POST - Crear nuevo registro de oro
// ============================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['tipo_oro', 'peso_gramos', 'precio_gramo'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_inventario_oro(:tipo_oro, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza, :lote)'
        );
        $stmt->bindValue(':tipo_oro', $input['tipo_oro'], PDO::PARAM_STR);
        $stmt->bindValue(':peso_gramos', $input['peso_gramos']);
        $stmt->bindValue(':precio_gramo', $input['precio_gramo']);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_ingreso', $input['fecha_ingreso'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':ubicacion', $input['ubicacion'] ?? null, isset($input['ubicacion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':pureza', $input['pureza'] ?? null);
        $stmt->bindValue(':lote', $input['lote'] ?? null, isset($input['lote']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Registro creado.', 'ID' => (int) $result]);
    } catch (PDOException $e) {
        error_log('inventario_oro POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear registro.']);
    }
    exit;
}

// ============================================
// PUT - Actualizar registro de oro
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
            'SELECT fun_actualizar_inventario_oro(:id, :tipo_oro, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza, :lote)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':tipo_oro', $input['tipo_oro'] ?? null, isset($input['tipo_oro']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':peso_gramos', $input['peso_gramos'] ?? null);
        $stmt->bindValue(':precio_gramo', $input['precio_gramo'] ?? null);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_ingreso', $input['fecha_ingreso'] ?? null, isset($input['fecha_ingreso']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion', $input['ubicacion'] ?? null, isset($input['ubicacion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':pureza', $input['pureza'] ?? null);
        $stmt->bindValue(':lote', $input['lote'] ?? null, isset($input['lote']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro actualizado.']);
    } catch (PDOException $e) {
        error_log('inventario_oro PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Registro no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// ============================================
// DELETE - Eliminar (soft-delete) registro de oro
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_oro(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro eliminado.']);
    } catch (PDOException $e) {
        error_log('inventario_oro DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Registro no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
