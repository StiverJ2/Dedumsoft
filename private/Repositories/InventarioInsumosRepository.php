<?php
/**
 * ============================================================================
 * INVENTARIO INSUMOS REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con inventario de insumos.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_inventario_insumos(offset, limit, categoria, stock_bajo, activo) → SETOF
 *   fun_crear_inventario_insumos(nombre, categoria, unidad_medida, precio_unitario, descripcion, cantidad, stock_minimo, proveedor_id, ubicacion_id) → BOOLEAN
 *   fun_actualizar_inventario_insumos(id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id) → BOOLEAN
 *   fun_eliminar_inventario_insumos(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class InventarioInsumosRepository extends Repository
{
    /**
     * Listar inventario de insumos con paginacion y filtros.
     *
     * @param int         $offset     Inicio de paginacion
     * @param int         $limit      Cantidad de registros
     * @param string|null $categoria  Filtrar por categoria (null = todos)
     * @param bool|null   $stock_bajo Filtrar por stock bajo (null = sin filtro)
     * @param bool|null   $activo     Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?string $categoria = null, ?bool $stock_bajo = false, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, ubicacion_nombre, fecha_registro, proveedor_nombre, activo
             FROM fun_obtener_inventario_insumos(:offset, :limit, :categoria, :stock_bajo, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoria, $categoria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':stock_bajo', $stock_bajo, $stock_bajo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un insumo por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, ubicacion_nombre, fecha_registro, proveedor_nombre, activo
             FROM fun_obtener_inventario_insumos(0, 1, NULL, FALSE, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear un nuevo insumo.
     *
     * @param string      $nombre
     * @param string      $categoria
     * @param string      $unidad_medida
     * @param float       $precio_unitario
     * @param string|null $descripcion
     * @param float|null  $cantidad
     * @param float|null  $stock_minimo
     * @param int|null    $proveedor_id
     * @param int|null    $ubicacion_id
     * @return bool TRUE si se creo exitosamente
     */
    public function crear(string $nombre, string $categoria, string $unidad_medida, float $precio_unitario, ?string $descripcion = null, ?float $cantidad = null, ?float $stock_minimo = null, ?int $proveedor_id = null, ?int $ubicacion_id = null): bool
    {
        return $this->execBool(
            'SELECT fun_crear_inventario_insumos(:nombre, :categoria, :unidad_medida, :precio_unitario, :descripcion, :cantidad, :stock_minimo, :proveedor_id, :ubicacion_id)',
            [
                ':nombre' => $nombre,
                ':categoria' => $categoria,
                ':unidad_medida' => $unidad_medida,
                ':precio_unitario' => $precio_unitario,
                ':descripcion' => $descripcion,
                ':cantidad' => $cantidad,
                ':stock_minimo' => $stock_minimo,
                ':proveedor_id' => $proveedor_id,
                ':ubicacion_id' => $ubicacion_id,
            ]
        );
    }

    /**
     * Actualizar un insumo existente (PATCH parcial).
     *
     * @param int         $id
     * @param string|null $nombre
     * @param string|null $categoria
     * @param string|null $descripcion
     * @param float|null  $cantidad
     * @param string|null $unidad_medida
     * @param float|null  $precio_unitario
     * @param float|null  $stock_minimo
     * @param int|null    $proveedor_id
     * @param int|null    $ubicacion_id
     * @return bool TRUE si se actualizo exitosamente
     */
    public function actualizar(int $id, ?string $nombre = null, ?string $categoria = null, ?string $descripcion = null, ?float $cantidad = null, ?string $unidad_medida = null, ?float $precio_unitario = null, ?float $stock_minimo = null, ?int $proveedor_id = null, ?int $ubicacion_id = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_inventario_insumos(:id, :nombre, :categoria, :descripcion, :cantidad, :unidad_medida, :precio_unitario, :stock_minimo, :proveedor_id, :ubicacion_id)',
            [
                ':id' => $id,
                ':nombre' => $nombre,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':cantidad' => $cantidad,
                ':unidad_medida' => $unidad_medida,
                ':precio_unitario' => $precio_unitario,
                ':stock_minimo' => $stock_minimo,
                ':proveedor_id' => $proveedor_id,
                ':ubicacion_id' => $ubicacion_id,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) un insumo.
     *
     * @param int $id
     * @return bool TRUE si se elimino exitosamente
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_inventario_insumos(:id)',
            [':id' => $id]
        );
    }

    /**
     * Obtener categorias distintas de insumos.
     *
     * @return array<int, string>
     */
    public function obtenerCategorias(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT categoria FROM inventario_insumos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
