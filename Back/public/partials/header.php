<?php
require_once __DIR__ . '/../../connection/guard.php';
require_once __DIR__ . '/../../auth/auth.php';
$user = get_session_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedumsoft</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; color: #222; }
        .layout { display: flex; min-height: 100vh; }
        nav { width: 220px; background: #222; color: #eee; padding: 16px; }
        nav a { color: #eee; text-decoration: none; display: block; padding: 8px 0; }
        nav a:hover { text-decoration: underline; }
        .content { flex: 1; padding: 20px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card { border: 1px solid #ddd; padding: 12px; margin-bottom: 12px; background: #fafafa; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .muted { color: #666; font-size: 12px; }
        .btn { padding: 6px 10px; border: 1px solid #333; background: #333; color: #fff; cursor: pointer; }
        .btn-link { background: transparent; color: #333; border: none; cursor: pointer; padding: 0; }
    </style>
</head>
<body>
<div class="layout">
