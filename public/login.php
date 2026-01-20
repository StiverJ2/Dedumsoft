<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: FORMULARIO DE LOGIN
 * ============================================================================
 * 
 * Página de inicio de sesión para el sistema Dedumsoft.
 * Incluye protección CSRF y soporte para navegadores legacy (IE8).
 * 
 * Características:
 * - Token CSRF para protección contra ataques de formulario
 * - Detección automática de navegador legacy
 * - Banner de alerta para navegadores no soportados
 * - Manejo de errores de autenticación
 * 
 * Flujo:
 * 1. Usuario ingresa credenciales
 * 2. Formulario envía POST a login_action.php
 * 3. Si error, redirige aquí con ?error=tipo
 * 4. Si éxito, redirige a home según rol
 * 
 * Parámetros GET:
 * - error: Código de error ('csrf', '1' = credenciales inválidas)
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';

// Procesar código de error si viene de un intento fallido
$error_key = $_GET['error'] ?? '';
$error = '';
if ($error_key === 'csrf') {
    $error = 'Sesion invalida. Intenta de nuevo.';
} elseif ($error_key !== '') {
    $error = 'Usuario o contrasena incorrectos.';
}

// Generar token CSRF para el formulario
$csrf = dedumsoft_csrf_token();

// Detectar modo legacy para cargar CSS/JS alternativos
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
            <a href="forgot_password.php" class="forgot-password">Olvidaste tu contrasena?</a>
        </div>
    </div>
</body>

</html>