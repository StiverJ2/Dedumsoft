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
$activo = isset($_GET['activo']) ? (bool)$_GET['activo'] : true;

try {
    $stmt = $connLogic->prepare('SELECT * FROM fun_obtener_proveedores(:offset, :limit, :tipo, :activo)');
    $stmt->execute([':offset' => $offset, ':limit' => $limit, ':tipo' => $tipo, ':activo' => $activo]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
