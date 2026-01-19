<?php
/**
 * ============================================================================
 * PLANTILLA DE CONFIGURACIÓN DE ENTORNO
 * ============================================================================
 * 
 * Este archivo es una plantilla de referencia para la configuración.
 * 
 * INSTRUCCIONES:
 * 1. Copiar este archivo a "env.php" en el mismo directorio
 * 2. Editar los valores según el entorno (desarrollo/producción)
 * 3. NUNCA subir env.php al repositorio (debe estar en .gitignore)
 * 
 * VALORES A CONFIGURAR:
 * - PROD: true en producción, false en desarrollo
 * - DB_*: Credenciales de PostgreSQL
 * - JWT_SECRET: Clave secreta para firmar tokens (mínimo 32 caracteres)
 * - JWT_EXP_SECONDS: Tiempo de expiración de tokens en segundos
 * 
 * SEGURIDAD:
 * - Usar contraseñas fuertes en producción
 * - Generar JWT_SECRET con: openssl rand -base64 32
 * - No usar los valores por defecto en producción
 * 
 * @package Dedumsoft\Env
 * @author  Equipo Dedumsoft
 */

// Protección contra acceso directo
// Template file; copy to env.php and update values.
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['CODIGO' => 403, 'MENSAJE' => 'Acceso no autorizado.']);
    exit;
}

/**
 * Configuración del sistema.
 * 
 * @var array ENV Constantes de configuración del entorno
 */
const ENV = [
    // Modo de ejecución (true = producción, false = desarrollo)
    'PROD' => false,

    // Configuración de base de datos PostgreSQL
    'DB_HOST' => 'localhost',
    'DB_PORT' => '5432',
    'DB_NAME' => 'db_dedumsoft',
    'DB_USER' => 'postgres',
    'DB_PASS' => 'change_me',  // ¡CAMBIAR EN PRODUCCIÓN!

    // Configuración de JWT (JSON Web Tokens)
    'JWT_SECRET' => 'change_me',      // ¡CAMBIAR! Usar: openssl rand -base64 32
    'JWT_EXP_SECONDS' => 3600         // 1 hora de expiración
];
