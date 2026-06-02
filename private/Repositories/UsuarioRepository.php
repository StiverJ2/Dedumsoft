<?php
/**
 * ============================================================================
 * USUARIO REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con usuarios.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_usuarios(offset, limit, activo) → SETOF
 *   fun_crear_usuario(username, nombre, clave, rolid, email, apellido, especialidad_id, telefono) → int
 *   fun_actualizar_usuario(id, nombre, rolid, email, activo) → BOOLEAN
 *   fun_actualizar_usuario_estado(id, activo) → BOOLEAN
 *   fun_eliminar_usuario(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class UsuarioRepository extends Repository
{
    /**
     * Listar usuarios con paginacion y filtros.
     *
     * @param int       $offset Inicio de paginacion
     * @param int       $limit  Cantidad de registros
     * @param bool|null $activo Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, username, nombre, email, rolid, rol_nombre, activo, fecha_registro
             FROM seguridad.fun_obtener_usuarios(:offset, :limit, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un usuario por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_usuario, username, nombre, email, rolid, rol_nombre, activo, fecha_registro
             FROM seguridad.fun_obtener_usuarios(0, 1, NULL)
             WHERE id_usuario = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear un nuevo usuario.
     *
     * @param string      $username
     * @param string      $nombre
     * @param string      $clave Hash bcrypt
     * @param int         $rolid
     * @param string|null $email
     * @param string|null $apellido
     * @param int|null    $especialidad_id
     * @param string|null $telefono
     * @return int ID del usuario creado
     */
    public function crear(string $username, string $nombre, string $clave, int $rolid, ?string $email = null, ?string $apellido = null, ?int $especialidad_id = null, ?string $telefono = null): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT seguridad.fun_crear_usuario(:username, :nombre, :clave, :rolid, :email, :apellido, :especialidad_id, :telefono) AS id_usuario'
        );
        $stmt->execute([
            ':username' => $username,
            ':nombre' => $nombre,
            ':clave' => $clave,
            ':rolid' => $rolid,
            ':email' => $email,
            ':apellido' => $apellido,
            ':especialidad_id' => $especialidad_id,
            ':telefono' => $telefono,
        ]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Actualizar un usuario existente (PATCH parcial).
     *
     * @param int         $id
     * @param string|null $nombre
     * @param int|null    $rolid
     * @param string|null $email
     * @param bool|null   $activo
     * @return bool
     */
    public function actualizar(int $id, ?string $nombre = null, ?int $rolid = null, ?string $email = null, ?bool $activo = null): bool
    {
        return $this->execBool(
            'SELECT seguridad.fun_actualizar_usuario(:id, :nombre, :rolid, :email, :activo)',
            [
                ':id' => $id,
                ':nombre' => $nombre,
                ':rolid' => $rolid,
                ':email' => $email,
                ':activo' => $activo,
            ]
        );
    }

    /**
     * Activar o desactivar un usuario (soft-delete).
     *
     * @param int  $id
     * @param bool $activo
     * @return bool
     */
    public function actualizarEstado(int $id, bool $activo): bool
    {
        return $this->execBool(
            'SELECT seguridad.fun_actualizar_usuario_estado(:id, :activo)',
            [':id' => $id, ':activo' => $activo]
        );
    }

    /**
     * Eliminar (soft-delete) un usuario.
     *
     * @param int $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT seguridad.fun_eliminar_usuario(:id)',
            [':id' => $id]
        );
    }

    /**
     * Invalidar un token JWT en base de datos (logout).
     *
     * @param string $token
     * @return bool
     */
    public function invalidarToken(string $token): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE seguridad.seg_login SET estado_token = FALSE WHERE token = :token'
        );
        $stmt->execute([':token' => $token]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener roles del sistema.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerRoles(): array
    {
        $stmt = $this->pdo->prepare('SELECT id_rol, nombre FROM seguridad.seg_rol ORDER BY id_rol');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte de usuarios (fun_reporte_usuarios).
     *
     * @return array<int, array<string, mixed>>
     */
    public function reporteUsuarios(): array
    {
        return $this->execSet(
            'SELECT id_usuario, username, nombre, rol, activo, created_at FROM seguridad.fun_reporte_usuarios()'
        );
    }
}
