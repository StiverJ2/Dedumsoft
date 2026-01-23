<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE ORO
 * ============================================================================
 * 
 * Endpoint CRUD para gestión del inventario de oro.
 * Permite crear, leer, actualizar y eliminar (soft-delete) registros.
 * 
 * Métodos soportados:
 * - GET: Listar inventario (paginado, filtrable por tipo y estado)
 * - POST: Crear nuevo registro de oro
 * - PATCH: Actualizar registro existente
 * - DELETE: Eliminar registro (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 2 (Inventario)
 * 
 * Campos principales:
 * - tipo_oro_id: Referencia al catálogo de tipos de oro
 * - peso_gramos: Peso en gramos
 * - precio_gramo: Precio por gramo
 * - pureza: Porcentaje de pureza
 * - proveedor_id: Referencia al proveedor
 * - ubicacion: Ubicación física del oro
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

// Cargar dependencias
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Validar métodos HTTP permitidos
if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(2); // Menú: Inventario

// =============================================================================
// GET: Listar inventario de oro
// =============================================================================
// Modos de operación:
// 1. Por ID específico: GET ?id=123
// 2. Listado paginado: GET ?offset=0&limit=50&tipo_id=1&activo=true
//
// Parámetros:
//   - id (int): ID específico del registro (opcional)
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//   - tipo_id (int): Filtrar por tipo de oro (opcional)
//   - activo (bool): Filtrar por estado activo/inactivo (default: true)
//
// Respuesta: { CODIGO: 200, DATOS: [...] }
if ($method === 'GET') {
    // Modo 1: Obtener registro por ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            // Consultar usando función PostgreSQL con filtro WHERE en resultado
            $stmt = $connLogic->prepare(
                'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, fecha_registro, valor_total, proveedor_nombre, activo FROM fun_obtener_inventario_oro(0, 1000, NULL, NULL) WHERE id = :id'
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

    // Modo 2: Listado paginado con filtros
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $tipo_id = isset($_GET['tipo_id']) && $_GET['tipo_id'] !== '' ? (int) $_GET['tipo_id'] : null;

    // Parsear parámetro booleano 'activo'
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        // Llamar función PostgreSQL con todos los filtros
        $stmt = $connLogic->prepare(
            'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, fecha_registro, valor_total, proveedor_nombre, activo FROM fun_obtener_inventario_oro(:offset, :limit, :tipo_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $tipo_id, $tipo_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
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

// =============================================================================
// POST: Crear nuevo registro de oro
// =============================================================================
// Body JSON:
//   - tipo_oro_id (int, requerido): ID del tipo de oro
//   - peso_gramos (float, requerido): Peso en gramos
//   - precio_gramo (float, requerido): Precio por gramo
//   - proveedor_id (int, opcional): ID del proveedor
//   - fecha_ingreso (date, opcional): Fecha de ingreso (default: hoy)
//   - ubicacion (string, opcional): Ubicación física
//   - pureza (float, opcional): Porcentaje de pureza
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Registro creado.', ID: <new_id> }
if ($method === 'POST') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $required = ['tipo_oro_id', 'peso_gramos', 'precio_gramo'];
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
            'SELECT fun_crear_inventario_oro(:tipo_oro_id, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza)'
        );
        $stmt->bindValue(':tipo_oro_id', (int) $input['tipo_oro_id'], PDO::PARAM_INT);
        $stmt->bindValue(':peso_gramos', $input['peso_gramos']);
        $stmt->bindValue(':precio_gramo', $input['precio_gramo']);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_ingreso', $input['fecha_ingreso'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':ubicacion', $input['ubicacion'] ?? null, isset($input['ubicacion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':pureza', $input['pureza'] ?? null);
        $stmt->execute();

        // Obtener ID del nuevo registro
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

// =============================================================================
// PATCH: Actualizar registro de oro existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID del registro a actualizar
//   - tipo_oro_id (int, opcional): Nuevo tipo de oro
//   - peso_gramos (float, opcional): Nuevo peso
//   - precio_gramo (float, opcional): Nuevo precio
//   - proveedor_id (int, opcional): Nuevo proveedor
//   - fecha_ingreso (date, opcional): Nueva fecha de ingreso
//   - ubicacion (string, opcional): Nueva ubicación
//   - pureza (float, opcional): Nueva pureza
//
// Nota: Solo se actualizan los campos proporcionados (PATCH parcial)
// Respuesta: { CODIGO: 200, MENSAJE: 'Registro actualizado.' }
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
        // La función maneja internamente los valores NULL para campos no modificados
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_inventario_oro(:id, :tipo_oro_id, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza)'
        );
        $stmt->bindValue(':id', (int) $input['id'], PDO::PARAM_INT);
        $stmt->bindValue(':tipo_oro_id', isset($input['tipo_oro_id']) ? (int) $input['tipo_oro_id'] : null, isset($input['tipo_oro_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':peso_gramos', $input['peso_gramos'] ?? null);
        $stmt->bindValue(':precio_gramo', $input['precio_gramo'] ?? null);
        $stmt->bindValue(':proveedor_id', $input['proveedor_id'] ?? null, isset($input['proveedor_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha_ingreso', $input['fecha_ingreso'] ?? null, isset($input['fecha_ingreso']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ubicacion', $input['ubicacion'] ?? null, isset($input['ubicacion']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':pureza', $input['pureza'] ?? null);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro actualizado.']);
    } catch (PDOException $e) {
        error_log('inventario_oro PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el registro
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Registro no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// =============================================================================
// DELETE: Eliminar registro de oro (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se marca como inactivo (activo=false)
//
// Entrada (JSON body o query string):
//   - id (int, requerido): ID del registro a eliminar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Registro eliminado.' }
if ($method === 'DELETE') {
    // Obtener ID de body JSON o query string
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID requerido.']);
        exit;
    }

    try {
        // Llamar función de eliminación lógica
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_oro(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro eliminado.']);
    } catch (PDOException $e) {
        error_log('inventario_oro DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el registro
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Registro no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
