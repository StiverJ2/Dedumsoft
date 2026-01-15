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
$estado = $_GET['estado'] ?? null;

try {
    $stmt = $connLogic->prepare(
        'SELECT id, nombre, tipo, marca, modelo, numero_serie, fecha_compra, valor_compra, estado, ultima_mantenimiento, proxima_mantenimiento, ubicacion, fecha_registro FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado)'
    );
    $stmt->execute([':offset' => $offset, ':limit' => $limit, ':estado' => $estado]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
