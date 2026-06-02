<?php
/**
 * ============================================================================
 * ARTESANO REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL relacionadas con operaciones de artesanos.
 *
 * - NO contiene logica de negocio.
 * - NO contiene SQL directo (salvo la llamada a la funcion).
 * - Solo prepara parametros y ejecuta la funcion correspondiente.
 *
 * Funciones PL/pgSQL utilizadas:
 *   fun_obtener_artesano_ordenes(artesano_id, offset, limit) → SETOF
 *   fun_artesano_registrar_consumo(orden_id, tipo_material, material_id, cantidad, usuario_id) → RECORD(success, mensaje, consumo_id)
 *   fun_artesano_marcar_terminada(orden_id, peso_final, tiempo_real, calidad_id, observaciones) → RECORD(success, mensaje, creacion_id, costo_materiales)
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class ArtesanoRepository extends Repository
{
    /**
     * Obtener ordenes asignadas a un artesano especifico.
     *
     * @param int $artesano_id ID del artesano
     * @param int $offset       Inicio de paginacion
     * @param int $limit        Cantidad de registros
     * @return array<int, array<string, mixed>>
     */
    public function obtenerOrdenes(int $artesano_id, int $offset = 0, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, producto_id, producto_nombre, cantidad, estado_id, estado, prioridad_id, prioridad, fecha_creacion, fecha_inicio, fecha_fin_estimada, fecha_fin_real, observaciones
             FROM fun_obtener_artesano_ordenes(:artesano_id, :offset, :limit)'
        );
        $stmt->bindValue(':artesano_id', $artesano_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registrar el consumo de material en una orden.
     *
     * @param int         $orden_id      ID de la orden
     * @param string      $tipo_material 'oro' o 'insumo'
     * @param int         $material_id   ID del material
     * @param float       $cantidad      Cantidad consumida
     * @param int|null    $usuario_id    ID del usuario que registra (auditoria)
     * @return array<string, mixed> {success, mensaje, consumo_id}
     */
    public function registrarConsumo(int $orden_id, string $tipo_material, int $material_id, float $cantidad, ?int $usuario_id = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT success, mensaje, consumo_id FROM fun_artesano_registrar_consumo(:orden_id, :tipo_material, :material_id, :cantidad, :usuario_id)'
        );
        $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_material', $tipo_material, PDO::PARAM_STR);
        $stmt->bindValue(':material_id', $material_id, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_STR);
        $stmt->bindValue(':usuario_id', $usuario_id, $usuario_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['success' => false, 'mensaje' => 'Error desconocido'];
    }

    /**
     * Marcar una orden como terminada y registrar la pieza final.
     *
     * @param int         $orden_id       ID de la orden
     * @param float       $peso_final     Peso final de la pieza en gramos
     * @param float|null  $tiempo_real    Tiempo real de produccion en horas
     * @param int|null    $calidad_id     ID del nivel de calidad
     * @param string|null $observaciones  Notas del artesano
     * @return array<string, mixed> {success, mensaje, creacion_id, costo_materiales}
     */
    public function marcarTerminada(int $orden_id, float $peso_final, ?float $tiempo_real = null, ?int $calidad_id = null, ?string $observaciones = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT success, mensaje, creacion_id, costo_materiales FROM fun_artesano_marcar_terminada(:orden_id, :peso_final, :tiempo_real, :calidad_id, :observaciones)'
        );
        $stmt->bindValue(':orden_id', $orden_id, PDO::PARAM_INT);
        $stmt->bindValue(':peso_final', $peso_final, PDO::PARAM_STR);
        $stmt->bindValue(':tiempo_real', $tiempo_real, $tiempo_real === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':calidad_id', $calidad_id, $calidad_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':observaciones', $observaciones, $observaciones === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['success' => false, 'mensaje' => 'Error desconocido'];
    }

    /**
     * Obtener un artesano por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, apellido, especialidades FROM fun_obtener_artesanos(TRUE) WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
