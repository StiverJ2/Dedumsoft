<?php
/**
 * ============================================================================
 * API REST: CATALOGOS MAESTROS
 * ============================================================================
 *
 * CRUD generico para catalogos del sistema.
 * Solo usa funciones fun_* cuando la consulta lo amerita (JOINs/WHEREs complejos).
 *
 * Autenticacion: Requerida
 * Autorizacion: Menu 7 (Configuracion)
 *
 * Parametros:
 * - catalog (string, requerido)
 * - GET: id (opcional), offset, limit, activo
 *
 * Respuestas:
 * { CODIGO: 200, MENSAJE: 'OK', DATOS: [...] }
 */

require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/CatalogConfig.php';

header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!require_api_auth()) {
    exit;
}
require_menu_access(7);

$catalogs = dedumsoft_catalogs_config();
$catalog = $_GET['catalog'] ?? '';

if ($catalog === '' || !isset($catalogs[$catalog])) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Catalogo invalido.']);
    exit;
}

$cfg = $catalogs[$catalog];
$table = $cfg['table'];
$columns = $cfg['columns'];
$fields = $cfg['fields'];

function dedu_normalize_field($value, $type)
{
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $value = trim($value);
    }
    if ($value === '') {
        return null;
    }
    if ($type === 'int') {
        return (int) $value;
    }
    if ($type === 'decimal') {
        return (float) $value;
    }
    if ($type === 'bool') {
        return (bool) $value;
    }
    return $value;
}

function dedu_parse_activo($value, $default)
{
    if ($value === null || $value === '') {
        return $default;
    }
    $value = strtolower(trim((string) $value));
    if ($value === 'all') {
        return null;
    }
    if ($value === '1' || $value === 'true' || $value === 't' || $value === 'si' || $value === 'yes') {
        return true;
    }
    if ($value === '0' || $value === 'false' || $value === 'f' || $value === 'no') {
        return false;
    }
    return $default;
}

if ($method === 'GET') {
    $id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;
    $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 200;
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $where = [];
    $params = [];
    if ($id !== null && $id > 0) {
        $where[] = 'id = :id';
        $params[':id'] = $id;
    } else if (!empty($cfg['filter_activo'])) {
        $activo = dedu_parse_activo($_GET['activo'] ?? null, true);
        if ($activo !== null) {
            $where[] = 'activo = :activo';
            $params[':activo'] = $activo;
        }
    }

    $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . $cfg['order_by'];

    if ($id === null) {
        $sql .= ' OFFSET :offset LIMIT :limit';
    }

    try {
        $stmt = $connLogic->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === ':activo') {
                $stmt->bindValue($key, $val, PDO::PARAM_BOOL);
            } else if ($key === ':id') {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        if ($id === null) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
    } catch (PDOException $e) {
        error_log('catalogos maestros GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON invalidos.']);
        exit;
    }

    $insertFields = [];
    $placeholders = [];
    $params = [];
    $requiredMissing = [];

    foreach ($fields as $field) {
        $name = $field['name'];
        $required = !empty($field['required']);
        $type = $field['data'] ?? 'text';
        $value = array_key_exists($name, $input) ? dedu_normalize_field($input[$name], $type) : null;

        if ($required && ($value === null || $value === '')) {
            $requiredMissing[] = $name;
        }

        if (array_key_exists($name, $input)) {
            $insertFields[] = $name;
            $placeholders[] = ':' . $name;
            $params[':' . $name] = $value;
        }
    }

    if (!empty($requiredMissing)) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Campos requeridos: ' . implode(', ', $requiredMissing) . '.']);
        exit;
    }

    if (empty($insertFields)) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Sin datos para guardar.']);
        exit;
    }

    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $placeholders) . ') RETURNING id';

    try {
        $stmt = $connLogic->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $id = $stmt->fetchColumn();
        http_response_code(201);
        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Registro creado.', 'ID' => (int) $id]);
    } catch (PDOException $e) {
        error_log('catalogos maestros POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON invalidos.']);
        exit;
    }

    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    $updates = [];
    $params = [':id' => $id];

    foreach ($fields as $field) {
        $name = $field['name'];
        $type = $field['data'] ?? 'text';
        if (array_key_exists($name, $input)) {
            $updates[] = $name . ' = :' . $name;
            $params[':' . $name] = dedu_normalize_field($input[$name], $type);
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'No hay campos para actualizar.']);
        exit;
    }

    $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ' WHERE id = :id';

    try {
        $stmt = $connLogic->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro actualizado.']);
    } catch (PDOException $e) {
        error_log('catalogos maestros PATCH error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON invalidos.']);
        exit;
    }

    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        $stmt = $connLogic->prepare('UPDATE ' . $table . ' SET activo = false WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro eliminado.']);
    } catch (PDOException $e) {
        error_log('catalogos maestros DELETE error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Metodo no permitido.']);
