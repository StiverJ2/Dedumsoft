<?php
require_once __DIR__ . '/../connection/guard.php';
require_once __DIR__ . '/../env/env.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/session.php';

function login_user(PDO $connLogic, string $username, string $password): array
{
    $username = trim($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        return ['CODIGO' => 400, 'MENSAJE' => 'Usuario y contrasena son obligatorios.'];
    }

    try {
        $stmt = $connLogic->prepare(
            'SELECT codigo, mensaje, username, id_usuario, rolid, hash, nombre FROM seguridad.fun_iniciar_sesion(:username)'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('login_service select error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return ['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.'];
    }

    if (!$row || (int)$row['codigo'] !== 200) {
        return ['CODIGO' => 401, 'MENSAJE' => 'Usuario o contrasena incorrectos.'];
    }

    if (!password_verify($password, $row['hash'])) {
        return ['CODIGO' => 401, 'MENSAJE' => 'Usuario o contrasena incorrectos.'];
    }

    $exp = time() + (int)ENV['JWT_EXP_SECONDS'];
    $payload = [
        'sub' => (int)$row['id_usuario'],
        'username' => $row['username'],
        'rolid' => (int)$row['rolid'],
        'exp' => $exp
    ];

    $token = jwt_encode($payload);
    $refresh = bin2hex(random_bytes(32));

    try {
        $stmt = $connLogic->prepare(
            'INSERT INTO seguridad.seg_login (token, refresh_token, refresh_expira, timestamp_expira, ip_origen, user_agent, usuarioid)
             VALUES (:token, :refresh_token, NOW() + INTERVAL \'7 days\', TO_TIMESTAMP(:exp), :ip, :ua, :usuarioid)'
        );
        $stmt->execute([
            ':token' => $token,
            ':refresh_token' => $refresh,
            ':exp' => $exp,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':usuarioid' => (int)$row['id_usuario']
        ]);
    } catch (PDOException $e) {
        error_log('login_service insert error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        return ['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.'];
    }

    dedumsoft_start_session();
    session_regenerate_id(true);
    $_SESSION['jwt'] = $token;
    $_SESSION['user'] = [
        'id_usuario' => (int)$row['id_usuario'],
        'username' => $row['username'],
        'rolid' => (int)$row['rolid'],
        'nombre' => $row['nombre']
    ];
    dedumsoft_rotate_csrf();

    return [
        'CODIGO' => 200,
        'MENSAJE' => 'Inicio de sesion exitoso.',
        'TOKEN' => $token
    ];
}
