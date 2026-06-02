<?php
/**
 * ============================================================================
 * COMPRA REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL de registro de compras.
 *
 * Funciones utilizadas:
 *   fun_registrar_compra_oro(item_id, cantidad, motivo, referencia, usuario_id, fecha)
 *   fun_registrar_compra_insumo(item_id, cantidad, motivo, referencia, usuario_id, fecha)
 *   fun_registrar_compra_maquinaria(item_id, cantidad, motivo, referencia, usuario_id, fecha)
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';

class CompraRepository extends Repository
{
    /**
     * Registrar una compra/entrada de inventario.
     *
     * @param string      $tipo        Tipo de inventario: oro, insumo, maquinaria
     * @param int         $item_id     ID del item
     * @param float       $cantidad    Cantidad comprada
     * @param string|null $motivo      Motivo de la compra
     * @param string|null $referencia  Referencia externa
     * @param int|null    $usuario_id  ID del usuario que registra
     * @param string|null $fecha       Fecha de la compra
     * @return int ID del movimiento creado
     * @throws InvalidArgumentException
     */
    public function registrar(string $tipo, int $item_id, float $cantidad, ?string $motivo = null, ?string $referencia = null, ?int $usuario_id = null, ?string $fecha = null): int
    {
        switch (strtolower($tipo)) {
            case 'oro':
                $sql = 'SELECT fun_registrar_compra_oro(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)';
                break;
            case 'insumo':
            case 'insumos':
                $sql = 'SELECT fun_registrar_compra_insumo(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)';
                break;
            case 'maquinaria':
                $sql = 'SELECT fun_registrar_compra_maquinaria(:item_id, :cantidad, :motivo, :referencia, :usuario_id, :fecha)';
                break;
            default:
                throw new InvalidArgumentException('Tipo de inventario no soportado.');
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad);
        $stmt->bindValue(':motivo', $motivo, $motivo !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':referencia', $referencia, $referencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':usuario_id', $usuario_id, $usuario_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha', $fecha, $fecha !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
