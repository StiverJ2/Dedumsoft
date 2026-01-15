<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../connection/connectionLogic.php';
require_once __DIR__ . '/../connection/httpMethodValidator.php';
require_once __DIR__ . '/login_service.php';

header('Content-Type: application/json');

if (!validateHttpMethod('POST')) {
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$response = login_user($connLogic, $username, $password);
http_response_code((int)($response['CODIGO'] ?? 500));
echo json_encode($response);
