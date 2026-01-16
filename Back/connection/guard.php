<?php
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 403, 'MENSAJE' => 'Acceso no autorizado.']);
    exit;
}

if (!function_exists('dedumsoft_is_legacy_browser')) {
    function dedumsoft_is_legacy_browser(): bool
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return stripos($ua, 'MSIE 8.0') !== false || stripos($ua, 'MSIE 7.0') !== false;
    }
}

if (!defined('DEDUMSOFT_EXCEPTION_HANDLER')) {
    define('DEDUMSOFT_EXCEPTION_HANDLER', true);

    set_exception_handler(function (Throwable $e): void {
        $message = sprintf(
            "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        error_log($message);

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $is_api = strpos($request_uri, '/api/') !== false || strpos($accept, 'application/json') !== false;
            if ($is_api) {
                header('Content-Type: application/json');
                echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
            } else {
                header('Content-Type: text/plain; charset=UTF-8');
                echo 'Error interno del servidor.';
            }
        }
        exit(1);
    });
}
