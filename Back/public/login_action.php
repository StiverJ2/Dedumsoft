<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../auth/login_service.php';

if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
    header('Location: login.php?error=csrf');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$response = login_user($connLogic, $username, $password);

if ((int)($response['CODIGO'] ?? 500) === 200) {
    header('Location: index.php');
    exit;
}

header('Location: login.php?error=1');
exit;
