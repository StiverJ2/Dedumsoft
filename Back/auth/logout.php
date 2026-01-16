<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/session.php';

if (!validateHttpMethod('POST')) {
    header('Location: ../public/login.php');
    exit;
}

if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
    session_destroy();
    header('Location: ../public/login.php?error=csrf');
    exit;
}

$token = $_SESSION['jwt'] ?? null;

if ($token) {
    try {
        $stmt = $connLogic->prepare('UPDATE seguridad.seg_login SET estado_token = FALSE WHERE token = :token');
        $stmt->execute([':token' => $token]);
    } catch (PDOException $e) {
        error_log('logout error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    }
}

session_destroy();
header('Location: ../public/login.php');
exit;
