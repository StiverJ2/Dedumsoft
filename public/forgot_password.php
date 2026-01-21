<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: RECUPERAR CONTRASEÑA
 * ============================================================================
 * 
 * Formulario para solicitar recuperación de contraseña.
 * El usuario ingresa su usuario y email y recibe un link de recuperación.
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/SecurityLogger.php';
require_once PRIVATE_PATH . '/Mail/Mailer.php';
require_once PRIVATE_PATH . '/Auth/RateLimiter.php';

function dedumsoft_guess_base_url(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($path === DIRECTORY_SEPARATOR || $path === '.') {
        $path = '';
    }
    return rtrim($protocol . '://' . $host . $path, '/');
}

function dedumsoft_request_password_reset(PDO $conn, string $username, string $email): array
{
    $rate = check_rate_limit();
    if (!$rate['allowed']) {
        dedumsoft_log_rate_limited(0);
        $retry_minutes = (int) ceil(($rate['retry_after'] ?? 0) / 60);
        return [
            'success' => false,
            'error' => 'Demasiadas solicitudes. Intenta en ' . $retry_minutes . ' minutos.'
        ];
    }

    $username = trim($username);
    $email = trim($email);

    if ($username === '') {
        return ['success' => false, 'error' => 'Usuario invalido'];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Email inválido'];
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip) {
        $ip = explode(',', $ip)[0];
    }
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    $stmt = $conn->prepare(
        'SELECT codigo, mensaje, token, usuario_id, nombre 
         FROM seguridad.fun_crear_reset_token(:username, :email, :ip::inet, :ua)'
    );
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':ip' => $ip,
        ':ua' => $user_agent
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $detail = 'Username/email mismatch';
    if ($result['token']) {
        $detail = 'Token generated';
    } elseif ((int) ($result['codigo'] ?? 0) === 429) {
        $detail = 'User rate limited';
    }
    dedumsoft_security_log('PASSWORD_RESET_REQUEST', $username, $detail . ' | email=' . $email);

    $response = [
        'success' => true,
        'message' => 'Si el usuario y el email existen en nuestro sistema, recibiras instrucciones para recuperar tu contrasena.'
    ];

    if ($result['token']) {
        $siteUrl = base_url();
        if ($siteUrl === '') {
            $siteUrl = dedumsoft_guess_base_url();
        }
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

        if (!(ENV['PROD'] ?? false)) {
            $response['dev_email_sent'] = $emailResult['success'];
            $response['dev_email_error'] = $emailResult['error'] ?? null;
            $response['dev_link'] = $reset_link;
        }
    }

    return $response;
}

$csrf = dedumsoft_csrf_token();
$legacy = dedumsoft_is_legacy_browser();
$error_message = '';
$posted_username = '';
$posted_email = '';
$dev_link = '';

if (!empty($_SESSION['dev_reset_link'])) {
    $dev_link = $_SESSION['dev_reset_link'];
    unset($_SESSION['dev_reset_link']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_username = trim($_POST['username'] ?? '');
    $posted_email = trim($_POST['email'] ?? '');

    if (!dedumsoft_validate_csrf($_POST['csrf_token'] ?? null)) {
        dedumsoft_log_csrf_invalid($posted_username ?: null);
        $error_message = 'Error de seguridad. Recarga la pagina e intenta de nuevo.';
    } else {
        $result = dedumsoft_request_password_reset($connLogic, $posted_username, $posted_email);
        if (($result['success'] ?? false) === true) {
            if (!empty($result['dev_link'])) {
                $_SESSION['dev_reset_link'] = $result['dev_link'];
            }
            header('Location: forgot_password.php?sent=1');
            exit;
        }
        $error_message = $result['error'] ?? 'Error al procesar la solicitud';
    }
}

$success = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Dedumsoft</title>
    <base href="<?php echo rtrim(base_url(), '/'); ?>/">
    <script>
        var DEDUMSOFT_BASE_URL = '<?php echo rtrim(base_url(), '/'); ?>';
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Raleway:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <?php if ($legacy): ?>
        <link rel="stylesheet" href="assets/css/ie8.css">
        <script src="assets/js/ie8.js"></script>
    <?php endif; ?>
    <style>
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #b8860b;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            display: none;
        }

        .dev-info {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.85rem;
            word-break: break-all;
        }

        .dev-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body class="ds-login">
    <div class="login-background">
        <div class="login-decoration decoration-1"></div>
        <div class="login-decoration decoration-2"></div>

        <div class="login-container">
            <div class="logo-wrapper">
                <div class="diamond-logo"></div>
                <h2>Joyas Van</h2>
            </div>
            <p class="slogan">Recuperar Contraseña</p>

            <?php if ($success): ?>
                <div class="success-message">
                    Si el usuario y el email estan registrados, recibiras instrucciones para recuperar tu contrasena.
                </div>
                <?php if ($dev_link !== ''): ?>
                    <div class="dev-info">
                        <strong>⚠️ Solo desarrollo - Link de recuperación:</strong>
                        <a href="<?php echo htmlspecialchars($dev_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                            <?php echo htmlspecialchars($dev_link, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <a href="login.php" class="back-link">← Volver al login</a>
            <?php else: ?>
                <h4 class="subtitulo">Ingresa tu usuario y email para recuperar tu contrasena</h4>

                <div id="error-msg" class="error-message" <?php echo $error_message !== '' ? 'style="display: block;"' : ''; ?>>
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <form id="forgot-form" method="post" action="forgot_password.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <div class="input-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" required autocomplete="username"
                            value="<?php echo htmlspecialchars($posted_username, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            value="<?php echo htmlspecialchars($posted_email, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <button type="submit" id="submit-btn" class="btn-login">Enviar instrucciones</button>
                </form>

                <a href="login.php" class="back-link">← Volver al login</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
