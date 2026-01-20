<?php
/**
 * Punto de entrada: Inventario (selector)
 * Redirige al inventario de oro por defecto
 */
require_once __DIR__ . '/../private/bootstrap.php';
header('Location: ' . base_url() . '/inventario_oro.php');
exit;
