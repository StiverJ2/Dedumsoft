<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../env/env.php';

try {
    $connLogic = new PDO(
        "pgsql:host=" . ENV['DB_HOST'] . ";port=" . ENV['DB_PORT'] . ";dbname=" . ENV['DB_NAME'],
        ENV['DB_USER'],
        ENV['DB_PASS']
    );
    $connLogic->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connLogic->exec("SET search_path TO joyeria, seguridad, public");
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error de conexion a la base de datos']);
    error_log('DB Connection Error (logic): ' . $e->getMessage());
    exit;
}
