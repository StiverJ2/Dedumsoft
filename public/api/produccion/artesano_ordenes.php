<?php
/**
 * ============================================================================
 * API REST: ÓRDENES DEL ARTESANO
 * ============================================================================
 * 
 * Endpoint para que los artesanos consulten y actualicen sus órdenes asignadas.
 * Forma parte del módulo de producción para operarios.
 * 
 * Métodos soportados:
 * - GET: Obtener órdenes asignadas a un artesano específico
 * - PUT: Actualizar el estado de una orden (iniciar, pausar, etc.)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 3 (Producción)
 * 
 * Flujo de estados de orden:
 * 1. Pendiente -> 2. En Proceso -> 3. Pausada -> 4. Terminada
 * 
 * @package Dedumsoft\API\Artesano
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Validar métodos HTTP permitidos
if (!in_array($method, ['GET', 'PUT'])) {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(3); // Menú: Producción

// =============================================================================
// GET: Obtener órdenes asignadas a un artesano
// =============================================================================
// Parámetros:
//   - artesano_id (int, requerido): ID del artesano
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 50)
//
// Respuesta: { CODIGO: 200, DATOS: [...] }
if ($method === 'GET') {
    // Parsear parámetros
    $artesano_id = isset($_GET['artesano_id']) ? (int) $_GET['artesano_id'] : 0;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

    // Validar campo requerido
    if ($artesano_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'artesano_id requerido.']);
        exit;
    }

    try {
        // Llamar función PostgreSQL que retorna órdenes filtradas por artesano
        $stmt = $connLogic->prepare(
            'SELECT id, producto_id, producto_nombre, cantidad, estado_id, estado, prioridad_id, prioridad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, observaciones FROM fun_obtener_ordenes_artesano(:artesano_id, :offset, :limit)'
        );
        $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('artesano_ordenes GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// PUT: Actualizar estado de orden (artesano)
// =============================================================================
// Permite al artesano cambiar el estado de su orden asignada.
// Valida transiciones válidas de estado.
//
// Body JSON:
//   - id (int, requerido): ID de la orden
//   - estado_id (int, requerido): Nuevo estado de la orden
//
// Estados comunes:
//   1 = Pendiente, 2 = En Proceso, 3 = Pausada, 4 = Terminada
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Estado actualizado.' }
if ($method === 'PUT') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
        exit;
    }

    // Extraer y validar campos requeridos
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    $estado_id = isset($input['estado_id']) ? (int) $input['estado_id'] : 0;

    if ($id <= 0 || $estado_id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'id y estado_id son requeridos.']);
        exit;
    }

    try {
        // Llamar función de actualización de estado
        // La función valida transiciones válidas y permisos
        $stmt = $connLogic->prepare('SELECT success, mensaje, rows_affected FROM fun_actualizar_estado_orden(:orden_id, :estado_id)');
        $stmt->bindValue(':orden_id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $estado_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si la operación fue exitosa
        if (!$result['success']) {
            http_response_code(404);
            echo json_encode(['CODIGO' => 404, 'MENSAJE' => $result['mensaje']]);
            exit;
        }
    } catch (PDOException $e) {
        error_log('artesano_ordenes PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => $result['mensaje']]);
    exit;
}
