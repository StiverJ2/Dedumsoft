<?php
/**
 * ============================================================================
 * BASE CONTROLLER
 * ============================================================================
 *
 * Clase base para todos los controllers de Dedumsoft.
 *
 * Un Controller encapsula la LOGICA DE NEGOCIO de un dominio:
 * - Validacion de inputs
 * - Reglas de negocio
 * - Orquestacion de repositories
 * - Manejo de errores
 *
 * Un Controller NO hace render de HTML ni output de JSON directamente.
 * Devuelve arrays estructurados que las vistas (pages) o APIs consumen.
 *
 * Patron de retorno:
 *   [
 *     'success' => true|false,
 *     'data'    => mixed,
 *     'message' => string,
 *     'code'    => int,
 *   ]
 *
 * @package Dedumsoft\Controllers
 */

abstract class Controller
{
    /** @var PDO */
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Construye una respuesta estandarizada.
     *
     * @param bool        $success
     * @param mixed|null  $data
     * @param string      $message
     * @param int         $code
     * @return array<string, mixed>
     */
    protected function response(bool $success, $data = null, string $message = '', int $code = 200): array
    {
        return [
            'success' => $success,
            'data'    => $data,
            'message' => $message,
            'code'    => $code,
        ];
    }

    /**
     * Respuesta de exito.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $code
     * @return array<string, mixed>
     */
    protected function success($data = null, string $message = 'OK', int $code = 200): array
    {
        return $this->response(true, $data, $message, $code);
    }

    /**
     * Respuesta de error.
     *
     * @param string $message
     * @param int    $code
     * @return array<string, mixed>
     */
    protected function error(string $message = 'Error', int $code = 400): array
    {
        return $this->response(false, null, $message, $code);
    }

    /**
     * Valida que un array contenga ciertas claves requeridas.
     *
     * @param array $input
     * @param array $required
     * @return array|null NULL si pasa la validacion, o array de error si falla
     */
    protected function validateRequired(array $input, array $required): ?array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return $this->error('Campos requeridos: ' . implode(', ', $missing), 422);
        }
        return null;
    }

    /**
     * Convierte un valor a entero de forma segura.
     *
     * @param mixed $value
     * @param int   $default
     * @return int
     */
    protected function toInt($value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }

    /**
     * Convierte un valor a float de forma segura.
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    protected function toFloat($value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (float) $value;
    }

    /**
     * Convierte un valor a string de forma segura.
     *
     * @param mixed  $value
     * @param string $default
     * @return string
     */
    protected function toString($value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }
        return trim((string) $value);
    }

    /**
     * Parsea un parametro booleano desde query string.
     *
     * @param mixed    $value
     * @param bool     $default
     * @return bool|null NULL = sin filtro
     */
    protected function parseBool($value, bool $default = true): ?bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed !== null ? $parsed : $default;
    }
}
