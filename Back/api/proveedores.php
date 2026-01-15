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
$activo_raw = $_GET['activo'] ?? null;
if ($activo_raw === null) {
    $activo = true;
} else {
    $activo = filter_var($activo_raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

try {
    $stmt = $connLogic->prepare(
        'SELECT id, nombre, tipo, contacto, telefono, email, direccion, activo, fecha_registro FROM fun_obtener_proveedores(:offset, :limit, :tipo, :activo)'
    );
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($tipo === null) {
        $stmt->bindValue(':tipo', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
    }
    if ($activo === null) {
        $stmt->bindValue(':activo', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':activo', $activo, PDO::PARAM_BOOL);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    http_response_code(500);
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    exit;
}

echo json_encode(['CODIGO' => 200, 'DATOS' => $rows]);
