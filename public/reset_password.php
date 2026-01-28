<?php
/**
 * ============================================================================
 * PÁGINA PÚBLICA: RESTABLECER CONTRASEÑA
 * ============================================================================
 * 
 * Formulario para establecer una nueva contraseña usando el token
 * recibido por email.
 * 
 * Parámetros GET:
 * - token: Token de recuperación (64 caracteres hex)
 * 
 * @package Dedumsoft\Public
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/SessionManager.php';

$csrf = dedumsoft_csrf_token();
$legacy = dedumsoft_is_legacy_browser();
$token = $_GET['token'] ?? '';
$success = isset($_GET['success']);
$error = '';

// Validar formato del token
if (!$success && (strlen($token) !== 64 || !ctype_xdigit($token))) {
    $error = 'Token inválido o expirado.';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Dedumsoft</title>
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
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
            padding-left: 1rem;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }

        .password-requirements li.valid {
            color: #28a745;
        }

        .password-requirements li.invalid {
            color: #dc3545;
        }

        .user-info {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
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

            <?php if ($success): ?>
                <p class="slogan">¡Contraseña Actualizada!</p>
                <div class="success-message">
                    Tu contraseña ha sido cambiada exitosamente.<br>
                    Ya puedes iniciar sesión con tu nueva contraseña.
                </div>
                <a href="login.php" class="back-link">Ir al login →</a>

            <?php elseif ($error): ?>
                <p class="slogan">Error</p>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <a href="forgot_password.php" class="back-link">← Solicitar nuevo link</a>

            <?php else: ?>
                <p class="slogan">Nueva Contraseña</p>

                <div id="user-info" class="user-info" style="display: none;">
                    Cambiando contraseña para: <strong id="user-name"></strong>
                </div>

                <div id="error-msg" class="error-message" style="display: none;"></div>

                <form id="reset-form" onsubmit="return handleSubmit(event)">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="input-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                            oninput="checkPassword()">
                        <ul class="password-requirements">
                            <li id="req-length">Mínimo 8 caracteres</li>
                            <li id="req-upper">Al menos una mayúscula</li>
                            <li id="req-lower">Al menos una minúscula</li>
                            <li id="req-number">Al menos un número</li>
                        </ul>
                    </div>

                    <div class="input-group">
                        <label for="password_confirm">Confirmar Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" required
                            autocomplete="new-password" oninput="checkPassword()">
                    </div>

                    <button type="submit" id="submit-btn" disabled>Cambiar Contraseña</button>
                </form>

                <a href="login.php" class="back-link">← Cancelar</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$success && !$error): ?>
        <?php if ($legacy): ?>
            <script src="assets/js/jquery-3.7.1.min.js"></script>
            <script>
                var token = '<?php echo htmlspecialchars($token); ?>';

                // Validar token al cargar
                $(document).ready(function () {
                    $.ajax({
                        url: DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=validate',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ token: token }),
                        success: function (data) {
                            if (data && data.CODIGO === 200) {
                                var info = data.DATOS || {};
                                $('#user-name').text(info.nombre || info.username || '');
                                $('#user-info').show();
                            }
                        },
                        error: function (xhr) {
                            var msg = 'Token inválido o expirado.';
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                msg = resp.MENSAJE || msg;
                            } catch (e) { }
                            $('#error-msg').text(msg).show();
                            $('#reset-form').hide();
                        }
                    });
                });

                function checkPassword() {
                    var password = $('#password').val();
                    var confirm = $('#password_confirm').val();

                    var hasLength = password.length >= 8;
                    var hasUpper = /[A-Z]/.test(password);
                    var hasLower = /[a-z]/.test(password);
                    var hasNumber = /[0-9]/.test(password);
                    var matches = password === confirm && password !== '';

                    $('#req-length').removeClass('valid invalid').addClass(hasLength ? 'valid' : 'invalid');
                    $('#req-upper').removeClass('valid invalid').addClass(hasUpper ? 'valid' : 'invalid');
                    $('#req-lower').removeClass('valid invalid').addClass(hasLower ? 'valid' : 'invalid');
                    $('#req-number').removeClass('valid invalid').addClass(hasNumber ? 'valid' : 'invalid');

                    var valid = hasLength && hasUpper && hasLower && hasNumber && matches;
                    $('#submit-btn').prop('disabled', !valid);
                }

                function handleSubmit(e) {
                    e.preventDefault();
                    var btn = $('#submit-btn');
                    var errorDiv = $('#error-msg');

                    btn.prop('disabled', true).text('Cambiando...');
                    errorDiv.hide();

                    $.ajax({
                        url: DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=reset',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            token: token,
                            password: $('#password').val(),
                            password_confirm: $('#password_confirm').val()
                        }),
                        success: function (data) {
                            if (data && data.CODIGO === 200) {
                                window.location.href = 'reset_password.php?success=1';
                            } else {
                                var msg = (data && data.MENSAJE) ? data.MENSAJE : 'Error al cambiar la contraseña';
                                errorDiv.text(msg).show();
                                btn.prop('disabled', false).text('Cambiar Contraseña');
                            }
                        },
                        error: function (xhr) {
                            var msg = 'Error al cambiar la contraseña';
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                msg = resp.MENSAJE || msg;
                            } catch (e) { }
                            errorDiv.text(msg).show();
                            btn.prop('disabled', false).text('Cambiar Contraseña');
                        }
                    });

                    return false;
                }
            </script>
        <?php else: ?>
            <script>
                const token = '<?php echo htmlspecialchars($token); ?>';

                // Validar token al cargar
                document.addEventListener('DOMContentLoaded', async () => {
                    try {
                        const response = await fetch(DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=validate', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ token })
                        });

                        const data = await response.json();

                        if (response.ok && data.CODIGO === 200) {
                            const info = data.DATOS || {};
                            document.getElementById('user-name').textContent = info.nombre || info.username || '';
                            document.getElementById('user-info').style.display = 'block';
                        } else {
                            throw new Error(data.MENSAJE || 'Token inválido o expirado.');
                        }
                    } catch (error) {
                        document.getElementById('error-msg').textContent = error.message;
                        document.getElementById('error-msg').style.display = 'block';
                        document.getElementById('reset-form').style.display = 'none';
                    }
                });

                const checkPassword = () => {
                    const password = document.getElementById('password').value;
                    const confirm = document.getElementById('password_confirm').value;

                    const checks = {
                        'req-length': password.length >= 8,
                        'req-upper': /[A-Z]/.test(password),
                        'req-lower': /[a-z]/.test(password),
                        'req-number': /[0-9]/.test(password)
                    };

                    let allValid = true;
                    for (const [id, valid] of Object.entries(checks)) {
                        const el = document.getElementById(id);
                        el.className = valid ? 'valid' : 'invalid';
                        if (!valid) allValid = false;
                    }

                    const matches = password === confirm && password !== '';
                    document.getElementById('submit-btn').disabled = !(allValid && matches);
                };

                const handleSubmit = async (e) => {
                    e.preventDefault();
                    const btn = document.getElementById('submit-btn');
                    const errorDiv = document.getElementById('error-msg');

                    btn.disabled = true;
                    btn.textContent = 'Cambiando...';
                    errorDiv.style.display = 'none';

                    try {
                        const response = await fetch(DEDUMSOFT_BASE_URL + '/api/auth/password_reset.php?action=reset', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                token,
                                password: document.getElementById('password').value,
                                password_confirm: document.getElementById('password_confirm').value
                            })
                        });

                        const data = await response.json();

                        if (!response.ok || data.CODIGO !== 200) {
                            throw new Error(data.MENSAJE || 'Error al cambiar la contraseña');
                        }

                        window.location.href = 'reset_password.php?success=1';

                    } catch (error) {
                        errorDiv.textContent = error.message;
                        errorDiv.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = 'Cambiar Contraseña';
                    }

                    return false;
                };

                window.checkPassword = checkPassword;
                window.handleSubmit = handleSubmit;
            </script>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>
