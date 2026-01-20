<?php
/**
 * ============================================================================
 * API DE AUTENTICACIÓN: LOGIN
 * ============================================================================
 * 
 * Endpoint para iniciar sesión en el sistema.
 * Recibe credenciales via POST y retorna resultado de autenticación.
 * 
 * Método: POST
 * Content-Type: application/x-www-form-urlencoded
 * 
 * Parámetros POST:
 * - username (string, requerido): Nombre de usuario
 * - password (string, requerido): Contraseña
 * 
 * Respuestas:
 * - 200: Login exitoso (incluye datos de sesión)
 * - 401: Credenciales inválidas
 * - 405: Método HTTP no permitido (solo POST)
 * - 429: Demasiados intentos (rate limit)
 * - 500: Error interno del servidor
 * 
 * Flujo:
 * 1. Valida método HTTP (solo POST)
 * 2. Extrae credenciales del body
 * 3. Delega a login_service.php para validación
 * 4. Retorna respuesta JSON
 * 
 * @package Dedumsoft\Auth
 * @author  Equipo Dedumsoft
 * @see     LoginService.php Para la lógica de autenticación
 */

// Cargar bootstrap (configuración + autoload)
require_once __DIR__ . '/../../../private/bootstrap.php';

// Cargar dependencias
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/LoginService.php';

header('Content-Type: application/json');

// Validar método HTTP (solo POST permitido)
if (!validateHttpMethod('POST')) {
    exit;
}

// Extraer credenciales del formulario
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Ejecutar proceso de login y retornar respuesta
$response = login_user($connLogic, $username, $password);
http_response_code((int) ($response['CODIGO'] ?? 500));
echo json_encode($response);
