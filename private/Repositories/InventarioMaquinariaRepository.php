<?php
/**
 * ============================================================================
 * INVENTARIO MAQUINARIA REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con inventario de maquinaria.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_inventario_maquinaria(offset, limit, estado_id, activo) → SETOF
 *   fun_crear_inventario_maquinaria(nombre, sku, tipo_maquinaria_id, marca, modelo, fecha_compra, valor_compra, estado_id, ubicacion_id) → BOOLEAN
 *   fun_actualizar_inventario_maquinaria(id, nombre, sku, tipo_maquinaria_id, marca, modelo, fecha_compra, valor_compra, estado_id, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, activo) → BOOLEAN
 *   fun_eliminar_inventario_maquinaria(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class InventarioMaquinariaRepository extends Repository
{
    /**
     * Listar inventario de maquinaria con paginacion y filtros.
     *
     * @param int       $offset    Inicio de paginacion
     * @param int       $limit     Cantidad de registros
     * @param int|null  $estado_id Filtrar por estado operativo (null = todos)
     * @param bool|null $activo    Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?int $estado_id = null, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sku, nombre, tipo_maquinaria_id, tipo_nombre, marca, modelo, fecha_compra, valor_compra, estado_id, estado_nombre, estado_color, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo
             FROM fun_obtener_inventario_maquinaria(:offset, :limit, :estado_id, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $estado_id, $estado_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un equipo de maquinaria por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sku, nombre, tipo_maquinaria_id, tipo_nombre, marca, modelo, fecha_compra, valor_compra, estado_id, estado_nombre, estado_color, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, ubicacion_nombre, fecha_registro, activo
             FROM fun_obtener_inventario_maquinaria(0, 1, NULL, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear un nuevo equipo de maquinaria.
     *
     * @param string      $nombre
     * @param string      $sku
     * @param int         $tipo_maquinaria_id
     * @param string|null $marca
     * @param string|null $modelo
     * @param string|null $fecha_compra
     * @param float|null  $valor_compra
     * @param int|null    $estado_id
     * @param int|null    $ubicacion_id
     * @return bool TRUE si se creo exitosamente
     */
    public function crear(string $nombre, string $sku, int $tipo_maquinaria_id, ?string $marca = null, ?string $modelo = null, ?string $fecha_compra = null, ?float $valor_compra = null, ?int $estado_id = null, ?int $ubicacion_id = null): bool
    {
        return $this->execBool(
            'SELECT fun_crear_inventario_maquinaria(:nombre, :sku, :tipo_maquinaria_id, :marca, :modelo, :fecha_compra, :valor_compra, :estado_id, :ubicacion_id)',
            [
                ':nombre' => $nombre,
                ':sku' => $sku,
                ':tipo_maquinaria_id' => $tipo_maquinaria_id,
                ':marca' => $marca,
                ':modelo' => $modelo,
                ':fecha_compra' => $fecha_compra,
                ':valor_compra' => $valor_compra,
                ':estado_id' => $estado_id,
                ':ubicacion_id' => $ubicacion_id,
            ]
        );
    }

    /**
     * Actualizar un equipo de maquinaria existente (PATCH parcial).
     *
     * @param int         $id
     * @param string|null $nombre
     * @param string|null $sku
     * @param int|null    $tipo_maquinaria_id
     * @param string|null $marca
     * @param string|null $modelo
     * @param string|null $fecha_compra
     * @param float|null  $valor_compra
     * @param int|null    $estado_id
     * @param string|null $ultima_mantenimiento
     * @param string|null $proxima_mantenimiento
     * @param int|null    $ubicacion_id
     * @param bool|null   $activo
     * @return bool TRUE si se actualizo exitosamente
     */
    public function actualizar(int $id, ?string $nombre = null, ?string $sku = null, ?int $tipo_maquinaria_id = null, ?string $marca = null, ?string $modelo = null, ?string $fecha_compra = null, ?float $valor_compra = null, ?int $estado_id = null, ?string $ultima_mantenimiento = null, ?string $proxima_mantenimiento = null, ?int $ubicacion_id = null, ?bool $activo = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_inventario_maquinaria(:id, :nombre, :sku, :tipo_maquinaria_id, :marca, :modelo, :fecha_compra, :valor_compra, :estado_id, :ultima_mantenimiento, :proxima_mantenimiento, :ubicacion_id, :activo)',
            [
                ':id' => $id,
                ':nombre' => $nombre,
                ':sku' => $sku,
                ':tipo_maquinaria_id' => $tipo_maquinaria_id,
                ':marca' => $marca,
                ':modelo' => $modelo,
                ':fecha_compra' => $fecha_compra,
                ':valor_compra' => $valor_compra,
                ':estado_id' => $estado_id,
                ':ultima_mantenimiento' => $ultima_mantenimiento,
                ':proxima_mantenimiento' => $proxima_mantenimiento,
                ':ubicacion_id' => $ubicacion_id,
                ':activo' => $activo,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) un equipo de maquinaria.
     *
     * @param int $id
     * @return bool TRUE si se elimino exitosamente
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_inventario_maquinaria(:id)',
            [':id' => $id]
        );
    }
}
