<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: RECUPERAR CONTRASEÑA
 * ============================================================================
 * 
 * Formulario para solicitar recuperación de contraseña.
 * El usuario ingresa su email y recibe un link de recuperación.
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';

$csrf = dedumsoft_csrf_token();
$legacy = dedumsoft_is_legacy_browser();
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
                    Si el email está registrado, recibirás instrucciones para recuperar tu contraseña.
                </div>
                <a href="login.php" class="back-link">← Volver al login</a>
            <?php else: ?>
                <h4 class="subtitulo">Ingresa tu email para recuperar tu contraseña</h4>

                <div id="error-msg" class="error-message"></div>

                <form id="forgot-form" onsubmit="return handleSubmit(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email">
                    </div>
                    <button type="submit" id="submit-btn">Enviar instrucciones</button>
                </form>

                <div id="dev-info" class="dev-info" style="display: none;">
                    <strong>⚠️ Solo desarrollo - Link de recuperación:</strong>
                    <a id="dev-link" href="#" target="_blank"></a>
                </div>

                <a href="login.php" class="back-link">← Volver al login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($legacy): ?>
        <script src="assets/js/jquery-3.7.1.min.js"></script>
        <script>
            function handleSubmit(e) {
                e.preventDefault();
                var email = document.getElementById('email').value;
                var btn = document.getElementById('submit-btn');
                var errorDiv = document.getElementById('error-msg');

                btn.disabled = true;
                btn.innerHTML = 'Enviando...';
                errorDiv.style.display = 'none';

                $.ajax({
                    url: DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=request',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ email: email }),
                    success: function (data) {
                        if (data.dev_link) {
                            var devInfo = document.getElementById('dev-info');
                            var devLink = document.getElementById('dev-link');
                            devLink.href = data.dev_link;
                            devLink.innerHTML = data.dev_link;
                            devInfo.style.display = 'block';
                        }
                        window.location.href = 'forgot_password.php?sent=1';
                    },
                    error: function (xhr) {
                        var msg = 'Error al procesar la solicitud';
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            msg = resp.error || msg;
                        } catch (e) { }
                        errorDiv.innerHTML = msg;
                        errorDiv.style.display = 'block';
                        btn.disabled = false;
                        btn.innerHTML = 'Enviar instrucciones';
                    }
                });

                return false;
            }
        </script>
    <?php else: ?>
        <script>
            async function handleSubmit(e) {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const btn = document.getElementById('submit-btn');
                const errorDiv = document.getElementById('error-msg');

                btn.disabled = true;
                btn.textContent = 'Enviando...';
                errorDiv.style.display = 'none';

                try {
                    const response = await fetch(DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=request', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Error al procesar la solicitud');
                    }

                    // En desarrollo, mostrar el link
                    if (data.dev_link) {
                        const devInfo = document.getElementById('dev-info');
                        const devLink = document.getElementById('dev-link');
                        devLink.href = data.dev_link;
                        devLink.textContent = data.dev_link;
                        devInfo.style.display = 'block';

                        // Esperar 3 segundos antes de redirigir
                        setTimeout(() => {
                            window.location.href = 'forgot_password.php?sent=1';
                        }, 3000);
                    } else {
                        window.location.href = 'forgot_password.php?sent=1';
                    }

                } catch (error) {
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Enviar instrucciones';
                }

                return false;
            }
        </script>
    <?php endif; ?>
</body>

</html>