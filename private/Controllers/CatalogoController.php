<?php
/**
 * ============================================================================
 * CATALOGO CONTROLLER
 * ============================================================================
 *
 * Logica de negocio para catalogos, ubicaciones, especialidades y opciones.
 *
 * Unifica el comportamiento entre:
 * - public/ubicaciones.php, public/configuracion.php, public/catalogos.php (vistas HTML)
 * - public/api/catalogos/*.php (API JSON)
 * - public/api/inventario/tipos_maquinaria.php (API JSON)
 *
 * @package Dedumsoft\Controllers
 */

require_once __DIR__ . '/Controller.php';
require_once PRIVATE_PATH . '/Repositories/CatalogoRepository.php';
require_once PRIVATE_PATH . '/Repositories/UbicacionRepository.php';

class CatalogoController extends Controller
{
    /** @var CatalogoRepository */
    private $catRepo;

    /** @var UbicacionRepository */
    private $ubicRepo;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->catRepo  = new CatalogoRepository($pdo);
        $this->ubicRepo = new UbicacionRepository($pdo);
    }

    // ========================================================================
    // UBICACIONES
    // ========================================================================

    /**
     * Listar ubicaciones con filtros.
     *
     * @param int       $offset
     * @param int       $limit
     * @param int|null  $areaId
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listarUbicaciones(int $offset = 0, int $limit = 100, ?int $areaId = null, ?bool $activo = true): array
    {
        try {
            $rows = $this->ubicRepo->listar($offset, $limit, $areaId, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::listarUbicaciones error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener una ubicacion por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerUbicacion(int $id): array
    {
        try {
            $row = $this->ubicRepo->obtenerPorId($id);
            if ($row === null) {
                return $this->error('Ubicacion no encontrada.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::obtenerUbicacion error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear una ubicacion.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearUbicacion(array $input): array
    {
        $validation = $this->validateRequired($input, ['nombre']);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $id = $this->ubicRepo->crear(
                $this->toString($input['nombre']),
                $this->toString($input['descripcion'] ?? null) ?: null,
                isset($input['area_id']) ? $this->toInt($input['area_id']) : null
            );

            if ($id <= 0) {
                return $this->error('No se pudo crear la ubicacion.', 422);
            }

            return $this->success(['id' => $id], 'Ubicacion creada.', 201);
        } catch (PDOException $e) {
            error_log('CatalogoController::crearUbicacion error: ' . $e->getMessage());
            return $this->error('Error al crear ubicacion.', 500);
        }
    }

    /**
     * Actualizar una ubicacion (PATCH parcial).
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarUbicacion(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);

        try {
            $ok = $this->ubicRepo->actualizar(
                $id,
                isset($input['nombre']) ? $this->toString($input['nombre']) : null,
                isset($input['descripcion']) ? $this->toString($input['descripcion']) : null,
                isset($input['area_id']) ? $this->toInt($input['area_id']) : null,
                isset($input['activo']) ? (bool) $input['activo'] : null
            );

            if (!$ok) {
                return $this->error('No se pudo actualizar la ubicacion.', 422);
            }

            return $this->success(null, 'Ubicacion actualizada.', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::actualizarUbicacion error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Ubicacion no encontrada.' : 'Error al actualizar.',
                $code
            );
        }
    }

    /**
     * Eliminar (soft-delete) una ubicacion.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminarUbicacion(int $id): array
    {
        try {
            $ok = $this->ubicRepo->eliminar($id);

            if (!$ok) {
                return $this->error('No se pudo eliminar la ubicacion.', 422);
            }

            return $this->success(null, 'Ubicacion eliminada.', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::eliminarUbicacion error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Ubicacion no encontrada.' : 'Error al eliminar.',
                $code
            );
        }
    }

    // ========================================================================
    // ESPECIALIDADES
    // ========================================================================

    /**
     * Listar especialidades.
     *
     * @param int       $offset
     * @param int       $limit
     * @param bool|null $activo
     * @return array<string, mixed>
     */
    public function listarEspecialidades(int $offset = 0, int $limit = 100, ?bool $activo = true): array
    {
        try {
            $rows = $this->catRepo->listarEspecialidades($offset, $limit, $activo);
            return $this->success($rows, 'OK', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::listarEspecialidades error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener una especialidad por ID.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function obtenerEspecialidad(int $id): array
    {
        try {
            $row = $this->catRepo->obtenerEspecialidadPorId($id);
            if ($row === null) {
                return $this->error('Especialidad no encontrada.', 404);
            }
            return $this->success($row, 'OK', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::obtenerEspecialidad error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear una especialidad.
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function crearEspecialidad(array $input): array
    {
        $validation = $this->validateRequired($input, ['nombre']);
        if ($validation !== null) {
            return $validation;
        }

        $nombre = $this->toString($input['nombre']);
        $descripcion = $this->toString($input['descripcion'] ?? null) ?: null;

        try {
            $id = $this->catRepo->crearEspecialidad($nombre, $descripcion);
            if ($id <= 0) {
                return $this->error('Error al crear especialidad.', 500);
            }
            return $this->success(['id' => $id], 'Especialidad creada.', 201);
        } catch (PDOException $e) {
            error_log('CatalogoController::crearEspecialidad error: ' . $e->getMessage());
            if ($e->getCode() == 23505) {
                return $this->error('La especialidad ya existe.', 409);
            }
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Actualizar una especialidad (PATCH parcial).
     *
     * @param array $input
     * @return array<string, mixed>
     */
    public function actualizarEspecialidad(array $input): array
    {
        if (!isset($input['id'])) {
            return $this->error('ID requerido.', 400);
        }

        $id = $this->toInt($input['id']);
        $nombre = isset($input['nombre']) ? $this->toString($input['nombre']) : null;
        $descripcion = isset($input['descripcion']) ? $this->toString($input['descripcion']) : null;
        $activo = array_key_exists('activo', $input) ? filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $nombre = ($nombre !== null && $nombre !== '') ? $nombre : null;
        $descripcion = ($descripcion !== null && $descripcion !== '') ? $descripcion : null;

        if ($nombre === null && $descripcion === null && $activo === null) {
            return $this->error('No hay campos para actualizar.', 400);
        }

        try {
            $ok = $this->catRepo->actualizarEspecialidad($id, $nombre, $descripcion, $activo);
            if (!$ok) {
                return $this->error('No se pudo actualizar la especialidad.', 422);
            }
            return $this->success(null, 'Especialidad actualizada.', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::actualizarEspecialidad error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.',
                $code
            );
        }
    }

    /**
     * Eliminar (soft-delete) una especialidad.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    public function eliminarEspecialidad(int $id): array
    {
        try {
            $ok = $this->catRepo->eliminarEspecialidad($id);
            if (!$ok) {
                return $this->error('No se pudo eliminar la especialidad.', 422);
            }
            return $this->success(null, 'Especialidad eliminada.', 200);
        } catch (PDOException $e) {
            error_log('CatalogoController::eliminarEspecialidad error: ' . $e->getMessage());
            $code = strpos($e->getMessage(), 'no encontrada') !== false ? 404 : 500;
            return $this->error(
                $code === 404 ? 'Especialidad no encontrada.' : 'Error interno del servidor.',
                $code
            );
        }
    }

    // ========================================================================
    // MAESTROS / CATALOGOS GENERICOS
    // ========================================================================

    /**
     * Listar registros de un catalogo maestro.
     *
     * @param string      $catalog
     * @param int|null    $id
     * @param int         $offset
     * @param int         $limit
     * @param string|null $activoRaw
     * @return array<string, mixed>
     */
    public function listarMaestros(string $catalog, ?int $id = null, int $offset = 0, int $limit = 200, ?string $activoRaw = null): array
    {
        try {
            $rows = $this->catRepo->obtenerMaestros($catalog, $id, $offset, $limit, $activoRaw);
            return $this->success($rows, 'OK', 200);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::listarMaestros error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtener un registro de catalogo maestro por ID.
     *
     * @param string $catalog
     * @param int    $id
     * @return array<string, mixed>
     */
    public function obtenerMaestro(string $catalog, int $id): array
    {
        try {
            $rows = $this->catRepo->obtenerMaestros($catalog, $id, 0, 1, null);
            if (empty($rows)) {
                return $this->error('Registro no encontrado.', 404);
            }
            return $this->success($rows[0], 'OK', 200);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::obtenerMaestro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Crear un registro en un catalogo maestro.
     *
     * @param string $catalog
     * @param array  $input
     * @return array<string, mixed>
     */
    public function crearMaestro(string $catalog, array $input): array
    {
        try {
            $id = $this->catRepo->crearMaestro($catalog, $input);
            return $this->success(['id' => $id], 'Registro creado.', 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::crearMaestro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Actualizar un registro de catalogo maestro (PATCH parcial).
     *
     * @param string $catalog
     * @param array  $input
     * @return array<string, mixed>
     */
    public function actualizarMaestro(string $catalog, array $input): array
    {
        if (!isset($input['id']) || (int) $input['id'] <= 0) {
            return $this->error('ID requerido.', 400);
        }

        $id = (int) $input['id'];

        try {
            $ok = $this->catRepo->actualizarMaestro($catalog, $id, $input);
            if (!$ok) {
                return $this->error('No se pudo actualizar el registro.', 422);
            }
            return $this->success(null, 'Registro actualizado.', 200);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::actualizarMaestro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Eliminar (soft-delete) un registro de catalogo maestro.
     *
     * @param string $catalog
     * @param int    $id
     * @return array<string, mixed>
     */
    public function eliminarMaestro(string $catalog, int $id): array
    {
        try {
            $ok = $this->catRepo->eliminarMaestro($catalog, $id);
            if (!$ok) {
                return $this->error('No se pudo eliminar el registro.', 422);
            }
            return $this->success(null, 'Registro eliminado.', 200);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::eliminarMaestro error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    /**
     * Alias: listarCatalogo → listarMaestros.
     *
     * @param string      $catalog
     * @param int|null    $id
     * @param int         $offset
     * @param int         $limit
     * @param string|null $activoRaw
     * @return array<string, mixed>
     */
    public function listarCatalogo(string $catalog, ?int $id = null, int $offset = 0, int $limit = 200, ?string $activoRaw = null): array
    {
        return $this->listarMaestros($catalog, $id, $offset, $limit, $activoRaw);
    }

    /**
     * Alias: obtenerCatalogo → obtenerMaestro.
     *
     * @param string $catalog
     * @param int    $id
     * @return array<string, mixed>
     */
    public function obtenerCatalogo(string $catalog, int $id): array
    {
        return $this->obtenerMaestro($catalog, $id);
    }

    /**
     * Alias: crearCatalogo → crearMaestro.
     *
     * @param string $catalog
     * @param array  $input
     * @return array<string, mixed>
     */
    public function crearCatalogo(string $catalog, array $input): array
    {
        return $this->crearMaestro($catalog, $input);
    }

    /**
     * Alias: actualizarCatalogo → actualizarMaestro.
     *
     * @param string $catalog
     * @param array  $input
     * @return array<string, mixed>
     */
    public function actualizarCatalogo(string $catalog, array $input): array
    {
        return $this->actualizarMaestro($catalog, $input);
    }

    /**
     * Alias: eliminarCatalogo → eliminarMaestro.
     *
     * @param string $catalog
     * @param int    $id
     * @return array<string, mixed>
     */
    public function eliminarCatalogo(string $catalog, int $id): array
    {
        return $this->eliminarMaestro($catalog, $id);
    }

    // ========================================================================
    // TIPOS DE MAQUINARIA
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
            error_log('CatalogoController::listarTiposMaquinaria error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // OPCIONES
    // ========================================================================

    /**
     * Obtener opciones para dropdowns segun tipos permitidos.
     *
     * @param array $requestedTypes
     * @return array<string, mixed>
     */
    public function listarOpciones(array $requestedTypes): array
    {
        try {
            $opciones = [];
            foreach ($requestedTypes as $tipo) {
                $opciones[$tipo] = $this->catRepo->obtenerOpciones($tipo);
            }
            return $this->success($opciones, 'OK', 200);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            error_log('CatalogoController::listarOpciones error: ' . $e->getMessage());
            return $this->error('Error interno del servidor.', 500);
        }
    }

    // ========================================================================
    // GUARDAR CONFIGURACION (validacion para POST de configuracion.php)
    // ========================================================================

    /**
     * Validar y normalizar el modo de UI.
     *
     * @param string $mode
     * @return array<string, mixed>
     */
    public function guardarConfiguracion(string $mode): array
    {
        $modeOverride = dedumsoft_ui_mode_override();
        $uaLegacy = dedumsoft_is_legacy_ua();
        $currentMode = $modeOverride !== '' ? $modeOverride : ($uaLegacy ? 'legacy' : 'normal');

        $mode = strtolower(trim($mode));
        if ($mode !== 'legacy' && $mode !== 'normal') {
            $mode = $currentMode;
        }

        return $this->success(['mode' => $mode], 'OK', 200);
    }

    // ========================================================================
    // LEGACY PAGE HELPERS
    // ========================================================================

    /**
     * Prepara los datos necesarios para renderizar la pagina de ubicaciones.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageDataUbicaciones(array $get, bool $legacy): array
    {
        $area    = $this->toString($get['area'] ?? '');
        $areaId  = null;
        $rows    = [];
        $options = [];

        try {
            $options = $this->catRepo->obtenerOpciones('areas');
        } catch (Exception $e) {
            error_log('areas error: ' . $e->getMessage());
            $options = [
                ['value' => 1, 'label' => 'General'],
                ['value' => 2, 'label' => 'Produccion'],
                ['value' => 3, 'label' => 'Almacen'],
                ['value' => 4, 'label' => 'Ventas'],
                ['value' => 5, 'label' => 'Oficina'],
                ['value' => 6, 'label' => 'Taller']
            ];
        }

        if ($legacy && $area !== '') {
            if (ctype_digit($area) && (int) $area > 0) {
                $areaId = (int) $area;
            } else {
                foreach ($options as $opt) {
                    if (strcasecmp((string) ($opt['label'] ?? ''), $area) === 0) {
                        $areaId = (int) ($opt['value'] ?? 0);
                        break;
                    }
                }
            }
        }

        if ($legacy) {
            try {
                $ubicLimit = $areaId !== null ? 200 : 50;
                $rows = $this->ubicRepo->listar(0, $ubicLimit, $areaId, true);

                if ($areaId !== null) {
                    $rows = array_values(array_filter($rows, function ($row) use ($areaId) {
                        return (int) ($row['area_id'] ?? 0) === $areaId;
                    }));
                }
            } catch (PDOException $e) {
                error_log('ubicaciones legacy error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
            }
        }

        $areaValue = $areaId !== null ? (string) $areaId : $area;

        return [
            'area'             => $area,
            'area_id'          => $areaId,
            'area_value'       => $areaValue,
            'ubicaciones_rows' => $rows,
            'area_options'     => $options,
            'legacy'           => $legacy,
        ];
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de configuracion.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageDataConfiguracion(array $get, bool $legacy): array
    {
        $modeOverride = dedumsoft_ui_mode_override();
        $uaLegacy     = dedumsoft_is_legacy_ua();
        $currentMode  = $modeOverride !== '' ? $modeOverride : ($uaLegacy ? 'legacy' : 'normal');
        $statusMsg    = '';

        if (($get['updated'] ?? '') === '1') {
            $statusMsg = 'Preferencia guardada.';
        } elseif (($get['error'] ?? '') === 'csrf') {
            $statusMsg = 'Error de seguridad. Intenta de nuevo.';
        }

        return [
            'current_mode' => $currentMode,
            'status_msg'   => $statusMsg,
            'legacy'       => $legacy,
        ];
    }

    /**
     * Prepara los datos necesarios para renderizar la pagina de catalogos.
     *
     * @param array $get  $_GET
     * @param bool  $legacy
     * @return array<string, mixed> Datos para la vista
     */
    public function pageDataCatalogos(array $get, bool $legacy): array
    {
        $catalogs       = dedumsoft_catalogs_config();
        $catalogKeys    = array_keys($catalogs);
        $defaultCatalog = count($catalogKeys) ? $catalogKeys[0] : '';
        $selectedCatalog = isset($get['catalog']) && isset($catalogs[$get['catalog']]) ? $get['catalog'] : $defaultCatalog;

        $uiCatalogs = [];
        foreach ($catalogs as $key => $cfg) {
            $uiCatalogs[$key] = [
                'label'        => $cfg['label'],
                'emoji'        => $cfg['emoji'] ?? '',
                'list_columns' => $cfg['list_columns'],
                'fields'       => $cfg['fields']
            ];
        }

        $legacyRows = [];
        if ($legacy && $selectedCatalog !== '') {
            try {
                $legacyRows = $this->catRepo->obtenerMaestros($selectedCatalog, null, 0, 200, null);
            } catch (Exception $e) {
                error_log('catalogos legacy error: ' . $e->getMessage());
            }
        }

        return [
            'catalogs'         => $catalogs,
            'selected_catalog' => $selectedCatalog,
            'ui_catalogs'      => $uiCatalogs,
            'legacy_rows'      => $legacyRows,
            'legacy'           => $legacy,
        ];
    }
}
