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
    // =========================================================================
    // Modo de ejecución
    // =========================================================================
    'PROD' => false,  // true = producción, false = desarrollo

    // =========================================================================
    // Base de datos PostgreSQL
    // =========================================================================
    'DB_HOST' => 'localhost',
    'DB_PORT' => '5432',
    'DB_NAME' => 'db_dedumsoft',
    'DB_USER' => 'postgres',
    'DB_PASS' => 'change_me',  // ¡CAMBIAR EN PRODUCCIÓN!

    // =========================================================================
    // JWT (JSON Web Tokens)
    // =========================================================================
    'JWT_SECRET' => 'change_me',      // ¡CAMBIAR! Usar: openssl rand -base64 32
    'JWT_EXP_SECONDS' => 3600,        // 1 hora de expiración

    // =========================================================================
    // Configuración de Email (PHPMailer)
    // =========================================================================
    // Servidores comunes:
    //   Gmail:      smtp.gmail.com (puerto 587, TLS)
    //   Outlook:    smtp.office365.com (puerto 587, TLS)
    //   SendGrid:   smtp.sendgrid.net (puerto 587, TLS)
    //   Amazon SES: email-smtp.us-east-1.amazonaws.com
    //
    // NOTA GMAIL: Usar "App Passwords" de 16 caracteres, NO la contraseña normal
    //             https://support.google.com/accounts/answer/185833
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => 587,
    'MAIL_USERNAME' => 'tu_email@gmail.com',
    'MAIL_PASSWORD' => 'xxxx_xxxx_xxxx_xxxx',  // App Password de 16 chars
    'MAIL_ENCRYPTION' => 'tls',                 // 'tls' (587) o 'ssl' (465)
    'MAIL_FROM_ADDRESS' => 'noreply@dedumsoft.com',
    'MAIL_FROM_NAME' => 'Dedumsoft Joyería',

    // =========================================================================
    // Configuración del Sitio
    // =========================================================================
    'SITE_NAME' => 'Dedumsoft Joyería',
    'SITE_URL' => 'http://localhost/dedumsoft'  // Sin trailing slash
];
