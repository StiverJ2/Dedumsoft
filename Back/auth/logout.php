<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';

if (!validateHttpMethod('POST')) {
    exit;
}

session_start();
$token = $_SESSION['jwt'] ?? null;

if ($token) {
    try {
        $stmt = $connLogic->prepare('UPDATE seguridad.seg_login SET estado_token = FALSE WHERE token = :token');
        $stmt->execute([':token' => $token]);
    } catch (PDOException $e) {
        // ignore
    }
}

session_destroy();
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'Sesion cerrada.']);
