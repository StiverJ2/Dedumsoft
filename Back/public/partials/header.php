<?php
require_once __DIR__ . '/../../connection/guard.php';
require_once __DIR__ . '/../../auth/auth.php';
$user = get_session_user();
$legacy = dedumsoft_is_legacy_browser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedumsoft</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Raleway:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/dedumsoft.css">
    <?php if ($legacy): ?>
        <link rel="stylesheet" href="assets/ie8.css">
        <script src="assets/ie8.js"></script>
    <?php endif; ?>
</head>
<body class="ds-body">
<div class="ds-shell">
