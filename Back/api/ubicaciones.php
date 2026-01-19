<?php
/**
 * ============================================================================
 * API REST: UBICACIONES DE ALMACÉN
 * ============================================================================
 * 
 * Endpoint CRUD para gestión de ubicaciones físicas del inventario.
 * Las ubicaciones pertenecen a áreas y se usan para localizar items.
 * 
 * Métodos soportados:
 * - GET: Listar ubicaciones (paginado, filtrable por área)
 * - POST: Crear nueva ubicación
 * - PUT: Actualizar ubicación existente
 * - DELETE: Eliminar ubicación (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 2 (Inventario)
 * 
 * Campos principales:
 * - nombre: Nombre de la ubicación (ej: "Estante A1", "Bóveda Principal")
 * - descripcion: Descripción detallada
 * - area_id: Área a la que pertenece (default: 1)
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(2); // Menú: Inventario

// =============================================================================
// GET: Listar ubicaciones
// =============================================================================
// Parámetros:
//   - offset (int): Inicio de paginación (default: 0)
//   - limit (int): Cantidad de registros (default: 100)
//   - area_id (int): Filtrar por área (opcional)
//   - activo (bool): Filtrar por estado activo (default: true)
//
// Respuesta: { CODIGO: 200, DATOS: [...] }
if ($method === 'GET') {
    // Parsear parámetros de paginación y filtros
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $area_id = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? (int) $_GET['area_id'] : null;
    $activo = isset($_GET['activo']) ? ($_GET['activo'] === '1' || $_GET['activo'] === 'true') : true;

    try {
        // Llamar función PostgreSQL con JOIN a tabla de áreas
        $stmt = $connLogic->prepare(
            'SELECT id, nombre, descripcion, area_id, area_nombre, activo, created_at FROM fun_obtener_ubicaciones(:offset, :limit, :area_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':area_id', $area_id, $area_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('ubicaciones GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear nueva ubicación
// =============================================================================
// Body JSON:
//   - nombre (string, requerido): Nombre de la ubicación
//   - descripcion (string, opcional): Descripción detallada
//   - area_id (int, opcional): ID del área (default: 1)
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Ubicación creada.', id: <new_id> }
if ($method === 'POST') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    $nombre = $input['nombre'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    $area_id = isset($input['area_id']) ? (int) $input['area_id'] : 1;

    // Validar campo requerido
    if (!$nombre) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Nombre es requerido.']);
        exit;
    }

    try {
        // Llamar función de creación
        $stmt = $connLogic->prepare('SELECT fun_crear_ubicacion(:nombre, :descripcion, :area_id)');
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':area_id' => $area_id]);
        $result = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('ubicaciones POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Ubicación creada.', 'id' => $result]);
    exit;
}

// =============================================================================
// PUT: Actualizar ubicación existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID de la ubicación a actualizar
//   - nombre (string, opcional): Nuevo nombre
//   - descripcion (string, opcional): Nueva descripción
//   - area_id (int, opcional): Nueva área
//   - activo (bool, opcional): Estado de activación
//
// Nota: Solo se actualizan los campos proporcionados
// Respuesta: { CODIGO: 200, MENSAJE: 'Ubicación actualizada.' }
if ($method === 'PUT') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    // Extraer campos opcionales
    $nombre = $input['nombre'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    $area_id = isset($input['area_id']) ? (int) $input['area_id'] : null;
    $activo = isset($input['activo']) ? (bool) $input['activo'] : null;

    try {
        // Llamar función de actualización
        $stmt = $connLogic->prepare('SELECT fun_actualizar_ubicacion(:id, :nombre, :descripcion, :area_id, :activo)');
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':area_id' => $area_id,
            ':activo' => $activo
        ]);
    } catch (PDOException $e) {
        error_log('ubicaciones PUT error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Ubicación actualizada.']);
    exit;
}

// =============================================================================
// DELETE: Eliminar ubicación (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se marca como inactivo
// Las referencias existentes en inventario se mantienen
//
// Entrada (JSON body o query string):
//   - id (int, requerido): ID de la ubicación a eliminar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Ubicación eliminada.' }
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
        $stmt = $connLogic->prepare('SELECT fun_eliminar_ubicacion(:id)');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Ubicación eliminada.']);
    } catch (PDOException $e) {
        error_log('ubicaciones DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar si el error es porque no se encontró la ubicación
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode(['CODIGO' => $code, 'MENSAJE' => $code === 404 ? 'Ubicación no encontrada.' : 'Error al eliminar.']);
    }
    exit;
}

// Método no soportado (fallback)
http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
