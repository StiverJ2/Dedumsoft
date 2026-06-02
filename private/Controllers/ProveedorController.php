<?php
/**
 * ============================================================================
 * PROVEEDOR CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para gestion de proveedores.
 *
 * Unifica el comportamiento entre:
 * - public/proveedores.php (vista HTML)
 * - public/api/catalogos/proveedores.php (API JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

class ProveedorController extends Controller
{
    /** @var ProveedorRepository */
    private $repo;

    /** @var CatalogoRepository */
    private $catRepo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo    = new ProveedorRepository($pdo);
        $this->catRepo = new CatalogoRepository($pdo);
    }

    // ========================================================================
    // LISTAR (para vistas y APIs)
    // ========================================================================

    /**
     * Lista proveedores con filtros.
     *
     * @param int       $offset
     * @param int       $limit
     * @param int|null  $tipoId
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listar(int $offset = 0, int $limit = 50, ?int $tipoId = null, ?bool $activo = true): array
    {
        try {
            $rows = $this->repo->listar($offset, $limit, $tipoId, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ProveedorController::listar error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un proveedor por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtener(int $id): array
    {
        try {
            $row = $this->repo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Proveedor no encontrado.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('ProveedorController::obtener error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // CATALOGOS / OPCIONES (para dropdowns en formularios)
    // ========================================================================

    /**
     * Obtener tipos de proveedor para dropdowns.
     *
     * @return array<string, mixed>
     */
    public function tipos(): array
    {
        try {
            $rows = $this->catRepo->obtenerOpciones('tipos_proveedor');
            return $this->success($rows, 'OK', 200);
        } catch (Exception $e) {
            error_log('ProveedorController::tipos error: ' . $e->getMessage());
            return $this->success([
                ['value' => 1, 'label' => 'Oro'],
                ['value' => 2, 'label' => 'Insumos'],
                ['value' => 3, 'label' => 'Maquinaria']
            ], 'Fallback', 200);
        }
    }

    // ========================================================================
    // MUTACIONES (para APIs)
    // ========================================================================

    /**
     * Crear un proveedor.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crear(array $input): array
    {
        $validation = $this->validateRequired($input, ['nombre', 'tipo_proveedor_id']);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $ok = $this->repo->crear(
                $this->toString($input['nombre']),
                $this->toInt($input['tipo_proveedor_id']),
                $this->toString($input['contacto'] ?? null) ?: null,
                $this->toString($input['telefono'] ?? null) ?: null,
                $this->toString($input['email'] ?? null) ?: null,
                $this->toString($input['direccion'] ?? null) ?: null
            );

            if (!$ok) {
                return $this->error('No se pudo crear el proveedor.', 422);
            }

            return $this->success(null, 'Proveedor creado.', 201);
        } catch (PDOException $e) {
            error_log('ProveedorController::crear error: ' . $e->getMessage());
            return $this->error('Error al crear proveedor.', 500);
        }
    }

    /**
     * Actualizar un proveedor (PATCH parcial).
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizar(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);

        try {
            $ok = $this->repo->actualizar(
                $id,
                isset($input['nombre']) ? $this->toString($input['nombre']) : null,
                isset($input['tipo_proveedor_id']) ? $this->toInt($input['tipo_proveedor_id']) : null,
                isset($input['contacto']) ? $this->toString($input['contacto']) : null,
                isset($input['telefono']) ? $this->toString($input['telefono']) : null,
                isset($input['email']) ? $this->toString($input['email']) : null,
                isset($input['direccion']) ? $this->toString($input['direccion']) : null,
                isset($input['activo']) ? (bool) $input['activo'] : null
            );

            if (!$ok) {
                return $this->error('No se pudo actualizar el proveedor.', 422);
            }

            return $this->success(null, 'Proveedor actualizado.', 200);
        } catch (PDOException $e) {
            error_log('ProveedorController::actualizar error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Proveedor no encontrado.' : 'Error al actualizar.',
                $code
            );
        }
    }

    /**
     * Eliminar (soft-delete) un proveedor.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminar(int $id): array
    {
        try {
            $ok = $this->repo->eliminar($id);

            if (!$ok) {
                return $this->error('No se pudo eliminar el proveedor.', 422);
            }

            return $this->success(null, 'Proveedor eliminado.', 200);
        } catch (PDOException $e) {
            error_log('ProveedorController::eliminar error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Proveedor no encontrado.' : 'Error al eliminar.',
                $code
            );
        }
    }

    // ========================================================================
    // LEGACY PAGE HELPERS (para public/proveedores.php)
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de proveedores.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageData(array $get, bool $legacy): array
    {
        $tipo   = $this->toString($get['tipo'] ?? '');
        $tipoId = null;
        $rows   = [];
        $options = [];

        if ($legacy) {
            $tiposResult = $this->tipos();
            $options = $tiposResult['data'] ?? [];

            if ($tipo !== '') {
                if (ctype_digit($tipo) && (int) $tipo > 0) {
                    $tipoId = (int) $tipo;
                } else {
                    foreach ($options as $opt) {
                        if (strcasecmp((string) ($opt['label'] ?? ''), $tipo) === 0) {
                            $tipoId = (int) ($opt['value'] ?? 0);
                            break;
                        }
                    }
                }
            }

            $limit = $tipoId !== null ? 200 : 20;
            $result = $this->listar(0, $limit, $tipoId, true);
            $rows = $result['data'] ?? [];

            if ($tipoId !== null) {
                $rows = array_values(array_filter($rows, function ($row) use ($tipoId) {
                    return (int) ($row['tipo_proveedor_id'] ?? 0) === $tipoId;
                }));
            }
        }

        return [
            'tipo'              => $tipo,
            'tipo_id'           => $tipoId,
            'tipo_value'        => $tipoId !== null ? (string) $tipoId : $tipo,
            'proveedores_rows'  => $rows,
            'tipo_proveedor_options' => $options,
            'legacy'            => $legacy,
        ];
    }
}
