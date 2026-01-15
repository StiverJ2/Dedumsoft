<?php
require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/../env/env.php';

function jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [];
    $segments[] = jwt_base64url_encode(json_encode($header));
    $segments[] = jwt_base64url_encode(json_encode($payload));
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, ENV['JWT_SECRET'], true);
    $segments[] = jwt_base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$header_b64, $payload_b64, $sig_b64] = $parts;
    $signing_input = $header_b64 . '.' . $payload_b64;
    $signature = jwt_base64url_encode(hash_hmac('sha256', $signing_input, ENV['JWT_SECRET'], true));
    if (!hash_equals($signature, $sig_b64)) {
        return null;
    }
    $payload = json_decode(jwt_base64url_decode($payload_b64), true);
    if (!is_array($payload)) {
        return null;
    }
    if (isset($payload['exp']) && time() > (int)$payload['exp']) {
        return null;
    }
    return $payload;
}
