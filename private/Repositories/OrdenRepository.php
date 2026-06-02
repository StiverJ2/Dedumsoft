<?php
/**
 * ============================================================================
 * ORDEN REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con ordenes de produccion.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_ordenes(offset, limit, estado) → SETOF
 *   fun_crear_orden(producto_id, cantidad, artesano_id, prioridad_id, observaciones) → INT
 *   fun_actualizar_orden(id, producto_id, cantidad, artesano_id, prioridad_id, observaciones, observaciones_terminada) → BOOLEAN
 *   fun_eliminar_orden(id) → BOOLEAN
 *   fun_cambiar_estado_orden(orden_id, estado_id) → RECORD(success, mensaje, rows_affected)
 *   fun_asignar_artesano(id, artesano_id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class OrdenRepository extends Repository
{
    /**
     * Listar ordenes de produccion con paginacion y filtro por estado.
     *
     * @param int        $offset Inicio de paginacion
     * @param int        $limit  Cantidad de registros
     * @param string|null $estado Filtrar por estado (null = todos)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?string $estado = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones, observaciones_terminada
             FROM fun_obtener_ordenes(:offset, :limit, :estado)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $estado, $estado === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una orden por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, producto_id, producto_nombre, cantidad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, artesano_id, artesano_nombre, estado, prioridad, observaciones, observaciones_terminada
             FROM fun_obtener_ordenes(0, 1, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear una nueva orden de produccion.
     *
     * @param int         $producto_id
     * @param int         $cantidad
     * @param int|null    $artesano_id
     * @param int|null    $prioridad_id
     * @param string|null $observaciones
     * @return int ID de la orden creada
     */
    public function crear(int $producto_id, int $cantidad, ?int $artesano_id = null, ?int $prioridad_id = null, ?string $observaciones = null): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT fun_crear_orden(:producto_id, :cantidad, :artesano_id, :prioridad_id, :observaciones)'
        );
        $stmt->bindValue(':producto_id', $producto_id, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindValue(':artesano_id', $artesano_id, $artesano_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':prioridad_id', $prioridad_id, $prioridad_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':observaciones', $observaciones, $observaciones === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Actualizar una orden existente (PATCH parcial).
     *
     * @param int         $id
     * @param int|null    $producto_id
     * @param int|null    $cantidad
     * @param int|null    $artesano_id
     * @param int|null    $prioridad_id
     * @param string|null $observaciones
     * @param string|null $observaciones_terminada
     * @return bool TRUE si se actualizo exitosamente
     */
    public function actualizar(int $id, ?int $producto_id = null, ?int $cantidad = null, ?int $artesano_id = null, ?int $prioridad_id = null, ?string $observaciones = null, ?string $observaciones_terminada = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_orden(:id, :producto_id, :cantidad, :artesano_id, :prioridad_id, :observaciones, :observaciones_terminada)',
            [
                ':id' => $id,
                ':producto_id' => $producto_id,
                ':cantidad' => $cantidad,
                ':artesano_id' => $artesano_id,
                ':prioridad_id' => $prioridad_id,
                ':observaciones' => $observaciones,
                ':observaciones_terminada' => $observaciones_terminada,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) una orden.
     *
     * @param int $id
     * @return bool TRUE si se elimino exitosamente
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_orden(:id)',
            [':id' => $id]
        );
    }

    /**
     * Cambiar el estado de una orden.
     *
     * @param int $orden_id
     * @param int $estado_id
     * @return array<string, mixed> {success, mensaje, rows_affected}
     */
    public function cambiarEstado(int $orden_id, int $estado_id): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT success, mensaje, rows_affected FROM fun_cambiar_estado_orden(:orden_id, :estado_id)'
        );
        $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $estado_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['success' => false, 'mensaje' => 'Error desconocido'];
    }

    /**
     * Asignar un artesano a una orden.
     *
     * @param int $id
     * @param int $artesano_id
     * @return bool TRUE si se asigno exitosamente
     */
    public function asignarArtesano(int $id, int $artesano_id): bool
    {
        return $this->execBool(
            'SELECT fun_asignar_artesano(:id, :artesano_id)',
            [
                ':id' => $id,
                ':artesano_id' => $artesano_id,
            ]
        );
    }
}
