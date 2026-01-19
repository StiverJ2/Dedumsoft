<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(3);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
    exit;
}

// Validar campos requeridos
$orden_id = isset($input['orden_id']) ? (int) $input['orden_id'] : 0;
$tipo_material = $input['tipo_material'] ?? '';
$material_id = isset($input['material_id']) ? (int) $input['material_id'] : 0;
$cantidad = isset($input['cantidad']) ? (float) $input['cantidad'] : 0;

if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'orden_id es requerido.']);
    exit;
}

if (!in_array($tipo_material, ['oro', 'insumo'])) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'tipo_material debe ser "oro" o "insumo".']);
    exit;
}

if ($material_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'material_id es requerido.']);
    exit;
}

if ($cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'cantidad debe ser mayor a 0.']);
    exit;
}

// Obtener usuario actual
$user = get_session_user();
$usuario_id = $user['id'] ?? null;

try {
    $stmt = $connLogic->prepare(
        'SELECT * FROM fun_registrar_consumo_material(:orden_id, :tipo_material, :material_id, :cantidad, :usuario_id)'
    );
    $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
    $stmt->bindValue(':tipo_material', $tipo_material, PDO::PARAM_STR);
    $stmt->bindValue(':material_id', $material_id, PDO::PARAM_INT);
    $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_STR);
    $stmt->bindValue(':usuario_id', $usuario_id, $usuario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => $result['mensaje']]);
        exit;
    }
} catch (PDOException $e) {
    error_log('artesano_consumo POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 201, 'MENSAJE' => $result['mensaje'], 'ID' => $result['consumo_id']]);
