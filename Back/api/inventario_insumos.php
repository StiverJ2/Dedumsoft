<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

if (!validateHttpMethod('GET')) {
    exit;
}

if (!require_api_auth()) {
    exit;
}

$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
$categoria = $_GET['categoria'] ?? null;
$categoria = ($categoria === '') ? null : $categoria;
$stock_bajo = filter_var($_GET['stock_bajo'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($stock_bajo === null) {
    $stock_bajo = false;
}

try {
    $stmt = $connLogic->prepare(
        'SELECT id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, ubicacion_nombre, fecha_registro, proveedor_nombre FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo)'
    );
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($categoria === null) {
        $stmt->bindValue(':categoria', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
    }
    $stmt->bindValue(':stock_bajo', $stock_bajo, PDO::PARAM_BOOL);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario_insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);