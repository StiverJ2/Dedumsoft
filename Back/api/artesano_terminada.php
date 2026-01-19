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
$peso_final = isset($input['peso_final']) ? (float) $input['peso_final'] : 0;
$tiempo_real = isset($input['tiempo_real']) && $input['tiempo_real'] !== '' ? (float) $input['tiempo_real'] : null;
$calidad_id = isset($input['calidad_id']) && $input['calidad_id'] !== '' ? (int) $input['calidad_id'] : null;
$observaciones = $input['observaciones'] ?? null;

if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'orden_id es requerido.']);
    exit;
}

if ($peso_final <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'peso_final debe ser mayor a 0.']);
    exit;
}

try {
    $stmt = $connLogic->prepare(
        'SELECT * FROM fun_registrar_pieza_terminada(:orden_id, :peso_final, :tiempo_real, :calidad_id, :observaciones)'
    );
    $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
    $stmt->bindValue(':peso_final', $peso_final, PDO::PARAM_STR);
    $stmt->bindValue(':tiempo_real', $tiempo_real, $tiempo_real === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':calidad_id', $calidad_id, $calidad_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':observaciones', $observaciones, $observaciones === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result['success']) {
        http_response_code(400);
        echo json_encode(['CODIGO' => 400, 'MENSAJE' => $result['mensaje']]);
        exit;
    }
} catch (PDOException $e) {
    error_log('artesano_terminada POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode([
    'CODIGO' => 201,
    'MENSAJE' => $result['mensaje'],
    'ID' => $result['creacion_id'],
    'COSTO_MATERIALES' => $result['costo_materiales']
]);
