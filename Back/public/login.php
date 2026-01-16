<?php
define('DEDUMSOFT_APP', true);
require_once __DIR__ . '/../auth/session.php';

$error_key = $_GET['error'] ?? '';
$error = '';
if ($error_key === 'csrf') {
    $error = 'Sesion invalida. Intenta de nuevo.';
} elseif ($error_key !== '') {
    $error = 'Usuario o contrasena incorrectos.';
}
$csrf = dedumsoft_csrf_token();
$legacy = dedumsoft_is_legacy_browser();
$legacy_ua = dedumsoft_is_legacy_ua();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dedumsoft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Raleway:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/login.css">
    <?php if ($legacy): ?>
        <link rel="stylesheet" href="assets/ie8.css">
        <script src="assets/ie8.js"></script>
    <?php endif; ?>
</head>
<body class="ds-login">
    <?php if (!$legacy && $legacy_ua): ?>
        <div class="ds-legacy-alert">
            Si el sitio no se ve bien en tu navegador, haz clic para
            <a href="mode.php?mode=legacy">cambiar a modo legacy</a>.
        </div>
    <?php endif; ?>
    <div class="login-background">
        <div class="login-decoration decoration-1"></div>
        <div class="login-decoration decoration-2"></div>

        <div class="login-container">
            <div class="logo-wrapper">
                <div class="diamond-logo"></div>
                <h2>Joyas Van</h2>
            </div>
            <p class="slogan">Lujo en cada detalle</p>
            <h4 class="subtitulo">Ingresa tu Usuario y Contrasena</h4>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php else: ?>
                <p class="error"></p>
            <?php endif; ?>
            <form action="login_action.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <div class="input-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="input-group">
                    <label for="password">Contrasena</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login">Ingresar</button>
            </form>
            <a href="#" class="forgot-password">Olvidaste tu contrasena?</a>
        </div>
    </div>
</body>
</html>
