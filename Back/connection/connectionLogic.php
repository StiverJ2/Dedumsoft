<?php
/**
 * ============================================================================
 * CONEXIÓN A BASE DE DATOS POSTGRESQL
 * ============================================================================
 * 
 * Establece la conexión PDO a la base de datos PostgreSQL.
 * La variable $connLogic queda disponible globalmente para su uso
 * en toda la aplicación.
 * 
 * Configuración:
 * - Los parámetros de conexión se leen de env/env.php
 * - Usa PDO para abstracción y seguridad
 * - Configura search_path para los esquemas de la aplicación
 * 
 * Esquemas utilizados:
 * - joyeria: Tablas de negocio (productos, órdenes, inventario)
 * - seguridad: Tablas de autenticación y autorización
 * - public: Esquema por defecto de PostgreSQL
 * 
 * @package Dedumsoft\Connection
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../env/env.php';

try {
    // Crear conexión PDO a PostgreSQL
    $connLogic = new PDO(
        "pgsql:host=" . ENV['DB_HOST'] . ";port=" . ENV['DB_PORT'] . ";dbname=" . ENV['DB_NAME'],
        ENV['DB_USER'],
        ENV['DB_PASS']
    );

    // Configurar modo de errores (lanzar excepciones)
    $connLogic->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Establecer search_path para no tener que calificar tablas
    // Ejemplo: SELECT * FROM productos (en vez de joyeria.productos)
    $connLogic->exec("SET search_path TO joyeria, seguridad, public");

} catch (Exception $e) {
    // Error de conexión - responder con 500 y registrar en logs
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error de conexion a la base de datos']);
    error_log('DB Connection Error (logic): ' . $e->getMessage());
    exit;
}
