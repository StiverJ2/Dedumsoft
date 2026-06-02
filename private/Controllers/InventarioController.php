<?php
/**
 * ============================================================================
 * INVENTARIO CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para gestion de inventario (oro, insumos, maquinaria).
 *
 * Unifica el comportamiento entre:
 * - public/inventario_oro.php (vista HTML)
 * - public/inventario_insumos.php (vista HTML)
 * - public/inventario_maquinaria.php (vista HTML)
 * - public/api/inventario/oro.php (API JSON)
 * - public/api/inventario/insumos.php (API JSON)
 * - public/api/inventario/maquinaria.php (API JSON)
 * - public/api/inventario/tipos_maquinaria.php (API JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/InventarioOroRepository.php';
require_once PRIVATE_PATH . '/Repositories/InventarioInsumosRepository.php';
require_once PRIVATE_PATH . '/Repositories/InventarioMaquinariaRepository.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/ProveedorRepository.php';
require_once PRIVATE_PATH . '/Repositories/UbicacionRepository.php';

class InventarioController extends Controller
{
    /** @var InventarioOroRepository */
    private $oroRepo;

    /** @var InventarioInsumosRepository */
    private $insumosRepo;

    /** @var InventarioMaquinariaRepository */
    private $maquinariaRepo;

    /** @var CatalogoRepository */
    private $catRepo;

    /** @var ProveedorRepository */
    private $provRepo;

    /** @var UbicacionRepository */
    private $ubicRepo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->oroRepo       = new InventarioOroRepository($pdo);
        $this->insumosRepo   = new InventarioInsumosRepository($pdo);
        $this->maquinariaRepo = new InventarioMaquinariaRepository($pdo);
        $this->catRepo       = new CatalogoRepository($pdo);
        $this->provRepo      = new ProveedorRepository($pdo);
        $this->ubicRepo      = new UbicacionRepository($pdo);
    }

    // ========================================================================
    // ORO
    // ========================================================================

    /**
     * Listar inventario de oro.
     *
     * @param int       $offset
     * @param int       $limit
     * @param int|null  $tipoId
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listarOro(int $offset = 0, int $limit = 50, ?int $tipoId = null, ?bool $activo = true): array
    {
        try {
            $rows = $this->oroRepo->listar($offset, $limit, $tipoId, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::listarOro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un registro de oro por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerOro(int $id): array
    {
        try {
            $row = $this->oroRepo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Registro de oro no encontrado.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::obtenerOro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear un registro de oro.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearOro(array $input): array
    {
        $validation = $this->validateRequired($input, ['tipo_oro_id', 'peso_gramos', 'precio_gramo']);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $ok = $this->oroRepo->crear(
                $this->toInt($input['tipo_oro_id']),
                $this->toFloat($input['peso_gramos']),
                $this->toFloat($input['precio_gramo']),
                isset($input['proveedor_id']) ? $this->toInt($input['proveedor_id']) : null,
                $this->toString($input['fecha_ingreso'] ?? null) ?: null,
                $this->toString($input['ubicacion'] ?? null) ?: null,
                isset($input['pureza']) ? $this->toFloat($input['pureza']) : null
            );

            if (!$ok) {
                return $this->error('No se pudo crear el registro.', 422);
            }

            return $this->success(null, 'Registro creado.', 201);
        } catch (PDOException $e) {
            error_log('InventarioController::crearOro error: ' . $e->getMessage());
            return $this->error('Error al crear registro.', 500);
        }
    }

    /**
     * Actualizar un registro de oro.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarOro(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);

        try {
            $ok = $this->oroRepo->actualizar(
                $id,
                isset($input['tipo_oro_id']) ? $this->toInt($input['tipo_oro_id']) : null,
                isset($input['peso_gramos']) ? $this->toFloat($input['peso_gramos']) : null,
                isset($input['precio_gramo']) ? $this->toFloat($input['precio_gramo']) : null,
                isset($input['proveedor_id']) ? $this->toInt($input['proveedor_id']) : null,
                $this->toString($input['fecha_ingreso'] ?? null) ?: null,
                $this->toString($input['ubicacion'] ?? null) ?: null,
                isset($input['pureza']) ? $this->toFloat($input['pureza']) : null
            );

            if (!$ok) {
                return $this->error('No se pudo actualizar el registro.', 422);
            }

            return $this->success(null, 'Registro actualizado.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::actualizarOro error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Registro no encontrado.' : 'Error al actualizar.',
                $code
            );
        }
    }

    /**
     * Eliminar un registro de oro.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminarOro(int $id): array
    {
        try {
            $ok = $this->oroRepo->eliminar($id);

            if (!$ok) {
                return $this->error('No se pudo eliminar el registro.', 422);
            }

            return $this->success(null, 'Registro eliminado.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::eliminarOro error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Registro no encontrado.' : 'Error al eliminar.',
                $code
            );
        }
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de inventario de oro.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed>
     */
    public function pageDataOro(array $get, bool $legacy): array
    {
        $oro_tipo = $this->toString($get['oro_tipo'] ?? '');
        $oro_tipo_id = null;
        $oro_rows = [];
        $proveedor_options = [];
        $oro_tipo_options = [];

        // Cargar proveedores para dropdown
        try {
            $proveedor_options = $this->provRepo->listar(0, 1000, null, true);
        } catch (PDOException $e) {
            error_log('inventario oro proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        // Obtener opciones de tipos de oro desde catalogo
        try {
            $oro_tipo_options = $this->catRepo->obtenerOpciones('tipos_oro');
        } catch (Exception $e) {
            error_log('tipos oro error: ' . $e->getMessage());
            $oro_tipo_options = [
                ['value' => 1, 'label' => '10k'],
                ['value' => 2, 'label' => '14k'],
                ['value' => 3, 'label' => '18k'],
                ['value' => 4, 'label' => '22k'],
                ['value' => 5, 'label' => '24k']
            ];
        }

        if ($legacy && $oro_tipo !== '') {
            if (ctype_digit($oro_tipo) && (int) $oro_tipo > 0) {
                $oro_tipo_id = (int) $oro_tipo;
            } else {
                foreach ($oro_tipo_options as $opt) {
                    if (strcasecmp((string) ($opt['label'] ?? ''), $oro_tipo) === 0) {
                        $oro_tipo_id = (int) ($opt['value'] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($legacy) {
            try {
                $oro_limit = $oro_tipo_id !== null ? 200 : 20;
                $oro_rows = $this->oroRepo->listar(0, $oro_limit, $oro_tipo_id, true);

                if ($oro_tipo_id !== null) {
                    $oro_rows = array_values(array_filter($oro_rows, function ($row) use ($oro_tipo_id) {
                        return (int) ($row['tipo_oro_id'] ?? 0) === $oro_tipo_id;
                    }));
                }
            } catch (PDOException $e) {
                error_log('inventario legacy oro error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            }
        }

        $oro_tipo_value = $oro_tipo_id !== null ? (string) $oro_tipo_id : $oro_tipo;

        return [
            'oro_tipo'            => $oro_tipo,
            'oro_tipo_id'         => $oro_tipo_id,
            'oro_tipo_value'      => $oro_tipo_value,
            'oro_rows'            => $oro_rows,
            'proveedor_options'   => $proveedor_options,
            'oro_tipo_options'    => $oro_tipo_options,
            'legacy'              => $legacy,
        ];
    }

    // ========================================================================
    // INSUMOS
    // ========================================================================

    /**
     * Listar inventario de insumos.
     *
     * @param int         $offset
     * @param int         $limit
     * @param string|null $categoria
     * @param bool|null   $stockBajo
     * @param bool|null   $activo
     * @return array<string, mixed>
     */
    public function listarInsumos(int $offset = 0, int $limit = 50, ?string $categoria = null, ?bool $stockBajo = false, ?bool $activo = true): array
    {
        try {
            $rows = $this->insumosRepo->listar($offset, $limit, $categoria, $stockBajo, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::listarInsumos error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un insumo por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerInsumos(int $id): array
    {
        try {
            $row = $this->insumosRepo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Insumo no encontrado.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::obtenerInsumos error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear un insumo.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearInsumos(array $input): array
    {
        $validation = $this->validateRequired($input, ['nombre', 'categoria', 'unidad_medida', 'precio_unitario']);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $ok = $this->insumosRepo->crear(
                $this->toString($input['nombre']),
                $this->toString($input['categoria']),
                $this->toString($input['unidad_medida']),
                $this->toFloat($input['precio_unitario']),
                $this->toString($input['descripcion'] ?? null) ?: null,
                isset($input['cantidad']) ? $this->toFloat($input['cantidad']) : null,
                isset($input['stock_minimo']) ? $this->toFloat($input['stock_minimo']) : null,
                isset($input['proveedor_id']) ? $this->toInt($input['proveedor_id']) : null,
                isset($input['ubicacion_id']) ? $this->toInt($input['ubicacion_id']) : null
            );

            if (!$ok) {
                return $this->error('No se pudo crear el insumo.', 422);
            }

            return $this->success(null, 'Insumo creado.', 201);
        } catch (PDOException $e) {
            error_log('InventarioController::crearInsumos error: ' . $e->getMessage());
            return $this->error('Error al crear insumo.', 500);
        }
    }

    /**
     * Actualizar un insumo.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarInsumos(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);

        try {
            $ok = $this->insumosRepo->actualizar(
                $id,
                isset($input['nombre']) ? $this->toString($input['nombre']) : null,
                isset($input['categoria']) ? $this->toString($input['categoria']) : null,
                isset($input['descripcion']) ? $this->toString($input['descripcion']) : null,
                isset($input['cantidad']) ? $this->toFloat($input['cantidad']) : null,
                isset($input['unidad_medida']) ? $this->toString($input['unidad_medida']) : null,
                isset($input['precio_unitario']) ? $this->toFloat($input['precio_unitario']) : null,
                isset($input['stock_minimo']) ? $this->toFloat($input['stock_minimo']) : null,
                isset($input['proveedor_id']) ? $this->toInt($input['proveedor_id']) : null,
                isset($input['ubicacion_id']) ? $this->toInt($input['ubicacion_id']) : null
            );

            if (!$ok) {
                return $this->error('No se pudo actualizar el insumo.', 422);
            }

            return $this->success(null, 'Insumo actualizado.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::actualizarInsumos error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Insumo no encontrado.' : 'Error al actualizar.',
                $code
            );
        }
    }

    /**
     * Eliminar un insumo.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminarInsumos(int $id): array
    {
        try {
            $ok = $this->insumosRepo->eliminar($id);

            if (!$ok) {
                return $this->error('No se pudo eliminar el insumo.', 422);
            }

            return $this->success(null, 'Insumo eliminado.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::eliminarInsumos error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrado') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Insumo no encontrado.' : 'Error al eliminar.',
                $code
            );
        }
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de insumos.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed>
     */
    public function pageDataInsumos(array $get, bool $legacy): array
    {
        $insumo_categoria = $this->toString($get['insumo_categoria'] ?? '');
        $insumo_stock_bajo = isset($get['insumo_stock_bajo']) && $get['insumo_stock_bajo'] !== '0';
        $insumo_rows = [];
        $categoria_options = [];
        $proveedor_options = [];

        // Cargar opciones de categorias
        try {
            $categoria_options = $this->insumosRepo->obtenerCategorias();
        } catch (PDOException $e) {
            error_log('inventario categorias error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        // Cargar proveedores para dropdown
        try {
            $proveedor_options = $this->provRepo->listar(0, 1000, null, true);
        } catch (PDOException $e) {
            error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        if ($legacy) {
            try {
                $insumo_limit = ($insumo_categoria !== '' || $insumo_stock_bajo) ? 200 : 20;
                $insumo_rows = $this->insumosRepo->listar(0, $insumo_limit, $insumo_categoria !== '' ? $insumo_categoria : null, $insumo_stock_bajo, true);

                if ($insumo_categoria !== '') {
                    $insumo_rows = array_values(array_filter($insumo_rows, function ($row) use ($insumo_categoria) {
                        return strcasecmp(trim((string) ($row['categoria'] ?? '')), $insumo_categoria) === 0;
                    }));
                }
                if ($insumo_stock_bajo) {
                    $insumo_rows = array_values(array_filter($insumo_rows, function ($row) {
                        $cantidad = isset($row['cantidad']) ? (float) $row['cantidad'] : 0;
                        $stock_minimo = isset($row['stock_minimo']) ? (float) $row['stock_minimo'] : 0;
                        return $cantidad <= $stock_minimo;
                    }));
                }
            } catch (PDOException $e) {
                error_log('inventario legacy insumos error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            }
        }

        return [
            'insumo_categoria'    => $insumo_categoria,
            'insumo_stock_bajo'   => $insumo_stock_bajo,
            'insumo_rows'         => $insumo_rows,
            'categoria_options'   => $categoria_options,
            'proveedor_options'   => $proveedor_options,
            'legacy'              => $legacy,
        ];
    }

    // ========================================================================
    // MAQUINARIA
    // ========================================================================

    /**
     * Listar inventario de maquinaria.
     *
     * @param int       $offset
     * @param int       $limit
     * @param int|null  $estadoId
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listarMaquinaria(int $offset = 0, int $limit = 50, ?int $estadoId = null, ?bool $activo = true): array
    {
        try {
            $rows = $this->maquinariaRepo->listar($offset, $limit, $estadoId, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::listarMaquinaria error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un equipo de maquinaria por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerMaquinaria(int $id): array
    {
        try {
            $row = $this->maquinariaRepo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Maquinaria no encontrada.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::obtenerMaquinaria error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear un equipo de maquinaria.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearMaquinaria(array $input): array
    {
        $validation = $this->validateRequired($input, ['nombre', 'sku', 'tipo_maquinaria_id']);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $ok = $this->maquinariaRepo->crear(
                $this->toString($input['nombre']),
                $this->toString($input['sku']),
                $this->toInt($input['tipo_maquinaria_id']),
                $this->toString($input['marca'] ?? null) ?: null,
                $this->toString($input['modelo'] ?? null) ?: null,
                $this->toString($input['fecha_compra'] ?? null) ?: null,
                isset($input['valor_compra']) ? $this->toFloat($input['valor_compra']) : null,
                isset($input['estado_id']) ? $this->toInt($input['estado_id']) : null,
                isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? $this->toInt($input['ubicacion_id']) : null
            );

            if (!$ok) {
                return $this->error('No se pudo crear la maquinaria.', 422);
            }

            return $this->success(null, 'Maquinaria creada.', 201);
        } catch (PDOException $e) {
            error_log('InventarioController::crearMaquinaria error: ' . $e->getMessage());
            return $this->error('Error al crear maquinaria.', 500);
        }
    }

    /**
     * Actualizar un equipo de maquinaria.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarMaquinaria(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);

        try {
            $ok = $this->maquinariaRepo->actualizar(
                $id,
                isset($input['nombre']) ? $this->toString($input['nombre']) : null,
                isset($input['sku']) ? $this->toString($input['sku']) : null,
                isset($input['tipo_maquinaria_id']) ? $this->toInt($input['tipo_maquinaria_id']) : null,
                $this->toString($input['marca'] ?? null) ?: null,
                $this->toString($input['modelo'] ?? null) ?: null,
                $this->toString($input['fecha_compra'] ?? null) ?: null,
                isset($input['valor_compra']) ? $this->toFloat($input['valor_compra']) : null,
                isset($input['estado_id']) ? $this->toInt($input['estado_id']) : null,
                $this->toString($input['ultima_mantenimiento'] ?? null) ?: null,
                $this->toString($input['proxima_mantenimiento'] ?? null) ?: null,
                isset($input['ubicacion_id']) && $input['ubicacion_id'] !== '' ? $this->toInt($input['ubicacion_id']) : null,
                isset($input['activo']) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN) : null
            );

            if (!$ok) {
                return $this->error('No se pudo actualizar la maquinaria.', 422);
            }

            return $this->success(null, 'Maquinaria actualizada.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::actualizarMaquinaria error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Maquinaria no encontrada.' : 'Error al actualizar.',
                $code
            );
        }
    }

    /**
     * Eliminar un equipo de maquinaria.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminarMaquinaria(int $id): array
    {
        try {
            $ok = $this->maquinariaRepo->eliminar($id);

            if (!$ok) {
                return $this->error('No se pudo eliminar la maquinaria.', 422);
            }

            return $this->success(null, 'Maquinaria eliminada.', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::eliminarMaquinaria error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Maquinaria no encontrada.' : 'Error al eliminar.',
                $code
            );
        }
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de maquinaria.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed>
     */
    public function pageDataMaquinaria(array $get, bool $legacy): array
    {
        $maq_estado_raw = $this->toString($get['maq_estado_id'] ?? '');
        $maq_estado_id = null;
        $maq_rows = [];
        $proveedor_options = [];
        $ubicacion_options = [];
        $tipo_maquinaria_options = [];
        $maq_estado_options = [];

        // Cargar proveedores para dropdown
        try {
            $proveedor_options = $this->provRepo->listar(0, 1000, null, true);
        } catch (PDOException $e) {
            error_log('inventario proveedores error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        // Cargar ubicaciones para dropdown
        try {
            $ubicacion_options = $this->ubicRepo->listar(0, 1000, null, true);
        } catch (PDOException $e) {
            error_log('inventario ubicaciones error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        // Cargar tipos de maquinaria
        try {
            $tipo_maquinaria_options = $this->catRepo->obtenerTiposMaquinaria();
        } catch (PDOException $e) {
            error_log('inventario tipos_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        // Obtener opciones de estados de maquinaria
        try {
            $maq_estado_options = $this->catRepo->obtenerOpciones('estados_maquinaria');
        } catch (Exception $e) {
            error_log('inventario estados_maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        }

        if ($maq_estado_raw !== '') {
            if (ctype_digit($maq_estado_raw) && (int) $maq_estado_raw > 0) {
                $maq_estado_id = (int) $maq_estado_raw;
            } else {
                foreach ($maq_estado_options as $opt) {
                    if (strcasecmp((string) ($opt['label'] ?? ''), $maq_estado_raw) === 0) {
                        $maq_estado_id = (int) ($opt['value'] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($legacy) {
            try {
                $maq_limit = $maq_estado_id !== null ? 200 : 20;
                $maq_rows = $this->maquinariaRepo->listar(0, $maq_limit, $maq_estado_id, true);

                if ($maq_estado_id !== null) {
                    $maq_rows = array_values(array_filter($maq_rows, function ($row) use ($maq_estado_id) {
                        return (int) ($row['estado_id'] ?? 0) === $maq_estado_id;
                    }));
                }
            } catch (PDOException $e) {
                error_log('inventario legacy maquinaria error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            }
        }

        return [
            'maq_estado_raw'          => $maq_estado_raw,
            'maq_estado_id'           => $maq_estado_id,
            'maq_rows'                => $maq_rows,
            'proveedor_options'       => $proveedor_options,
            'ubicacion_options'       => $ubicacion_options,
            'tipo_maquinaria_options' => $tipo_maquinaria_options,
            'maq_estado_options'      => $maq_estado_options,
            'legacy'                  => $legacy,
        ];
    }

    // ========================================================================
    // CATALOGOS AUXILIARES
    // ========================================================================

    /**
     * Listar tipos de maquinaria.
     *
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listarTiposMaquinaria(?bool $activo = true): array
    {
        try {
            $rows = $this->catRepo->obtenerTiposMaquinaria($activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('InventarioController::listarTiposMaquinaria error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }
}
