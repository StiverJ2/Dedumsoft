<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

if (!validateHttpMethod('POST')) {
    exit;
}

if (!require_api_auth()) {
    exit;
}
require_menu_access(2);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Datos JSON inválidos.']);
    exit;
}

$tipo = strtolower(trim($input['tipo_inventario'] ?? ''));
$item_id = $input['item_id'] ?? null;
$cantidad = $input['cantidad'] ?? null;
$motivo = $input['motivo'] ?? null;
$referencia = $input['referencia'] ?? null;
$fecha = $input['fecha'] ?? null;

if ($tipo === '' || $item_id === null || $item_id === '') {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo e item_id requeridos.']);
    exit;
}

if ($cantidad === null || !is_numeric($cantidad) || (float)$cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Cantidad invalida.']);
    exit;
}

if ($fecha === '') {
    $fecha = null;
}

$user = get_session_user();
$usuario_id = $user['id_usuario'] ?? null;

try {
    switch ($tipo) {
        case 'oro':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_oro(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        case 'insumo':
        case 'insumos':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_insumo(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        case 'maquinaria':
            $stmt = $connLogic->prepare(
                'SELECT fun_registrar_compra_maquinaria(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)'
            );
            break;
        default:
            http_response_code(400);
            echo json_encode(['CODIGO' => 400, 'MENSAJE' => 'Tipo de inventario no soportado.']);
            exit;
    }

    $stmt->bindValue(':item_id', (int) $item_id, PDO::PARAM_INT);
    $stmt->bindValue(':cantidad', $cantidad);
    $stmt->bindValue(':motivo', $motivo, isset($motivo) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':referencia', $referencia, isset($referencia) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':usuario_id', $usuario_id, isset($usuario_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':fecha', $fecha, isset($fecha) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();

    $mov_id = $stmt->fetchColumn();

    http_response_code(201);
    echo json_encode(['CODIGO' => 201, 'MENSAJE' => 'Compra registrada.', 'ID' => (int) $mov_id]);
} catch (PDOException $e) {
    error_log('compras POST error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
    http_response_code($code);
    echo json_encode([
        'CODIGO' => $code,
        'MENSAJE' => $code === 404 ? 'Item no encontrado.' : 'Error al registrar compra.'
    ]);
}
