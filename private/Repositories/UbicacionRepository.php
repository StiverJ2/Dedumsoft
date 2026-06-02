<?php
/**
 * ============================================================================
 * UBICACION REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con ubicaciones.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_ubicaciones(offset, limit, area_id, activo) → SETOF
 *   fun_crear_ubicacion(nombre, descripcion, area_id) → int
 *   fun_actualizar_ubicacion(id, nombre, descripcion, area_id, activo) → BOOLEAN
 *   fun_eliminar_ubicacion(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class UbicacionRepository extends Repository
{
    /**
     * Listar ubicaciones con paginacion y filtros.
     *
     * @param int       $offset  Inicio de paginacion
     * @param int       $limit   Cantidad de registros
     * @param int|null  $area_id Filtrar por area (null = todas)
     * @param bool|null $activo  Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 100, ?int $area_id = null, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, descripcion, area_id, area_nombre, activo, created_at
             FROM fun_obtener_ubicaciones(:offset, :limit, :area_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':area_id', $area_id, $area_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una ubicacion por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, descripcion, area_id, area_nombre, activo, created_at
             FROM fun_obtener_ubicaciones(0, 1, NULL, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear una nueva ubicacion.
     *
     * @param string      $nombre
     * @param string|null $descripcion
     * @param int|null    $area_id
     * @return int ID de la ubicacion creada
     */
    public function crear(string $nombre, ?string $descripcion = null, ?int $area_id = null): int
    {
        $stmt = $this->pdo->prepare('SELECT fun_crear_ubicacion(:nombre, :descripcion, :area_id)');
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':area_id' => $area_id]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Actualizar una ubicacion existente (PATCH parcial).
     *
     * @param int         $id
     * @param string|null $nombre
     * @param string|null $descripcion
     * @param int|null    $area_id
     * @param bool|null   $activo
     * @return bool
     */
    public function actualizar(int $id, ?string $nombre = null, ?string $descripcion = null, ?int $area_id = null, ?bool $activo = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_ubicacion(:id, :nombre, :descripcion, :area_id, :activo)',
            [
                ':id' => $id,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':area_id' => $area_id,
                ':activo' => $activo,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) una ubicacion.
     *
     * @param int $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_ubicacion(:id)',
            [':id' => $id]
        );
    }
}
