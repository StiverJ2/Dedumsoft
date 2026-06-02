<?php
/**
 * ============================================================================
 * API REST: REPORTE DE USUARIOS
 * ============================================================================
 *
 * Endpoint para obtener el listado de usuarios del sistema.
 * Muestra información básica de la tabla seguridad.usuarios.
 *
 * Métodos soportados:
 * - GET: Obtener listado de usuarios
 *
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Menú 4 (Reportes)
 *
 * Parámetros: Ninguno
 *
 * Datos retornados:
 * - id_usuario: ID único del usuario
 * - username: Nombre de usuario para login
 * - nombre: Nombre completo del usuario
 * - rol: Rol asignado (Administrador, Supervisor, etc.)
 * - activo: Estado de activación
 * - created_at: Fecha de creación del usuario
 *
 * @package Dedumsoft\API\Reportes
 * @author  Equipo Dedumsoft
 */

require_once __DIR__ . '/../../../private/api_helper.php';

api_init(4, ['GET']);

try {
    // Llamar función de reporte de usuarios (esquema seguridad)
    $stmt = $connLogic->prepare(
        'SELECT id_usuario, username, nombre, rol, activo, created_at FROM seguridad.fun_reporte_usuarios()'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    api_log_error('reportes_usuarios', 'GET', $e->getMessage() . ' SQLSTATE=' . $e->getCode());
    api_error(500, 'Error interno del servidor.');
}

api_ok($rows);
