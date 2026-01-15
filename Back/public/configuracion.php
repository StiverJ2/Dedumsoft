<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../connection/connectionLogic.php';

require_login('login.php');

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/nav.php';
?>
<div class="content">
    <div class="header">
        <h2>Configuracion</h2>
    </div>
    <div class="card">
        <p>Seccion en desarrollo. Ajustes basicos se agregaran aqui.</p>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
