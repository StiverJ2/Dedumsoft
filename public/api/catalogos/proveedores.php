<?php
/**
 * ============================================================================
 * API REST: PROVEEDORES
 * ============================================================================
 * 
 * Endpoint CRUD para gestión de proveedores de la joyería.
 * Incluye información de contacto y tipo de proveedor.
 * 
 * Métodos soportados:
 * - GET: Listar proveedores (paginado, filtrable por tipo)
 * - POST: Crear nuevo proveedor
 * - PATCH: Actualizar proveedor existente
 * - DELETE: Eliminar proveedor (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: 
 * - GET: Menú 6 (Proveedores) o Menú 2 (Inventario)
 * - POST/PATCH/DELETE: Menú 6 (Proveedores)
 * 
 * Campos principales:
 * - nombre: Razón social o nombre del proveedor
 * - tipo_proveedor_id: Tipo de proveedor (del catálogo)
 * - contacto: Nombre de la persona de contacto
 * - telefono/email: Datos de comunicación
 * - direccion: Dirección física
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

if (!validateHttpMethod(['GET', 'POST', 'PATCH', 'DELETE'])) {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Verificar autenticación
if (!require_api_auth()) {
    exit;
}

// Control de acceso especial:
// - GET: Accesible desde Proveedores (6) o Inventario (2)
// - POST/PATCH/DELETE: Solo desde Proveedores (6)
$can_proveedores = dedumsoft_user_can_menu(6);
$can_inventario = dedumsoft_user_can_menu(2);
if ($method === 'GET') {
    if (!$can_proveedores && !$can_inventario) {
        dedumsoft_forbidden();
    }
} elseif (!$can_proveedores) {
    dedumsoft_forbidden();
}

// =============================================================================
// GET: Listar proveedores
// =============================================================================
// Parámetros:
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//   - tipo_id (int): Filtrar por tipo de proveedor (opcional)
//   - activo (bool): Filtrar por estado activo/inactivo (default: true)
//
// Respuesta: { CODIGO: 200, MENSAJE: 'OK', DATOS: [...] }
if ($method === 'GET') {
    // Parsear parámetros de paginación y filtros
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;

    // Manejo especial del parámetro 'activo'
    // null = sin parámetro (default true), string = parsear booleano
    $activo_raw = $_GET['activo'] ?? null;
    if ($activo_raw === null) {
        $activo = true;
    } else {
        $activo = filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    try {
        // Llamar función PostgreSQL con JOINs automáticos
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, tipo_proveedor_id, tipo_nombre, contacto, telefono, email, direccion, activo, fecha_registro FROM fun_obtener_proveedores(:offset, :limit, :tipo_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $tipo_id, $tipo_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('proveedores GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear nuevo proveedor
// =============================================================================
// Body JSON:
//   - nombre (string, requerido): Razón social o nombre
//   - tipo_proveedor_id (int, requerido): ID del tipo de proveedor
//   - contacto (string, opcional): Nombre de la persona de contacto
//   - telefono (string, opcional): Teléfono de contacto
//   - email (string, opcional): Correo electrónico
//   - direccion (string, opcional): Dirección física
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Proveedor creado.', DATOS: { id: <new_id> } }
if ($method === 'POST') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['nombre', 'tipo_proveedor_id'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => "Campo requerido: $field"]);
            exit;
        }
    }

    try {
        // Llamar función de creación en PostgreSQL
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_proveedor(:nombre, :tipo_proveedor_id, :contacto, :telefono, :email, :direccion)'
        );
        $stmt->bindValue(':nombre', $input['nombre'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo_proveedor_id', (int) $input['tipo_proveedor_id'], PDO::PARAM_INT);
        $stmt->bindValue(':contacto', $input['contacto'] ?? null, isset($input['contacto']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':telefono', $input['telefono'] ?? null, isset($input['telefono']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':email', $input['email'] ?? null, isset($input['email']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':direccion', $input['direccion'] ?? null, isset($input['direccion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        // Obtener ID del nuevo registro
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode([
            'CODIGO' => 201,
            'MENSAJE' => 'Proveedor creado.',
            'DATOS' => ['id' => (int) $result]
        ]);
    } catch (PDOException $e) {
        error_log('proveedores POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear proveedor.']);
    }
    exit;
}

// =============================================================================
// PATCH: Actualizar proveedor existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID del proveedor a actualizar
//   - nombre (string, opcional): Nuevo nombre
//   - tipo_proveedor_id (int, opcional): Nuevo tipo
//   - contacto (string, opcional): Nuevo contacto
//   - telefono (string, opcional): Nuevo teléfono
//   - email (string, opcional): Nuevo email
//   - direccion (string, opcional): Nueva dirección
//   - activo (bool, opcional): Estado de activación
//
// Nota: Solo se actualizan los campos proporcionados (PATCH parcial)
// Respuesta: { CODIGO: 200, MENSAJE: 'Proveedor actualizado.' }
if ($method === 'PATCH') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    try {
        // Llamar función de actualización en PostgreSQL
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_proveedor(:id, :nombre, :tipo_proveedor_id, :contacto, :telefono, :email, :direccion, :activo)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $input['nombre'] ?? null, isset($input['nombre']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':tipo_proveedor_id', isset($input['tipo_proveedor_id']) ? (int) $input['tipo_proveedor_id'] : null, isset($input['tipo_proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':contacto', $input['contacto'] ?? null, isset($input['contacto']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':telefono', $input['telefono'] ?? null, isset($input['telefono']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':email', $input['email'] ?? null, isset($input['email']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':direccion', $input['direccion'] ?? null, isset($input['direccion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':activo', $input['activo'] ?? null, isset($input['activo']) ? PDO::PARAM_BOOL : PDO::PARAM_NULL);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Proveedor actualizado.']);
    } catch (PDOException $e) {
        error_log('proveedores PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el proveedor
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Proveedor no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// =============================================================================
// DELETE: Eliminar proveedor (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se marca como inactivo (activo=false)
// Esto preserva las referencias de compras históricas
//
// Entrada (JSON body o query string):
//   - id (int, requerido): ID del proveedor a eliminar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Proveedor eliminado.' }
if ($method === 'DELETE') {
    // Obtener ID de body JSON o query string (flexibilidad para clientes REST)
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    try {
        // Llamar función de eliminación lógica
        $stmt = $connLogic->prepare('SELECT fun_eliminar_proveedor(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Proveedor eliminado.']);
    } catch (PDOException $e) {
        error_log('proveedores DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el proveedor
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Proveedor no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
