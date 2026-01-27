<?php
/**
 * ============================================================================
 * API REST: ESPECIALIDADES DE ARTESANOS
 * ============================================================================
 * 
 * Endpoint CRUD para catálogo de especialidades de artesanos.
 * 
 * Métodos soportados:
 * - GET: Listar especialidades (paginado)
 * - POST: Crear especialidad
 * - PATCH: Actualizar especialidad
 * - DELETE: Eliminar especialidad (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 8 (Especialidades)
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!require_api_auth()) {
    exit;
}
require_menu_access(8);

// =============================================================================
// GET: Listar especialidades
// =============================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $activo = isset($_GET['activo']) ? ($_GET['activo'] === '1' || $_GET['activo'] === 'true') : true;

    try {
        if ($id > 0) {
            $stmt = $connLogic->prepare(
                'SELECT id, nombre, descripcion, activo FROM cat_especialidad WHERE id = :id'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                http_response_code(404);
                echo json_encode(['CODIGO' => 404, 'MENSAJE' => 'Especialidad no encontrada.']);
                exit;
            }
        } else {
            $stmt = $connLogic->prepare(
                'SELECT id, nombre, descripcion, activo FROM fun_obtener_especialidades(:offset, :limit, :activo)'
            );
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('especialidades GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
    exit;
}

// =============================================================================
// POST: Crear especialidad
// =============================================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim((string) ($input['nombre'] ?? ''));
    $descripcion = trim((string) ($input['descripcion'] ?? ''));
    if ($nombre === '') {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Nombre es requerido.']);
        exit;
    }

    $descripcion = $descripcion === '' ? null : $descripcion;

    try {
        $stmt = $connLogic->prepare('SELECT fun_crear_especialidad(:nombre, :descripcion) AS id');
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) ($stmt->fetchColumn() ?: 0);
    } catch (PDOException $e) {
        error_log('especialidades POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        if ($e->getCode() == 23505) {
            http_response_code(409);
            echo json_encode(['CODIGO' => 409, 'MENSAJE' => 'La especialidad ya existe.']);
        } else {
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        }
        exit;
    }

    if ($id <= 0) {
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error al crear especialidad.']);
        exit;
    }

    http_response_code(201);
    echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Especialidad creada.', 'ID' => $id]);
    exit;
}

// =============================================================================
// PATCH: Actualizar especialidad
// =============================================================================
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    $nombre = isset($input['nombre']) ? trim((string) $input['nombre']) : null;
    $descripcion = isset($input['descripcion']) ? trim((string) $input['descripcion']) : null;
    $activo = null;
    if (array_key_exists('activo', $input)) {
        $activo = filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    $nombre = ($nombre !== null && $nombre !== '') ? $nombre : null;
    $descripcion = ($descripcion !== null && $descripcion !== '') ? $descripcion : null;

    try {
        $stmt = $connLogic->prepare('SELECT fun_actualizar_especialidad(:id, :nombre, :descripcion, :activo)');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, $nombre === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('especialidades PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode([
            'CODIGO' => $code,
            'MENSAJE' => $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.'
        ]);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Especialidad actualizada.']);
    exit;
}

// =============================================================================
// DELETE: Eliminar especialidad (soft-delete)
// =============================================================================
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare('SELECT fun_eliminar_especialidad(:id)');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('especialidades DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
        http_response_code($code);
        echo json_encode([
            'CODIGO' => $code,
            'MENSAJE' => $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.'
        ]);
        exit;
    }

    echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Especialidad eliminada.']);
    exit;
}

http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
