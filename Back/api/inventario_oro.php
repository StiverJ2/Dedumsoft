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

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$tipo = $_GET['tipo'] ?? null;
$tipo = ($tipo === '') ? null : $tipo;

try {
    $stmt = $connLogic->prepare(
        'SELECT id, tipo_oro, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, lote, fecha_registro, valor_total, proveedor_nombre FROM fun_obtener_inventario_oro(:offset, :limit, :tipo)'
    );
    $stmt->execute([':offset' => $offset, ':limit' => $limit, ':tipo' => $tipo]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('inventario_oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
