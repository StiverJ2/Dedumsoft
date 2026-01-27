<?php
/**
 * ============================================================================
 * API REST: ÓRDENES DE PRODUCCIÓN
 * ============================================================================
 * 
 * Endpoint para gestión de órdenes de trabajo/producción.
 * 
 * Métodos soportados:
 * - GET: Listar órdenes con paginación y filtro por estado
 * - POST: Crear orden de producción
 * - PATCH: Actualizar orden (asignar artesano)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 3 (Producción)
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Validar métodos permitidos
if (!in_array($method, ['GET', 'POST', 'PATCH'], true)) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(3); // Menú: Producción

// Determinar rol del usuario
$session_user = get_session_user();
$rolid = (int) ($session_user['rolid'] ?? 0);
$is_operador = ($rolid === 2);

// =============================================================================
// GET: Listar órdenes de producción
// =============================================================================
// Parámetros:
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//   - estado (string): Filtrar por estado (opcional)
//
// Respuesta: { CODIGO: 200, DATOS: [...] }
if ($method === 'GET') {
    // Parsear parámetros de paginación
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $estado = $_GET['estado'] ?? null;
    $estado = ($estado === '') ? null : $estado;

    try {
        // Llamar función PostgreSQL que retorna las órdenes
        $sql = 'SELECT id, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones, observaciones_terminada FROM fun_obtener_ordenes(:offset, :limit, :estado)';
        if ($is_operador) {
            $sql .= " WHERE LOWER(estado) <> 'terminada'";
        }
        $stmt = $connLogic->prepare($sql);
        $stmt->execute([':offset' => $offset, ':limit' => $limit, ':estado' => $estado]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ordenes GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear orden de producción
// =============================================================================
// Body JSON:
//   - producto_id (int, requerido)
//   - cantidad (int, requerido)
//   - artesano_id (int, opcional)
//   - prioridad_id (int, opcional)
//   - observaciones (text, opcional)
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Orden creada.', ID: 123 }
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    $producto_id = isset($input['producto_id']) ? (int) $input['producto_id'] : 0;
    $cantidad = isset($input['cantidad']) ? (int) $input['cantidad'] : 0;
    $artesano_id = isset($input['artesano_id']) ? (int) $input['artesano_id'] : 0;
    $prioridad_id = isset($input['prioridad_id']) ? (int) $input['prioridad_id'] : 0;
    $observaciones = isset($input['observaciones']) ? trim((string) $input['observaciones']) : null;

    if ($producto_id <= 0 || $cantidad <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'producto_id y cantidad son requeridos.']);
        exit;
    }

    if ($artesano_id <= 0) {
        $artesano_id = null;
    }
    if ($prioridad_id <= 0) {
        $prioridad_id = 2;
    }
    if ($observaciones === '') {
        $observaciones = null;
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT fun_crear_orden(:producto_id, :cantidad, :artesano_id, :prioridad_id, :observaciones)'
        );
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        if ($artesano_id === null) {
            $stmt->bindValue(':artesano_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':prioridad_id', $prioridad_id, PDO::PARAM_INT);
        if ($observaciones === null) {
            $stmt->bindValue(':observaciones', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':observaciones', $observaciones, PDO::PARAM_STR);
        }
        $stmt->execute();
        $new_id = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('ordenes POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear la orden.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Orden creada.', 'ID' => (int) $new_id]);
    exit;
}

// =============================================================================
// PATCH: Actualizar orden (asignar artesano)
// =============================================================================
// Body JSON:
//   - id (int): ID de la orden
//   - artesano_id (int): ID del artesano a asignar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Orden actualizada.' }
if ($method === 'PATCH') {
    // Leer body JSON
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Validar campos requeridos
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    $artesano_id = isset($input['artesano_id']) ? (int) $input['artesano_id'] : 0;

    if ($id <= 0 || $artesano_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'id y artesano_id son requeridos.']);
        exit;
    }

    try {
        // Llamar función de actualización (solo actualiza artesano_id)
        $stmt = $connLogic->prepare(
            'SELECT fun_actualizar_orden(:id, NULL, NULL, :artesano_id, NULL, NULL, NULL)'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();

        if (!$result) {
            http_response_code(404);
            echo json_encode(['CODIGO' => 404, 'MENSAJE' => 'Orden no encontrada.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log('ordenes PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al asignar artesano.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Orden actualizada.']);
    exit;
}
