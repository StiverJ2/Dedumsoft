<?php
/**
 * ============================================================================
 * API REST: INVENTARIO DE MAQUINARIA
 * ============================================================================
 * 
 * Endpoint CRUD para gestión del inventario de maquinaria y equipos.
 * Incluye control de mantenimiento preventivo y estado operativo.
 * 
 * Métodos soportados:
 * - GET: Listar maquinaria (paginado, filtrable por estado)
 * - POST: Crear nuevo equipo
 * - PATCH: Actualizar equipo existente
 * - DELETE: Eliminar equipo (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 2 (Inventario)
 * 
 * Campos principales:
 * - sku: Código único de inventario
 * - nombre: Nombre del equipo
 * - tipo_maquinaria_id: Tipo de maquinaria (del catálogo)
 * - marca/modelo: Identificación del fabricante
 * - estado_id: Estado operativo (operativo, en mantenimiento, fuera de servicio)
 * - ultima_mantenimiento: Fecha del último mantenimiento
 * - proxima_mantenimiento: Fecha programada del próximo mantenimiento
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

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(2); // Menú: Inventario

// =============================================================================
// GET: Listar inventario de maquinaria
// =============================================================================
// Modos de operación:
// 1. Por ID específico: GET ?id=123
// 2. Listado paginado: GET ?offset=0&limit=50&estado_id=1&activo=true
//
// Parámetros:
//   - id (int): ID específico del equipo (opcional)
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//   - estado_id (int): Filtrar por estado operativo (opcional)
//   - activo (bool): Filtrar por estado activo/inactivo (default: true)
//
// Respuesta: { CODIGO: 200, MENSAJE: 'OK', DATOS: [...] }
if ($method === 'GET') {
    // Modo 1: Obtener equipo por ID específico
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $id = (int) $_GET['id'];
        try {
            // Consultar equipo específico con todos sus datos relacionados
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
        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
        exit;
    }

    // Modo 2: Listado paginado con filtros
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $estado_id = isset($_GET['estado_id']) && $_GET['estado_id'] !== '' ? (int) $_GET['estado_id'] : null;

    // Filtro de estado activo/inactivo
    $activo = filter_var($_GET['activo'] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activo === null) {
        $activo = true;
    }

    try {
        // Llamar función PostgreSQL con filtros
        // Incluye JOINs automáticos para tipo, estado y ubicación
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

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear nuevo equipo de maquinaria
// =============================================================================
// Body JSON:
//   - nombre (string, requerido): Nombre del equipo
//   - sku (string, requerido): Código único de inventario
//   - tipo_maquinaria_id (int, requerido): ID del tipo de maquinaria
//   - marca (string, opcional): Marca del fabricante
//   - modelo (string, opcional): Modelo del equipo
//   - fecha_compra (date, opcional): Fecha de adquisición
//   - valor_compra (float, opcional): Valor de compra
//   - estado_id (int, opcional): Estado operativo (default: 1 = Operativo)
//   - ubicacion_id (int, opcional): Ubicación del equipo
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Maquinaria creada.', DATOS: { id: <new_id> } }
if ($method === 'POST') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos (SKU es obligatorio para control de activos)
    $required = ['nombre', 'sku', 'tipo_maquinaria_id'];
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

        // Obtener ID del nuevo registro
        $result = $stmt->fetchColumn();

        http_response_code(201);
        echo json_encode([
            'CODIGO' => 201,
            'MENSAJE' => 'Maquinaria creada.',
            'DATOS' => ['id' => (int) $result]
        ]);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear maquinaria.']);
    }
    exit;
}

// =============================================================================
// PATCH: Actualizar maquinaria existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID del equipo a actualizar
//   - nombre (string, opcional): Nuevo nombre
//   - sku (string, opcional): Nuevo código SKU
//   - tipo_maquinaria_id (int, opcional): Nuevo tipo
//   - marca (string, opcional): Nueva marca
//   - modelo (string, opcional): Nuevo modelo
//   - fecha_compra (date, opcional): Nueva fecha de compra
//   - valor_compra (float, opcional): Nuevo valor
//   - estado_id (int, opcional): Nuevo estado operativo
//   - ultima_mantenimiento (date, opcional): Fecha del último mantenimiento
//   - proxima_mantenimiento (date, opcional): Fecha del próximo mantenimiento
//   - ubicacion_id (int, opcional): Nueva ubicación
//   - activo (bool, opcional): Estado de activación
//
// Nota: Solo se actualizan los campos proporcionados (PATCH parcial)
// Respuesta: { CODIGO: 200, MENSAJE: 'Maquinaria actualizada.' }
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
        // Soporta actualización de fechas de mantenimiento para control preventivo
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
        error_log('inventario_maquinaria PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el equipo
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Maquinaria no encontrada.' : 'Error al actualizar.']);
    }
    exit;
}

// =============================================================================
// DELETE: Eliminar maquinaria (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se marca como inactivo (activo=false)
// Esto preserva el historial de mantenimientos y la trazabilidad del activo
//
// Entrada (JSON body o query string):
//   - id (int, requerido): ID del equipo a eliminar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Maquinaria eliminada.' }
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_inventario_maquinaria(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Maquinaria eliminada.']);
    } catch (PDOException $e) {
        error_log('inventario_maquinaria DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró el equipo
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Maquinaria no encontrada.' : 'Error al eliminar.']);
    }
    exit;
}
