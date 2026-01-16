<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';

if (!validateHttpMethod('POST')) {
    header('Location: ../public/login.php');
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
header('Location: ../public/login.php');
exit;
