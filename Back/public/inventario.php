<?php
define('DEDUMSOFT_APP', true);

require_once __DIR__ . '/../auth/auth.php';

require_login('login.php');

header('Location: inventario_insumos.php');
exit;