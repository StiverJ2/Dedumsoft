<?php
/**
 * ============================================================================
 * PROVEEDOR REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con proveedores.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_proveedores(offset, limit, tipo_id, activo) → SETOF
 *   fun_crear_proveedor(nombre, tipo_id, contacto, telefono, email, direccion) → BOOLEAN
 *   fun_actualizar_proveedor(id, nombre, tipo_id, contacto, telefono, email, direccion, activo) → BOOLEAN
 *   fun_eliminar_proveedor(id) → BOOLEAN
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class ProveedorRepository extends Repository
{
    /**
     * Listar proveedores con paginacion y filtros.
     *
     * @param int       $offset  Inicio de paginacion
     * @param int       $limit   Cantidad de registros
     * @param int|null  $tipo_id Filtrar por tipo de proveedor (null = todos)
     * @param bool|null $activo  Filtrar por estado (null = sin filtro)
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $offset = 0, int $limit = 50, ?int $tipo_id = null, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, tipo_proveedor_id, tipo_nombre, contacto, telefono, email, direccion, activo, fecha_registro
             FROM fun_obtener_proveedores(:offset, :limit, :tipo_id::int, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_id', $tipo_id, $tipo_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un proveedor por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        // La funcion fun_obtener_proveedores no filtra por ID,
        // asi que filtramos post-query (ineficiente pero funciona
        // hasta que exista fun_r_proveedor).
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, tipo_proveedor_id, tipo_nombre, contacto, telefono, email, direccion, activo, fecha_registro
             FROM fun_obtener_proveedores(0, 1, NULL, NULL)
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear un nuevo proveedor.
     *
     * @param string      $nombre
     * @param int         $tipo_proveedor_id
     * @param string|null $contacto
     * @param string|null $telefono
     * @param string|null $email
     * @param string|null $direccion
     * @return bool TRUE si se creo exitosamente
     */
    public function crear(string $nombre, int $tipo_proveedor_id, ?string $contacto = null, ?string $telefono = null, ?string $email = null, ?string $direccion = null): bool
    {
        return $this->execBool(
            'SELECT fun_crear_proveedor(:nombre, :tipo_proveedor_id, :contacto, :telefono, :email, :direccion)',
            [
                ':nombre' => $nombre,
                ':tipo_proveedor_id' => $tipo_proveedor_id,
                ':contacto' => $contacto,
                ':telefono' => $telefono,
                ':email' => $email,
                ':direccion' => $direccion,
            ]
        );
    }

    /**
     * Actualizar un proveedor existente (PATCH parcial).
     *
     * @param int         $id
     * @param string|null $nombre
     * @param int|null    $tipo_proveedor_id
     * @param string|null $contacto
     * @param string|null $telefono
     * @param string|null $email
     * @param string|null $direccion
     * @param bool|null   $activo
     * @return bool TRUE si se actualizo exitosamente
     */
    public function actualizar(int $id, ?string $nombre = null, ?int $tipo_proveedor_id = null, ?string $contacto = null, ?string $telefono = null, ?string $email = null, ?string $direccion = null, ?bool $activo = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_proveedor(:id, :nombre, :tipo_proveedor_id, :contacto, :telefono, :email, :direccion, :activo)',
            [
                ':id' => $id,
                ':nombre' => $nombre,
                ':tipo_proveedor_id' => $tipo_proveedor_id,
                ':contacto' => $contacto,
                ':telefono' => $telefono,
                ':email' => $email,
                ':direccion' => $direccion,
                ':activo' => $activo,
            ]
        );
    }

    /**
     * Eliminar (soft-delete) un proveedor.
     *
     * @param int $id
     * @return bool TRUE si se elimino exitosamente
     */
    public function eliminar(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_proveedor(:id)',
            [':id' => $id]
        );
    }
}
