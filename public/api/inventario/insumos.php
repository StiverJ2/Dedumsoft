<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE INSUMOS
 * ============================================================================
 * 
 * Endpoint CRUD para gestión del inventario de insumos/materiales.
 * Incluye control de stock mínimo para alertas de reabastecimiento.
 * 
 * Métodos soportados:
 * - GET: Listar insumos (paginado, filtrable por categoría y stock bajo)
 * - POST: Crear nuevo insumo
 * - PATCH: Actualizar insumo existente
 * - DELETE: Eliminar insumo (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización:
 * - GET: Menú 2 (Inventario) o Menú 3 (Producción)
 * - POST/PATCH/DELETE: Menú 2 (Inventario)
 * 
 * Campos principales:
 * - nombre: Nombre del insumo
 * - categoria: Categoría del insumo
 * - cantidad: Cantidad actual en stock
 * - unidad_medida: Unidad (kg, litros, piezas, etc.)
 * - precio_unitario: Precio por unidad
 * - stock_minimo: Cantidad mínima para alertas
 * - proveedor_id: Referencia al proveedor habitual
 * - ubicacion_id: Ubicación en almacén
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
// - GET: Accesible desde Inventario (2) o Producción (3)
// - POST/PATCH/DELETE: Solo desde Inventario (2)
$can_inventario = dedumsoft_user_can_menu(2);
$can_produccion = dedumsoft_user_can_menu(3);
if ($method === 'GET') {
    if (!$can_inventario && !$can_produccion) {
        dedumsoft_forbidden();
    }
} elseif (!$can_inventario) {
    dedumsoft_forbidden();
}

// =============================================================================
// GET: Listar inventario de insumos
// =============================================================================
// Modos de operación:
// 1. Por ID específico: GET ?id=123
// 2. Listado paginado: GET ?offset=0&limit=50&categoria=piedras&stock_bajo=true
//
// Parámetros:
//   - id (int): ID específico del insumo (opcional)
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//   - categoria (string): Filtrar por categoría (opcional)
//   - stock_bajo (bool): Solo mostrar items con stock < stock_minimo (default: false)
//   - activo (bool): Filtrar por estado activo/inactivo (default: true)
//
// Respuesta: { CODIGO: 200, MENSAJE: 'OK', DATOS: [...] }
if ($method === 'GET') {
    // Modo 1: Obtener insumo por ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            // Consultar insumo específico
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
        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
        exit;
    }

    // Modo 2: Listado paginado con filtros
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $categoria = $_GET['categoria'] ?? null;
    $categoria = ($categoria === '') ? null : $categoria;

    // Filtro de stock bajo (cantidad < stock_minimo)
    $stock_bajo = filter_var($_GET['stock_bajo'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($stock_bajo === null) {
        $stock_bajo = false;
    }

    // Filtro de estado activo/inactivo
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        // Llamar función PostgreSQL con todos los filtros
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

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear nuevo insumo
// =============================================================================
// Body JSON:
//   - nombre (string, requerido): Nombre del insumo
//   - categoria (string, requerido): Categoría (piedras, metales, químicos, etc.)
//   - unidad_medida (string, requerido): Unidad de medida (kg, g, L, piezas, etc.)
//   - precio_unitario (float, requerido): Precio por unidad
//   - descripcion (string, opcional): Descripción detallada
//   - cantidad (float, opcional): Cantidad inicial (default: 0)
//   - stock_minimo (float, opcional): Nivel mínimo para alertas (default: 0)
//   - proveedor_id (int, opcional): ID del proveedor habitual
//   - ubicacion_id (int, opcional): ID de ubicación en almacén
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Insumo creado.', DATOS: { id: <new_id> } }
if ($method === 'POST') {
    // Leer y validar JSON del body
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
        // Llamar función de creación en PostgreSQL
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

        // Obtener ID del nuevo registro
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode([
            'CODIGO' => 201,
            'MENSAJE' => 'Insumo creado.',
            'DATOS' => ['id' => (int) $result]
        ]);
    } catch (PDOException $e) {
        error_log('inventario_insumos POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear insumo.']);
    }
    exit;
}

// =============================================================================
// PATCH: Actualizar insumo existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID del insumo a actualizar
//   - nombre (string, opcional): Nuevo nombre
//   - categoria (string, opcional): Nueva categoría
//   - descripcion (string, opcional): Nueva descripción
//   - cantidad (float, opcional): Nueva cantidad
//   - unidad_medida (string, opcional): Nueva unidad de medida
//   - precio_unitario (float, opcional): Nuevo precio
//   - stock_minimo (float, opcional): Nuevo nivel mínimo
//   - proveedor_id (int, opcional): Nuevo proveedor
//   - ubicacion_id (int, opcional): Nueva ubicación
//
// Nota: Solo se actualizan los campos proporcionados (PATCH parcial)
// Respuesta: { CODIGO: 200, MENSAJE: 'Insumo actualizado.' }
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
        error_log('inventario_insumos PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el registro
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Insumo no encontrado.' : 'Error al actualizar.']);
    }
    exit;
}

// =============================================================================
// DELETE: Eliminar insumo (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se marca como inactivo (activo=false)
// Esto preserva la integridad referencial y el historial de movimientos
//
// Entrada (JSON body o query string):
//   - id (int, requerido): ID del insumo a eliminar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Insumo eliminado.' }
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_insumos(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Insumo eliminado.']);
    } catch (PDOException $e) {
        error_log('inventario_insumos DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el registro
        $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Insumo no encontrado.' : 'Error al eliminar.']);
    }
    exit;
}
