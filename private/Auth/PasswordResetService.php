<?php
/**
 * ============================================================================
 * PASSWORD RESET SERVICE
 * ============================================================================
 *
 * Encapsula la recuperacion de contrasena: validacion, llamadas PL/pgSQL,
 * logging de seguridad y envio de email.
 *
 * @package Dedumsoft\Auth
 */

require_once __DIR__ . '/../Http/SecurityLogger.php';
require_once __DIR__ . '/../Mail/Mailer.php';
require_once __DIR__ . '/RateLimiter.php';

class PasswordResetService
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Solicita un token de recuperacion y envia el email si corresponde.
     *
     * @param string $username
     * @param string $email
     * @param string $siteUrl
     * @param bool $includeDevInfo
     * @return array<string, mixed>
     */
    public function request(string $username, string $email, string $siteUrl, bool $includeDevInfo = false): array
    {
        $rate = check_rate_limit();
        if (!$rate['allowed']) {
            dedumsoft_log_rate_limited($rate['count'] ?? 0);
            $retry_minutes = (int) ceil(($rate['retry_after'] ?? 0) / 60);
            return [
                'success' => false,
                'code' => 429,
                'message' => 'Demasiadas solicitudes. Intenta en ' . $retry_minutes . ' minutos.',
            ];
        }

        $username = trim($username);
        $email = trim($email);

        if ($username === '') {
            return ['success' => false, 'code' => 400, 'message' => 'Usuario invalido.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'code' => 400, 'message' => 'Email inválido.'];
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip) {
            $ip = explode(',', $ip)[0];
        }
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        $stmt = $this->pdo->prepare(
            'SELECT codigo, mensaje, token, usuario_id, nombre
             FROM seguridad.fun_crear_reset_token(:username, :email, :ip::inet, :ua)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':ip' => $ip,
            ':ua' => $user_agent,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $detail = 'Username/email mismatch';
        if (!empty($result['token'])) {
            $detail = 'Token generated';
        } elseif ((int) ($result['codigo'] ?? 0) === 429) {
            $detail = 'User rate limited';
        }
        dedumsoft_security_log('PASSWORD_RESET_REQUEST', $username, $detail . ' | email=' . $email);

        $response = [
            'success' => true,
            'code' => 200,
            'message' => 'Si el usuario y el email existen en nuestro sistema, recibiras instrucciones para recuperar tu contrasena.',
        ];

        if (!empty($result['token'])) {
            $reset_link = rtrim($siteUrl, '/') . '/public/reset_password.php?token=' . $result['token'];
            $emailResult = send_password_reset_email(
                $email,
                $result['nombre'] ?? 'Usuario',
                $result['token'],
                $reset_link
            );

            if ($emailResult['success']) {
                dedumsoft_security_log('PASSWORD_RESET_EMAIL_SENT', $email, 'Email sent successfully');
            } else {
                dedumsoft_security_log('PASSWORD_RESET_EMAIL_FAILED', $email, $emailResult['error'] ?? 'Unknown error');
                error_log('[DEDUMSOFT] Error enviando email de reset a ' . $email . ': ' . ($emailResult['error'] ?? 'Unknown'));
            }

            if ($includeDevInfo) {
                $response['data'] = [
                    'email_sent' => $emailResult['success'],
                    'email_error' => $emailResult['error'] ?? null,
                    'link' => $reset_link,
                ];
            }
        }

        return $response;
    }

    /**
     * Valida un token sin consumirlo.
     *
     * @param string $token
     * @return array<string, mixed>
     */
    public function validateToken(string $token): array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) !== 64) {
            return ['success' => false, 'code' => 400, 'message' => 'Token inválido.'];
        }

        $stmt = $this->pdo->prepare(
            'SELECT codigo, mensaje, usuario_id, username, nombre
             FROM seguridad.fun_validar_reset_token(:token)'
        );
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ((int) ($result['codigo'] ?? 500) !== 200) {
            return ['success' => false, 'code' => 400, 'message' => $result['mensaje'] ?? 'Token inválido.'];
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => 'OK',
            'data' => [
                'username' => $result['username'],
                'nombre' => $result['nombre'],
            ],
        ];
    }

    /**
     * Cambia la contrasena usando un token valido.
     *
     * @param string $token
     * @param string $password
     * @param string $passwordConfirm
     * @return array<string, mixed>
     */
    public function reset(string $token, string $password, string $passwordConfirm): array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) !== 64) {
            return ['success' => false, 'code' => 400, 'message' => 'Token inválido.'];
        }

        if ($password === '' || strlen($password) < 8) {
            return ['success' => false, 'code' => 400, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if ($password !== $passwordConfirm) {
            return ['success' => false, 'code' => 400, 'message' => 'Las contraseñas no coinciden.'];
        }

        $strength_error = $this->validatePasswordStrength($password);
        if ($strength_error) {
            return ['success' => false, 'code' => 400, 'message' => $strength_error];
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare(
            'SELECT codigo, mensaje FROM seguridad.fun_reset_password(:token, :hash)'
        );
        $stmt->execute([':token' => $token, ':hash' => $hash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ((int) ($result['codigo'] ?? 500) !== 200) {
            dedumsoft_security_log('PASSWORD_RESET_FAILED', null, $result['mensaje'] ?? 'Unknown error');
            return ['success' => false, 'code' => 400, 'message' => $result['mensaje'] ?? 'Token inválido.'];
        }

        dedumsoft_security_log('PASSWORD_RESET_SUCCESS', null, 'Password changed via reset token');

        return [
            'success' => true,
            'code' => 200,
            'message' => $result['mensaje'] ?? 'Contraseña actualizada exitosamente. Ya puedes iniciar sesión.',
        ];
    }

    private function validatePasswordStrength(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe tener al menos una letra mayúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'La contraseña debe tener al menos una letra minúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe tener al menos un número';
        }
        return null;
    }
}
