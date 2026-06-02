<?php
/**
 * ============================================================================
 * COMPRA CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para registro de compras / entradas de inventario.
 *
 * Unifica el comportamiento entre:
 * - public/api/produccion/compras.php (API JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/CompraRepository.php';

class CompraController extends Controller
{
    /** @var CompraRepository */
    private $repo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new CompraRepository($pdo);
    }

    // ========================================================================
    // MUTACIONES (para APIs)
    // ========================================================================

    /**
     * Registrar una compra / entrada de inventario.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function registrarCompra(array $input): array
    {
        $validation = $this->validateRequired($input, ['tipo_inventario', 'item_id']);
        if ($validation !== null) {
            return $validation;
        }

        $tipo = strtolower(trim((string) $input['tipo_inventario']));
        if ($tipo === '') {
            return $this->error('Tipo requerido.', 400);
        }

        $item_id = $this->toInt($input['item_id']);
        if ($item_id <= 0) {
            return $this->error('Item ID invalido.', 400);
        }

        $cantidad = isset($input['cantidad']) ? (float) $input['cantidad'] : 0.0;
        if ($cantidad <= 0) {
            return $this->error('Cantidad invalida.', 400);
        }

        $motivo = $this->toString($input['motivo'] ?? null) ?: null;
        $referencia = $this->toString($input['referencia'] ?? null) ?: null;
        $fecha = $this->toString($input['fecha'] ?? null) ?: null;
        if ($fecha === '') {
            $fecha = null;
        }

        $usuario_id = null;
        if (isset($input['usuario_id']) && $input['usuario_id'] !== null && $input['usuario_id'] !== '') {
            $uid = $this->toInt($input['usuario_id']);
            if ($uid > 0) {
                $usuario_id = $uid;
            }
        }

        try {
            $mov_id = $this->repo->registrar($tipo, $item_id, $cantidad, $motivo, $referencia, $usuario_id, $fecha);
            if (!$mov_id) {
                return $this->error('No se pudo registrar la compra.', 422);
            }
            return $this->success(['id' => $mov_id], 'Compra registrada.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CompraController::registrarCompra error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Item no encontrado.' : 'Error al registrar compra.',
                $code
            );
        }
    }
}
