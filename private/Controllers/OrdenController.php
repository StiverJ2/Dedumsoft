<?php
/**
 * ============================================================================
 * ORDEN CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para gestion de ordenes de produccion y operaciones de artesano.
 *
 * Unifica el comportamiento entre:
 * - public/produccion.php (vista HTML de ordenes)
 * - public/artesano_ordenes.php (vista HTML de artesano)
 * - public/api/produccion/ordenes.php (API JSON de ordenes)
 * - public/api/produccion/artesano_ordenes.php (API JSON de artesano ordenes)
 * - public/api/produccion/artesano_consumo.php (API JSON de consumo)
 * - public/api/produccion/artesano_terminada.php (API JSON de terminada)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/OrdenRepository.php';
require_once PRIVATE_PATH . '/Repositories/ArtesanoRepository.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';

class OrdenController extends Controller
{
    /** @var OrdenRepository */
    private $ordenRepo;

    /** @var ArtesanoRepository */
    private $artRepo;

    /** @var CatalogoRepository */
    private $catRepo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->ordenRepo = new OrdenRepository($pdo);
        $this->artRepo   = new ArtesanoRepository($pdo);
        $this->catRepo   = new CatalogoRepository($pdo);
    }

    // ========================================================================
    // PAGE DATA HELPERS
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de produccion/ordenes.
     *
     * @param array $get        $_GET
     * @param bool  $legacy     Modo legacy
     * @param bool  $isOperador Si el usuario es operador (rol 2)
     * @return array<string, mixed> Datos para la vista
     */
    public function pageDataOrdenes(array $get, bool $legacy, bool $isOperador = false): array
    {
        $estado            = $this->toString($get['estado'] ?? '');
        $estado_filtrado   = $estado;
        $ordenes_rows      = [];
        $artesanos_options = [];
        $productos_options = [];
        $prioridades_options = [];
        $estado_options    = [];

        // Cargar estados de orden (usado en filtros)
        try {
            $estado_options = $this->catRepo->obtenerOpciones('estados_orden');
        } catch (Exception $e) {
            error_log('pageDataOrdenes estados error: ' . $e->getMessage());
        }

        // Cargar datos para modo legacy
        if ($legacy) {
            try {
                $ordenes_limit = $estado !== '' ? 200 : 20;
                $ordenes_rows = $this->ordenRepo->listar(0, $ordenes_limit, $estado !== '' ? $estado : null);

                if ($estado !== '') {
                    $ordenes_rows = array_values(array_filter($ordenes_rows, function ($row) use ($estado) {
                        return strcasecmp((string) ($row['estado'] ?? ''), $estado) === 0;
                    }));
                }
                if ($isOperador) {
                    $ordenes_rows = array_values(array_filter($ordenes_rows, function ($row) {
                        return strtolower((string) ($row['estado'] ?? '')) !== 'terminada';
                    }));
                }
            } catch (PDOException $e) {
                error_log('pageDataOrdenes legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            }

            // Cargar artesanos para dropdown de asignacion
            try {
                $artesanos_options = $this->catRepo->obtenerOpciones('artesanos');
            } catch (Exception $e) {
                error_log('pageDataOrdenes artesanos error: ' . $e->getMessage());
            }

            if ($estado !== '') {
                if (ctype_digit($estado)) {
                    foreach ($estado_options as $opt) {
                        if ((int) ($opt['value'] ?? 0) === (int) $estado) {
                            $estado_filtrado = (string) ($opt['label'] ?? '');
                            break;
                        }
                    }
                } else {
                    $estado_filtrado = $estado;
                }
            }

            // Cargar productos para creacion de ordenes
            try {
                $productos_options = $this->catRepo->obtenerOpciones('productos');
            } catch (Exception $e) {
                error_log('pageDataOrdenes productos error: ' . $e->getMessage());
            }

            // Cargar prioridades para creacion de ordenes
            try {
                $prioridades_options = $this->catRepo->obtenerOpciones('prioridades');
            } catch (Exception $e) {
                error_log('pageDataOrdenes prioridades error: ' . $e->getMessage());
            }
        }

        return [
            'estado'              => $estado,
            'estado_filtrado'     => $estado_filtrado,
            'ordenes_rows'        => $ordenes_rows,
            'estado_options'      => $estado_options,
            'artesanos_options'   => $artesanos_options,
            'productos_options'   => $productos_options,
            'prioridades_options' => $prioridades_options,
            'legacy'              => $legacy,
        ];
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de artesano_ordenes.
     *
     * @param int  $artesanoId ID del artesano
     * @param bool $legacy     Modo legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageDataArtesanoOrdenes(int $artesanoId, bool $legacy): array
    {
        $ordenes_rows   = [];
        $estados_options = [];
        $artesano_nombre = '';

        // Obtener nombre del artesano para mostrar en encabezado
        if ($artesanoId) {
            try {
                $artesano = $this->artRepo->obtenerPorId($artesanoId);
                if ($artesano) {
                    $artesano_nombre = trim($artesano['nombre'] . ' ' . $artesano['apellido']);
                }
            } catch (PDOException $e) {
                error_log('pageDataArtesanoOrdenes artesano error: ' . $e->getMessage());
            }
        }

        // Cargar estados de orden para dropdown
        try {
            $estados_options = $this->catRepo->obtenerOpciones('estados_orden');
        } catch (Exception $e) {
            error_log('pageDataArtesanoOrdenes estados error: ' . $e->getMessage());
        }

        // Cargar ordenes del artesano para modo legacy
        if ($legacy && $artesanoId) {
            try {
                $ordenes_rows = $this->artRepo->obtenerOrdenes($artesanoId, 0, 50);
            } catch (PDOException $e) {
                error_log('pageDataArtesanoOrdenes ordenes error: ' . $e->getMessage());
            }
        }

        return [
            'artesano_nombre' => $artesano_nombre,
            'estados_options' => $estados_options,
            'ordenes_rows'    => $ordenes_rows,
        ];
    }

    // ========================================================================
    // LISTAR / OBTENER (para APIs)
    // ========================================================================

    /**
     * Lista ordenes de produccion con filtros.
     *
     * @param int         $offset
     * @param int         $limit
     * @param string|null $estado
     * @param bool        $isOperador
     * @return array<string, mixed>
     */
    public function listarOrdenes(int $offset = 0, int $limit = 50, ?string $estado = null, bool $isOperador = false): array
    {
        try {
            $rows = $this->ordenRepo->listar($offset, $limit, $estado);

            // Los operadores no ven ordenes terminadas
            if ($isOperador) {
                $rows = array_values(array_filter($rows, function ($row) {
                    return strtolower($row['estado'] ?? '') !== 'terminada';
                }));
            }

            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('OrdenController::listarOrdenes error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener una orden por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerOrden(int $id): array
    {
        try {
            $row = $this->ordenRepo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Orden no encontrada.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('OrdenController::obtenerOrden error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Listar ordenes asignadas a un artesano.
     *
     * @param int $artesanoId
     * @param int $offset
     * @param int $limit
     * @return array<string, mixed>
     */
    public function listarArtesanoOrdenes(int $artesanoId, int $offset = 0, int $limit = 50): array
    {
        if ($artesanoId <= 0) {
            return $this->error('artesano_id requerido.', 400);
        }

        try {
            $rows = $this->artRepo->obtenerOrdenes($artesanoId, $offset, $limit);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('OrdenController::listarArtesanoOrdenes error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // MUTACIONES (para APIs)
    // ========================================================================

    /**
     * Crear una orden de produccion.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearOrden(array $input): array
    {
        $validation = $this->validateRequired($input, ['producto_id', 'cantidad']);
        if ($validation !== null) {
            return $validation;
        }

        $producto_id   = $this->toInt($input['producto_id']);
        $cantidad      = $this->toInt($input['cantidad']);
        $artesano_id   = isset($input['artesano_id']) && $input['artesano_id'] > 0 ? $this->toInt($input['artesano_id']) : null;
        $prioridad_id  = isset($input['prioridad_id']) && $input['prioridad_id'] > 0 ? $this->toInt($input['prioridad_id']) : 2;
        $observaciones = isset($input['observaciones']) && $input['observaciones'] !== '' ? $this->toString($input['observaciones']) : null;

        try {
            $new_id = $this->ordenRepo->crear($producto_id, $cantidad, $artesano_id, $prioridad_id, $observaciones);
            return $this->success(['id' => $new_id], 'Orden creada.', 201);
        } catch (PDOException $e) {
            error_log('OrdenController::crearOrden error: ' . $e->getMessage());
            return $this->error('Error al crear la orden.', 500);
        }
    }

    /**
     * Asignar un artesano a una orden.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function asignarArtesano(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id          = $this->toInt($input['id']);
        $artesano_id = isset($input['artesano_id']) && $input['artesano_id'] > 0 ? $this->toInt($input['artesano_id']) : 0;

        if ($artesano_id <= 0) {
            return $this->error('artesano_id es requerido.', 400);
        }

        try {
            $ok = $this->ordenRepo->asignarArtesano($id, $artesano_id);

            if (!$ok) {
                return $this->error('No se pudo asignar el artesano.', 422);
            }

            return $this->success(null, 'Orden actualizada.', 200);
        } catch (PDOException $e) {
            error_log('OrdenController::asignarArtesano error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Orden no encontrada.' : 'Error al asignar artesano.',
                $code
            );
        }
    }

    /**
     * Cambiar el estado de una orden.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function cambiarEstadoOrden(array $input): array
    {
        $validation = $this->validateRequired($input, ['id', 'estado_id']);
        if ($validation !== null) {
            return $validation;
        }

        $id        = $this->toInt($input['id']);
        $estado_id = $this->toInt($input['estado_id']);

        try {
            $result = $this->ordenRepo->cambiarEstado($id, $estado_id);

            if (!$result['success']) {
                return $this->error($result['mensaje'], 400);
            }

            return $this->success(null, $result['mensaje'], 200);
        } catch (PDOException $e) {
            error_log('OrdenController::cambiarEstadoOrden error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Registrar consumo de material en una orden.
     *
     * @param array    $input
     * @param int|null $usuarioId
     * @return array<string, mixed>
     */
    public function registrarConsumo(array $input, ?int $usuarioId = null): array
    {
        $validation = $this->validateRequired($input, ['orden_id', 'tipo_material', 'material_id', 'cantidad']);
        if ($validation !== null) {
            return $validation;
        }

        $orden_id      = $this->toInt($input['orden_id']);
        $tipo_material = strtolower(trim((string) $input['tipo_material']));
        $material_id   = $this->toInt($input['material_id']);
        $cantidad      = (float) $input['cantidad'];

        if (!in_array($tipo_material, ['oro', 'insumo'], true)) {
            return $this->error('tipo_material debe ser "oro" o "insumo".', 400);
        }

        if ($cantidad <= 0) {
            return $this->error('cantidad debe ser mayor a 0.', 400);
        }

        try {
            $result = $this->artRepo->registrarConsumo($orden_id, $tipo_material, $material_id, $cantidad, $usuarioId);

            if (!$result['success']) {
                return $this->error($result['mensaje'], 400);
            }

            return $this->success(['id' => (int) $result['consumo_id']], $result['mensaje'], 201);
        } catch (PDOException $e) {
            error_log('OrdenController::registrarConsumo error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Registrar pieza terminada.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function registrarTerminada(array $input): array
    {
        $validation = $this->validateRequired($input, ['orden_id', 'peso_final']);
        if ($validation !== null) {
            return $validation;
        }

        $orden_id      = $this->toInt($input['orden_id']);
        $peso_final    = (float) $input['peso_final'];
        $tiempo_real   = isset($input['tiempo_real']) && $input['tiempo_real'] !== '' ? (float) $input['tiempo_real'] : null;
        $calidad_id    = isset($input['calidad_id']) && $input['calidad_id'] !== '' ? $this->toInt($input['calidad_id']) : null;
        $observaciones = $input['observaciones'] ?? null;
        $observaciones = ($observaciones === '') ? null : $observaciones;

        if ($peso_final <= 0) {
            return $this->error('peso_final debe ser mayor a 0.', 400);
        }

        try {
            $result = $this->artRepo->marcarTerminada($orden_id, $peso_final, $tiempo_real, $calidad_id, $observaciones);

            if (!$result['success']) {
                return $this->error($result['mensaje'], 400);
            }

            return $this->success([
                'id'               => (int) $result['creacion_id'],
                'costo_materiales' => $result['costo_materiales'],
            ], $result['mensaje'], 201);
        } catch (PDOException $e) {
            error_log('OrdenController::registrarTerminada error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }
}
