<?php
require_once __DIR__ . '/guard.php';

function validateHttpMethod(string $method): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode([
            'CODIGO' => 405,
            'MENSAJE' => 'Metodo HTTP no permitido.'
        ]);
        return false;
    }
    return true;
}
