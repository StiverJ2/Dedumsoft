<?php
/**
 * ============================================================================
 * INVENTARIO ORO REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con inventario de oro.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_inventario_oro(offset, limit, tipo_id, activo) → SETOF
 *   fun_crear_inventario_oro(tipo_oro_id, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza) → BOOLEAN
 *   fun_actualizar_inventario_oro(id, tipo_oro_id, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza) → BOOLEAN
 *   fun_eliminar_inventario_oro(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class InventarioOroRepository extends Repository
{
    /**
     * Listar inventario de oro con paginacion y filtros.
     *
     * @param int       $offset   Inicio de paginacion
     * @param int       $limit    Cantidad de registros
     * @param int|null  $tipo_id  Filtrar por tipo de oro (null = todos)
     * @param bool|null $activo   Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?int $tipo_id = null, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, fecha_registro, valor_total, proveedor_nombre, activo
             FROM fun_obtener_inventario_oro(:offset, :limit, :tipo_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $tipo_id, $tipo_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un registro de oro por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tipo_oro_id, tipo_oro_nombre, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, fecha_registro, valor_total, proveedor_nombre, activo
             FROM fun_obtener_inventario_oro(0, 1, NULL, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear un nuevo registro de oro.
     *
     * @param int         $tipo_oro_id
     * @param float       $peso_gramos
     * @param float       $precio_gramo
     * @param int|null    $proveedor_id
     * @param string|null $fecha_ingreso
     * @param string|null $ubicacion
     * @param float|null  $pureza
     * @return bool TRUE si se creo exitosamente
     */
    public function crear(int $tipo_oro_id, float $peso_gramos, float $precio_gramo, ?int $proveedor_id = null, ?string $fecha_ingreso = null, ?string $ubicacion = null, ?float $pureza = null): bool
    {
        return $this->execBool(
            'SELECT fun_crear_inventario_oro(:tipo_oro_id, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza)',
            [
                ':tipo_oro_id' => $tipo_oro_id,
                ':peso_gramos' => $peso_gramos,
                ':precio_gramo' => $precio_gramo,
                ':proveedor_id' => $proveedor_id,
                ':fecha_ingreso' => $fecha_ingreso,
                ':ubicacion' => $ubicacion,
                ':pureza' => $pureza,
            ]
        );
    }

    /**
     * Actualizar un registro de oro existente (PATCH parcial).
     *
     * @param int         $id
     * @param int|null    $tipo_oro_id
     * @param float|null  $peso_gramos
     * @param float|null  $precio_gramo
     * @param int|null    $proveedor_id
     * @param string|null $fecha_ingreso
     * @param string|null $ubicacion
     * @param float|null  $pureza
     * @return bool TRUE si se actualizo exitosamente
     */
    public function actualizar(int $id, ?int $tipo_oro_id = null, ?float $peso_gramos = null, ?float $precio_gramo = null, ?int $proveedor_id = null, ?string $fecha_ingreso = null, ?string $ubicacion = null, ?float $pureza = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_inventario_oro(:id, :tipo_oro_id, :peso_gramos, :precio_gramo, :proveedor_id, :fecha_ingreso, :ubicacion, :pureza)',
            [
                ':id' => $id,
                ':tipo_oro_id' => $tipo_oro_id,
                ':peso_gramos' => $peso_gramos,
                ':precio_gramo' => $precio_gramo,
                ':proveedor_id' => $proveedor_id,
                ':fecha_ingreso' => $fecha_ingreso,
                ':ubicacion' => $ubicacion,
                ':pureza' => $pureza,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) un registro de oro.
     *
     * @param int $id
     * @return bool TRUE si se elimino exitosamente
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_inventario_oro(:id)',
            [':id' => $id]
        );
    }
}
