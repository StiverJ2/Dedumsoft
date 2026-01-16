<?php
// Template file; copy to env.php and update values.
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 403, 'MENSAJE' => 'Acceso no autorizado.']);
    exit;
}

const ENV = [
    'PROD' => false,
    'DB_HOST' => 'localhost',
    'DB_PORT' => '5432',
    'DB_NAME' => 'db_dedumsoft',
    'DB_USER' => 'postgres',
    'DB_PASS' => 'change_me',
    'JWT_SECRET' => 'change_me',
    'JWT_EXP_SECONDS' => 3600
];
