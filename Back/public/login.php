<?php
$error = isset($_GET['error']) ? 'Usuario o contrasena incorrectos.' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dedumsoft</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .box { width: 320px; margin: 100px auto; background: #fff; border: 1px solid #ddd; padding: 20px; }
        label { display: block; margin-bottom: 6px; }
        input { width: 100%; padding: 8px; margin-bottom: 12px; }
        .error { color: #b00020; margin-bottom: 10px; }
        .btn { width: 100%; padding: 8px; background: #222; color: #fff; border: none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Dedumsoft</h2>
        <p class="muted">Iniciar sesion</p>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="login_action.php" method="post">
            <label for="username">Usuario</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Contrasena</label>
            <input type="password" name="password" id="password" required>
            <button type="submit" class="btn">Ingresar</button>
        </form>
    </div>
</body>
</html>
