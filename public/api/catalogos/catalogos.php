<?php
/**
 * ============================================================================
 * API REST: CATÁLOGOS DEL SISTEMA
 * ============================================================================
 * 
 * Endpoint CRUD genérico para gestión de catálogos.
 * Maneja múltiples tablas de catálogo con un solo endpoint.
 * 
 * Métodos soportados:
 * - GET: Listar registros del catálogo
 * - POST: Crear nuevo registro en catálogo
 * - PATCH: Actualizar registro existente
 * - DELETE: Eliminar registro (soft-delete)
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 7 (Configuración)
 * 
 * Catálogos soportados:
 * - areas: Áreas de la empresa (almacén, taller, oficina, etc.)
 * - tipos_oro: Tipos de oro (24k, 18k, 14k, etc.) con kilates y pureza
 * - tipos_proveedor: Clasificación de proveedores
 * 
 * Campos comunes:
 * - codigo: Código único del registro
 * - nombre: Nombre descriptivo
 * - descripcion: Descripción opcional
 * - orden: Orden de visualización
 * 
 * Campos específicos (tipos_oro):
 * - kilates: Kilates del oro (10, 14, 18, 24)
 * - pureza_porcentaje: Porcentaje de pureza (41.7%, 58.5%, etc.)
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

// Verificar autenticación y autorización
if (!require_api_auth()) {
    exit;
}
require_menu_access(7); // Menú: Configuración

// =============================================================================
// Determinar catálogo a consultar
// =============================================================================
// Parámetro obligatorio: catalog=[areas|tipos_oro|tipos_proveedor]
$catalog = $_GET['catalog'] ?? 'areas';

// Lista blanca de catálogos válidos (previene SQL injection)
$valid_catalogs = ['areas', 'tipos_oro', 'tipos_proveedor'];
if (!in_array($catalog, $valid_catalogs)) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Catálogo inválido.']);
    exit;
}

// =============================================================================
// GET: Listar registros del catálogo
// =============================================================================
// Parámetros:
//   - catalog (string, requerido): Nombre del catálogo
//
// Nota: Solo retorna registros activos, ordenados por 'orden' y 'nombre'
// Respuesta: { CODIGO: 200, DATOS: [...] }
if ($method === 'GET') {
    try {
        // Consulta directa a la tabla del catálogo
        // NOTA: $catalog está validado contra lista blanca, seguro para interpolación
        $stmt = $connLogic->prepare("SELECT id, nombre, descripcion, orden, activo FROM {$catalog} WHERE activo = true ORDER BY orden, nombre");
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

// =============================================================================
// POST: Crear nuevo registro en catálogo
// =============================================================================
// Body JSON:
//   - codigo (string, requerido): Código único
//   - nombre (string, requerido): Nombre descriptivo
//   - descripcion (string, opcional): Descripción
//   - orden (int, opcional): Orden de visualización (default: 0)
//   - kilates (float, solo tipos_oro): Kilates del oro
//   - pureza_porcentaje (float, solo tipos_oro): Porcentaje de pureza
//
// Respuesta: { CODIGO: 201, MENSAJE: 'Registro creado.', ID: <new_id> }
if ($method === 'POST') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campos requeridos
    if (!isset($input['codigo']) || !isset($input['nombre'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Código y nombre son requeridos.']);
        exit;
    }

    try {
        // Preparar campos base (comunes a todos los catálogos)
        $fields = ['codigo', 'nombre', 'descripcion', 'orden'];
        $values = [':codigo', ':nombre', ':descripcion', ':orden'];
        $params = [
            ':codigo' => $input['codigo'],
            ':nombre' => $input['nombre'],
            ':descripcion' => $input['descripcion'] ?? null,
            ':orden' => $input['orden'] ?? 0
        ];

        // Campos específicos para catálogo tipos_oro
        if ($catalog === 'tipos_oro') {
            $fields[] = 'kilates';
            $fields[] = 'pureza_porcentaje';
            $values[] = ':kilates';
            $values[] = ':pureza_porcentaje';
            $params[':kilates'] = $input['kilates'] ?? null;
            $params[':pureza_porcentaje'] = $input['pureza_porcentaje'] ?? null;
        }

        // Construir consulta dinámica con RETURNING para obtener el ID
        $sql = "INSERT INTO {$catalog} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ") 
                RETURNING id";

        $stmt = $connLogic->prepare($sql);
        $stmt->execute($params);
        $id = $stmt->fetchColumn();

        echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Registro creado.', 'ID' => $id]);
    } catch (PDOException $e) {
        error_log("catalogo POST error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());

        // Detectar error de clave duplicada (código ya existe)
        if ($e->getCode() == 23505) {
            http_response_code(409);
            echo json_encode(['CODIGO' => 409, 'MENSAJE' => 'El código ya existe.']);
        } else {
            http_response_code(500);
            echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
        }
    }
    exit;
}

// =============================================================================
// PATCH: Actualizar registro existente
// =============================================================================
// Body JSON:
//   - id (int, requerido): ID del registro a actualizar
//   - codigo (string, opcional): Nuevo código
//   - nombre (string, opcional): Nuevo nombre
//   - descripcion (string, opcional): Nueva descripción
//   - orden (int, opcional): Nuevo orden
//   - kilates (float, solo tipos_oro): Nuevos kilates
//   - pureza_porcentaje (float, solo tipos_oro): Nueva pureza
//
// Nota: Solo se actualizan los campos proporcionados
// Respuesta: { CODIGO: 200, MENSAJE: 'Registro actualizado.' }
if ($method === 'PATCH') {
    // Leer y validar JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campo requerido
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        // Construir UPDATE dinámico solo con campos proporcionados
        $updates = [];
        $params = [':id' => $input['id']];

        // Campos comunes
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

        // Campos específicos para catálogo tipos_oro
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

        // Actualizar timestamp de modificación
        $updates[] = 'fecha_modificacion = CURRENT_TIMESTAMP';

        // Ejecutar UPDATE
        $sql = "UPDATE {$catalog} SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $connLogic->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Registro actualizado.']);
    } catch (PDOException $e) {
        error_log("catalogo PATCH error ({$catalog}): " . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

// =============================================================================
// DELETE: Eliminar registro (soft-delete)
// =============================================================================
// El registro no se elimina físicamente, solo se desactiva (activo=false)
//
// Body JSON:
//   - id (int, requerido): ID del registro a desactivar
//
// Respuesta: { CODIGO: 200, MENSAJE: 'Registro desactivado.' }
if ($method === 'DELETE') {
    // Leer JSON del body
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campo requerido
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'ID es requerido.']);
        exit;
    }

    try {
        // Soft-delete: marcar como inactivo
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

// Método no soportado (fallback)
http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
