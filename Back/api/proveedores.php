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
// GET - Listar proveedores
// ============================================
if ($method === 'GET') {
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $tipo = $_GET['tipo'] ?? null;
    $tipo = ($tipo === '') ? null : $tipo;
    $activo_raw = $_GET['activo'] ?? null;
    if ($activo_raw === null) {
        $activo = true;
    } else {
        $activo = filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo, contacto, telefono, email, direccion, activo, fecha_registro FROM fun_obtener_proveedores(:offset, :limit, :tipo, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, $tipo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('proveedores GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// ============================================
// POST - Crear nuevo proveedor
// ============================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['nombre', 'tipo'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    // Validar tipo
    $tipos_validos = ['oro', 'insumos', 'maquinaria'];
    if (!in_array($input['tipo'], $tipos_validos)) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo debe ser: oro, insumos o maquinaria']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_proveedor(:nombre, :tipo, :contacto, :telefono, :email, :direccion)'
        );
        $stmt->bindValue(':nombre', $input['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $input['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':contacto', $input['contacto'] ?? null, isset($input['contacto']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':telefono', $input['telefono'] ?? null, isset($input['telefono']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':email', $input['email'] ?? null, isset($input['email']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':direccion', $input['direccion'] ?? null, isset($input['direccion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Proveedor creado.', 'ID' => (int) $result]);
    } catch (PDOException $e) {
        error_log('proveedores POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear proveedor.']);
    }
    exit;
}

// ============================================
// PUT - Actualizar proveedor
// ============================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    // Validar tipo si se proporciona
    if (isset($input['tipo'])) {
        $tipos_validos = ['oro', 'insumos', 'maquinaria'];
        if (!in_array($input['tipo'], $tipos_validos)) {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo debe ser: oro, insumos o maquinaria']);
            exit;
        }
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_proveedor(:id, :nombre, :tipo, :contacto, :telefono, :email, :direccion, :activo)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $input['nombre'] ?? null, isset($input['nombre']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo', $input['tipo'] ?? null, isset($input['tipo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':contacto', $input['contacto'] ?? null, isset($input['contacto']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':telefono', $input['telefono'] ?? null, isset($input['telefono']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':email', $input['email'] ?? null, isset($input['email']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':direccion', $input['direccion'] ?? null, isset($input['direccion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', $input['activo'] ?? null, isset($input['activo']) ? PDO::PARAM_BOOL : PDO::PARAM_NULL);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Proveedor actualizado.']);
    } catch (PDOException $e) {
        error_log('proveedores PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Proveedor no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// ============================================
// DELETE - Eliminar (soft-delete) proveedor
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_proveedor(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Proveedor eliminado.']);
    } catch (PDOException $e) {
        error_log('proveedores DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Proveedor no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
