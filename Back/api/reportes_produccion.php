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
require_menu_access(4);

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');

try {
    $stmt = $connLogic->prepare(
        'SELECT id, producto, cantidad, artesano, estado, fecha_inicio, fecha_fin_real FROM fun_reporte_produccion(:desde, :hasta)'
    );
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('reportes_produccion error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
