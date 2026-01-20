<?php
/**
 * ============================================================================
 * SERVICIO DE ENVÍO DE EMAILS
 * ============================================================================
 * 
 * Módulo de envío de correos electrónicos usando PHPMailer.
 * Configuración centralizada desde env/env.php.
 * 
 * Uso:
 *   require_once __DIR__ . '/connection/mailer.php';
 *   $result = send_email('dest@email.com', 'Asunto', '<h1>HTML</h1>');
 *   if ($result['success']) { ... }
 * 
 * @package Dedumsoft\Mail
 * @author  Equipo Dedumsoft
 */

// Verificar carga via bootstrap
if (!defined('DEDUMSOFT_APP')) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Cargar autoloader de Composer (ubicado en raíz del proyecto)
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Enviar un correo electrónico.
 * 
 * @param string      $to          Dirección de destino
 * @param string      $subject     Asunto del correo
 * @param string      $htmlBody    Cuerpo HTML del correo
 * @param string|null $altBody     Cuerpo alternativo en texto plano (opcional)
 * @param array       $attachments Array de rutas de archivos adjuntos (opcional)
 * 
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function send_email(
    string $to,
    string $subject,
    string $htmlBody,
    ?string $altBody = null,
    array $attachments = []
): array {
    // Verificar configuración
    if (empty(ENV['MAIL_HOST']) || empty(ENV['MAIL_USERNAME'])) {
        return [
            'success' => false,
            'message' => 'Configuración de email no establecida',
            'error' => 'MAIL_HOST o MAIL_USERNAME no configurados en env.php'
        ];
    }

    $mail = new PHPMailer(true);

    try {
        // =====================================================================
        // Configuración del servidor SMTP
        // =====================================================================
        
        // Habilitar debug solo en desarrollo
        $mail->SMTPDebug = ENV['PROD'] ? SMTP::DEBUG_OFF : SMTP::DEBUG_OFF;
        
        $mail->isSMTP();
        $mail->Host       = ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = ENV['MAIL_USERNAME'];
        $mail->Password   = ENV['MAIL_PASSWORD'];
        $mail->Port       = ENV['MAIL_PORT'];

        // Encriptación TLS o SSL
        if (ENV['MAIL_ENCRYPTION'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (ENV['MAIL_ENCRYPTION'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        // Timeout de conexión (segundos)
        $mail->Timeout = 30;

        // =====================================================================
        // Remitente y destinatario
        // =====================================================================
        $mail->setFrom(
            ENV['MAIL_FROM_ADDRESS'],
            ENV['MAIL_FROM_NAME']
        );
        
        $mail->addAddress($to);

        // =====================================================================
        // Contenido del email
        // =====================================================================
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        
        // Texto alternativo para clientes sin HTML
        if ($altBody !== null) {
            $mail->AltBody = $altBody;
        } else {
            // Generar texto plano automáticamente
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        }

        // =====================================================================
        // Archivos adjuntos
        // =====================================================================
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                $mail->addAttachment($file);
            }
        }

        // =====================================================================
        // Enviar email
        // =====================================================================
        $mail->send();

        return [
            'success' => true,
            'message' => 'Email enviado correctamente',
            'error' => null
        ];

    } catch (Exception $e) {
        // Log del error
        error_log('[DEDUMSOFT_MAILER] Error enviando email a ' . $to . ': ' . $mail->ErrorInfo);

        return [
            'success' => false,
            'message' => 'No se pudo enviar el email',
            'error' => ENV['PROD'] ? 'Error de envío' : $mail->ErrorInfo
        ];
    }
}

/**
 * Enviar email de recuperación de contraseña.
 * 
 * @param string $to        Email del destinatario
 * @param string $nombre    Nombre del usuario
 * @param string $token     Token de recuperación
 * @param string $resetLink URL completa para resetear contraseña
 * 
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function send_password_reset_email(
    string $to,
    string $nombre,
    string $token,
    string $resetLink
): array {
    $siteName = ENV['SITE_NAME'] ?? 'Dedumsoft';
    $subject = "Recuperación de contraseña - $siteName";

    // Plantilla HTML del email
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">
                                🔐 Recuperación de Contraseña
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong>{$nombre}</strong>,
                            </p>
                            
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>{$siteName}</strong>.
                            </p>
                            
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                Haz clic en el siguiente botón para crear una nueva contraseña:
                            </p>
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 6px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <a href="{$resetLink}" target="_blank" style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 6px;">
                                            Restablecer Contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 30px 0 20px 0;">
                                Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:
                            </p>
                            
                            <p style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; word-break: break-all; font-size: 13px; color: #667eea; margin: 0 0 20px 0;">
                                {$resetLink}
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
                            
                            <p style="color: #999999; font-size: 13px; line-height: 1.6; margin: 0;">
                                ⏰ <strong>Este enlace expira en 1 hora</strong> por razones de seguridad.
                            </p>
                            
                            <p style="color: #999999; font-size: 13px; line-height: 1.6; margin: 15px 0 0 0;">
                                ⚠️ Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña permanecerá igual.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 25px 40px; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                Este es un correo automático, por favor no respondas a este mensaje.
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 10px 0 0 0;">
                                © 2026 {$siteName}. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    // Texto alternativo
    $altBody = <<<TEXT
Hola {$nombre},

Recibimos una solicitud para restablecer la contraseña de tu cuenta en {$siteName}.

Para crear una nueva contraseña, visita el siguiente enlace:
{$resetLink}

⏰ Este enlace expira en 1 hora por razones de seguridad.

⚠️ Si no solicitaste este cambio, puedes ignorar este correo.

--
{$siteName}
TEXT;

    return send_email($to, $subject, $htmlBody, $altBody);
}

/**
 * Probar la conexión SMTP.
 * Útil para verificar configuración sin enviar email real.
 * 
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function test_smtp_connection(): array {
    if (empty(ENV['MAIL_HOST']) || empty(ENV['MAIL_USERNAME'])) {
        return [
            'success' => false,
            'message' => 'Configuración incompleta',
            'error' => 'MAIL_HOST o MAIL_USERNAME no configurados'
        ];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = ENV['MAIL_USERNAME'];
        $mail->Password   = ENV['MAIL_PASSWORD'];
        $mail->Port       = ENV['MAIL_PORT'];

        if (ENV['MAIL_ENCRYPTION'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (ENV['MAIL_ENCRYPTION'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->Timeout = 10;

        // Probar conexión sin enviar
        if ($mail->smtpConnect()) {
            $mail->smtpClose();
            return [
                'success' => true,
                'message' => 'Conexión SMTP exitosa',
                'error' => null
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo conectar al servidor SMTP',
            'error' => 'Conexión fallida'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error de conexión SMTP',
            'error' => $mail->ErrorInfo
        ];
    }
}
