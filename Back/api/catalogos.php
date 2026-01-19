<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!require_api_auth()) {
    exit;
}
require_menu_access(7);

// Determinar qué catálogo se está solicitando
$catalog = $_GET['catalog'] ?? 'areas';

// Validar catálogo
$valid_catalogs = ['areas', 'tipos_oro', 'tipos_proveedor'];
if (!in_array($catalog, $valid_catalogs)) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Catálogo inválido.']);
    exit;
}

if ($method === 'GET') {
    try {
        $stmt = $connLogic->prepare("SELECT * FROM {$catalog} WHERE activo = true ORDER BY orden, nombre");
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['CODIGO' => 200, 'DATOS' => $datos]);
    } catch (PDOException $e) {
        error_log("catalogo GET error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['codigo']) || !isset($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Código y nombre son requeridos.']);
        exit;
    }

    try {
        // Preparar campos según el catálogo
        $fields = ['codigo', 'nombre', 'descripcion', 'orden'];
        $values = [':codigo', ':nombre', ':descripcion', ':orden'];
        $params = [
            ':codigo' => $input['codigo'],
            ':nombre' => $input['nombre'],
            ':descripcion' => $input['descripcion'] ?? null,
            ':orden' => $input['orden'] ?? 0
        ];

        // Campos específicos para tipos_oro
        if ($catalog === 'tipos_oro') {
            $fields[] = 'kilates';
            $fields[] = 'pureza_porcentaje';
            $values[] = ':kilates';
            $values[] = ':pureza_porcentaje';
            $params[':kilates'] = $input['kilates'] ?? null;
            $params[':pureza_porcentaje'] = $input['pureza_porcentaje'] ?? null;
        }

        $sql = "INSERT INTO {$catalog} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ") 
                RETURNING id";

        $stmt = $connLogic->prepare($sql);
        $stmt->execute($params);
        $id = $stmt->fetchColumn();

        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Registro creado.', 'ID' => $id]);
    } catch (PDOException $e) {
        error_log("catalogo POST error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        if ($e->getCode() == 23505) { // Duplicate key
            http_response_code(409);
            echo json_encode(['CODIGO' => 409, 'MENSAJE' => 'El código ya existe.']);
        } else {
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        }
    }
    exit;
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        $updates = [];
        $params = [':id' => $input['id']];

        if (isset($input['codigo'])) {
            $updates[] = 'codigo = :codigo';
            $params[':codigo'] = $input['codigo'];
        }
        if (isset($input['nombre'])) {
            $updates[] = 'nombre = :nombre';
            $params[':nombre'] = $input['nombre'];
        }
        if (isset($input['descripcion'])) {
            $updates[] = 'descripcion = :descripcion';
            $params[':descripcion'] = $input['descripcion'];
        }
        if (isset($input['orden'])) {
            $updates[] = 'orden = :orden';
            $params[':orden'] = $input['orden'];
        }

        // Campos específicos para tipos_oro
        if ($catalog === 'tipos_oro') {
            if (isset($input['kilates'])) {
                $updates[] = 'kilates = :kilates';
                $params[':kilates'] = $input['kilates'];
            }
            if (isset($input['pureza_porcentaje'])) {
                $updates[] = 'pureza_porcentaje = :pureza_porcentaje';
                $params[':pureza_porcentaje'] = $input['pureza_porcentaje'];
            }
        }

        $updates[] = 'fecha_modificacion = CURRENT_TIMESTAMP';

        $sql = "UPDATE {$catalog} SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $connLogic->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro actualizado.']);
    } catch (PDOException $e) {
        error_log("catalogo PUT error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        // Soft delete
        $stmt = $connLogic->prepare("UPDATE {$catalog} SET activo = false WHERE id = :id");
        $stmt->execute([':id' => $input['id']]);

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro desactivado.']);
    } catch (PDOException $e) {
        error_log("catalogo DELETE error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
