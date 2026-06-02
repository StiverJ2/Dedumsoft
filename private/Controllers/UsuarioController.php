<?php
/**
 * ============================================================================
 * USUARIO CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para gestion de usuarios.
 *
 * Unifica el comportamiento entre:
 * - public/usuarios.php (vista HTML)
 * - public/api/usuarios.php (API JSON)
 * - public/api/auth/logout.php (cierre de sesion)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/UsuarioRepository.php';

class UsuarioController extends Controller
{
    /** @var UsuarioRepository */
    private $repo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new UsuarioRepository($pdo);
    }

    // ========================================================================
    // LISTAR / OBTENER
    // ========================================================================

    /**
     * Lista usuarios con paginacion y filtros.
     *
     * @param int       $offset
     * @param int       $limit
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listar(int $offset = 0, int $limit = 50, ?bool $activo = true): array
    {
        try {
            $rows = $this->repo->listar($offset, $limit, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('UsuarioController::listar error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un usuario por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtener(int $id): array
    {
        try {
            $row = $this->repo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Usuario no encontrado.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('UsuarioController::obtener error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // MUTACIONES
    // ========================================================================

    /**
     * Crear un usuario.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crear(array $input): array
    {
        $validation = $this->validateRequired($input, ['username', 'nombre', 'rolid', 'password']);
        if ($validation !== null) {
            return $validation;
        }

        $username = $this->toString($input['username']);
        $nombre   = $this->toString($input['nombre']);
        $password = (string) $input['password'];
        $rolid    = $this->toInt($input['rolid']);

        if (strlen($password) < 8) {
            return $this->error('La contraseña debe tener al menos 8 caracteres.', 400);
        }

        $email = $this->toString($input['email'] ?? null);
        $email = $email === '' ? null : $email;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email invalido.', 400);
        }

        $apellido = $this->toString($input['apellido'] ?? null);
        $apellido = $apellido === '' ? null : $apellido;
        $especialidad_id = isset($input['especialidad_id']) ? $this->toInt($input['especialidad_id']) : 0;
        $especialidad_id = $especialidad_id > 0 ? $especialidad_id : null;
        $telefono = $this->toString($input['telefono'] ?? null);
        $telefono = $telefono === '' ? null : $telefono;

        if ($rolid === 2 && $apellido === null) {
            return $this->error('Apellido requerido para rol Operador.', 400);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            return $this->error('No se pudo generar la contraseña.', 500);
        }

        try {
            $id = $this->repo->crear($username, $nombre, $hash, $rolid, $email, $apellido, $especialidad_id, $telefono);

            if ($id <= 0) {
                return $this->error('Error al crear usuario.', 500);
            }

            return $this->success(['id' => $id], 'Usuario creado.', 201);
        } catch (PDOException $e) {
            error_log('UsuarioController::crear error: ' . $e->getMessage());
            $message = $e->getMessage();
            if (strpos($message, 'Usuario ya existe') !== false || strpos($message, 'Email ya registrado') !== false) {
                return $this->error('Usuario o email ya registrado.', 409);
            } elseif (strpos($message, 'Rol invalido') !== false) {
                return $this->error('Rol invalido.', 400);
            } elseif (strpos($message, 'Apellido requerido') !== false) {
                return $this->error('Apellido requerido para rol Operador.', 400);
            } elseif (strpos($message, 'Especialidad invalida') !== false) {
                return $this->error('Especialidad invalida.', 400);
            }
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Activar o desactivar un usuario.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarEstado(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);
        $activo = isset($input['activo']) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        if ($activo === null) {
            return $this->error('Estado activo es requerido.', 400);
        }

        $session_user = get_session_user();
        if (!empty($session_user) && (int) ($session_user['id_usuario'] ?? 0) === $id && $activo === false) {
            return $this->error('No puedes desactivar tu propio usuario.', 400);
        }

        try {
            $ok = $this->repo->actualizarEstado($id, $activo);
            if (!$ok) {
                return $this->error('No se pudo actualizar el usuario.', 422);
            }
            return $this->success(null, $activo ? 'Usuario activado.' : 'Usuario desactivado.', 200);
        } catch (PDOException $e) {
            error_log('UsuarioController::actualizarEstado error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Usuario no encontrado.' : 'Error al actualizar.',
                $code
            );
        }
    }

    // ========================================================================
    // AUTH
    // ========================================================================

    /**
     * Cerrar sesion: invalida token JWT y destruye la sesion PHP.
     *
     * @param string|null $token
     * @return array<string, mixed>
     */
    public function logout(?string $token): array
    {
        if ($token) {
            try {
                $this->repo->invalidarToken($token);
            } catch (PDOException $e) {
                error_log('UsuarioController::logout error: ' . $e->getMessage());
            }
        }

        session_destroy();
        return $this->success(null, 'Sesion cerrada.', 200);
    }

    // ========================================================================
    // LEGACY PAGE HELPERS
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de usuarios.
     *
     * @param array $get           $_GET
     * @param bool  $legacy
     * @param int   $currentUserId
     * @return array<string, mixed> Datos para la vista
     */
    public function pageData(array $get, bool $legacy, int $currentUserId = 0): array
    {
        $rol_filter       = $this->toString($get['rol'] ?? '');
        $rol_filter_value = $rol_filter;
        $activo_filter    = $this->toString($get['activo'] ?? '');
        $usuarios_rows    = [];
        $rol_options      = [];
        $rol_id_map       = [];

        try {
            $roles = $this->repo->obtenerRoles();
            $rol_options = array_map(function ($r) use (&$rol_id_map) {
                $nombre = trim((string) $r['nombre']);
                $key    = strtolower($nombre);
                $label  = $nombre;
                if ($key === 'admin') {
                    $label = 'Administrador';
                } elseif ($key === 'operador') {
                    $label = 'Operador';
                } elseif ($key === 'lectura') {
                    $label = 'Lectura';
                }
                $rol_id_map[(string) ($r['id_rol'] ?? '')] = $nombre;
                return [
                    'value' => $nombre,
                    'label' => $label,
                ];
            }, $roles);
        } catch (PDOException $e) {
            error_log('UsuarioController::pageData roles error: ' . $e->getMessage());
            $rol_options = [
                ['value' => 'ADMIN',    'label' => 'Administrador'],
                ['value' => 'OPERADOR', 'label' => 'Operador'],
                ['value' => 'LECTURA',  'label' => 'Lectura'],
            ];
        }

        if ($legacy && $rol_filter !== '') {
            if (ctype_digit($rol_filter) && isset($rol_id_map[$rol_filter])) {
                $rol_filter_value = (string) $rol_id_map[$rol_filter];
            } else {
                $key = strtolower($rol_filter);
                if ($key === 'administrador' || $key === 'admin') {
                    $rol_filter_value = 'ADMIN';
                } elseif ($key === 'operador') {
                    $rol_filter_value = 'OPERADOR';
                } elseif ($key === 'lectura') {
                    $rol_filter_value = 'LECTURA';
                }
            }
        }

        if ($legacy) {
            try {
                $all_rows = $this->repo->reporteUsuarios();

                foreach ($all_rows as $row) {
                    if ($rol_filter_value !== '' && strtolower($row['rol']) !== strtolower($rol_filter_value)) {
                        continue;
                    }
                    if ($activo_filter !== '') {
                        $is_active = !empty($row['activo']);
                        if ($activo_filter === '1' && !$is_active) {
                            continue;
                        }
                        if ($activo_filter === '0' && $is_active) {
                            continue;
                        }
                    }
                    $usuarios_rows[] = $row;
                }
            } catch (PDOException $e) {
                error_log('UsuarioController::pageData legacy error: ' . $e->getMessage());
            }
        }

        return [
            'rol_filter'       => $rol_filter,
            'rol_filter_value' => $rol_filter_value,
            'activo_filter'    => $activo_filter,
            'usuarios_rows'    => $usuarios_rows,
            'rol_options'      => $rol_options,
            'current_user_id' => $currentUserId,
            'legacy'           => $legacy,
        ];
    }
}
