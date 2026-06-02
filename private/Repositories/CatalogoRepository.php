<?php
/**
 * ============================================================================
 * CATALOGO REPOSITORY
 * ============================================================================
 *
 * Responsabilidad: INVOCAR funciones PL/pgSQL y SQL centralizado para catalogos.
 *
 * - Especialidades: fun_obtener_especialidades, fun_crear_especialidad, etc.
 * - Maestros: SQL dinamico sobre tablas de CatalogConfig (no existe fun generica).
 * - Opciones: Delega a funciones especificas por tipo o SQL directo cuando no hay funcion.
 *
 * @package Dedumsoft\Repositories
 */

require_once __DIR__ . '/Repository.php';
require_once PRIVATE_PATH . '/Database/CatalogConfig.php';

class CatalogoRepository extends Repository
{
    /**
     * Obtener registros de un catalogo maestro.
     *
     * @param string      $catalog Nombre del catalogo
     * @param int|null    $id      Filtrar por ID (null = listar)
     * @param int         $offset  Inicio de paginacion
     * @param int         $limit   Cantidad de registros
     * @param string|null $activoRaw Valor raw del parametro activo
     * @return array<int, array<string, mixed>>
     * @throws InvalidArgumentException
     */
    public function obtenerMaestros(string $catalog, ?int $id = null, int $offset = 0, int $limit = 200, ?string $activoRaw = null): array
    {
        $catalogs = dedumsoft_catalogs_config();
        if (!isset($catalogs[$catalog])) {
            throw new InvalidArgumentException('Catalogo invalido.');
        }

        $cfg = $catalogs[$catalog];
        $table = $cfg['table'];
        $columns = $cfg['columns'];
        $orderBy = $cfg['order_by'];

        $where = [];
        $params = [];
        if ($id !== null && $id > 0) {
            $where[] = 'id = :id';
            $params[':id'] = $id;
        } elseif (!empty($cfg['filter_activo'])) {
            $activo = $this->parseActivo($activoRaw, true);
            if ($activo !== null) {
                $where[] = 'activo = :activo';
                $params[':activo'] = $activo;
            }
        }

        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . $orderBy;
        if ($id === null || $id <= 0) {
            $sql .= ' OFFSET :offset LIMIT :limit';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === ':activo') {
                $stmt->bindValue($key, $val, PDO::PARAM_BOOL);
            } elseif ($key === ':id') {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }
        if ($id === null || $id <= 0) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear un registro en un catalogo maestro.
     *
     * @param string $catalog
     * @param array  $input
     * @return int ID creado
     * @throws InvalidArgumentException
     */
    public function crearMaestro(string $catalog, array $input): int
    {
        $catalogs = dedumsoft_catalogs_config();
        if (!isset($catalogs[$catalog])) {
            throw new InvalidArgumentException('Catalogo invalido.');
        }

        $cfg = $catalogs[$catalog];
        $table = $cfg['table'];
        $fields = $cfg['fields'];

        $insertFields = [];
        $placeholders = [];
        $params = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['data'] ?? 'text';
            $value = array_key_exists($name, $input) ? $this->normalizeField($input[$name], $type) : null;

            if (!empty($field['required']) && ($value === null || $value === '')) {
                throw new InvalidArgumentException("Campo requerido: {$name}");
            }

            if (array_key_exists($name, $input)) {
                $insertFields[] = $name;
                $placeholders[] = ':' . $name;
                $params[':' . $name] = $value;
            }
        }

        if (empty($insertFields)) {
            throw new InvalidArgumentException('Sin datos para guardar.');
        }

        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $placeholders) . ') RETURNING id';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Actualizar un registro de catalogo maestro.
     *
     * @param string $catalog
     * @param int    $id
     * @param array  $input
     * @return bool
     * @throws InvalidArgumentException
     */
    public function actualizarMaestro(string $catalog, int $id, array $input): bool
    {
        $catalogs = dedumsoft_catalogs_config();
        if (!isset($catalogs[$catalog])) {
            throw new InvalidArgumentException('Catalogo invalido.');
        }

        $cfg = $catalogs[$catalog];
        $table = $cfg['table'];
        $fields = $cfg['fields'];

        $updates = [];
        $params = [':id' => $id];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['data'] ?? 'text';
            if (array_key_exists($name, $input)) {
                $updates[] = $name . ' = :' . $name;
                $params[':' . $name] = $this->normalizeField($input[$name], $type);
            }
        }

        if (empty($updates)) {
            throw new InvalidArgumentException('No hay campos para actualizar.');
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar (soft-delete) un registro de catalogo maestro.
     *
     * @param string $catalog
     * @param int    $id
     * @return bool
     * @throws InvalidArgumentException
     */
    public function eliminarMaestro(string $catalog, int $id): bool
    {
        $catalogs = dedumsoft_catalogs_config();
        if (!isset($catalogs[$catalog])) {
            throw new InvalidArgumentException('Catalogo invalido.');
        }

        $table = $catalogs[$catalog]['table'];
        $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET activo = false WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Listar especialidades.
     *
     * @param int       $offset
     * @param int       $limit
     * @param bool|null $activo
     * @return array<int, array<string, mixed>>
     */
    public function listarEspecialidades(int $offset = 0, int $limit = 100, ?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, descripcion, activo FROM fun_obtener_especialidades(:offset, :limit, :activo)'
        );
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una especialidad por ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerEspecialidadPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nombre, descripcion, activo FROM fun_obtener_especialidades(0, 1, NULL) WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crear una especialidad.
     *
     * @param string      $nombre
     * @param string|null $descripcion
     * @return int ID creado
     */
    public function crearEspecialidad(string $nombre, ?string $descripcion = null): int
    {
        $stmt = $this->pdo->prepare('SELECT fun_crear_especialidad(:nombre, :descripcion)');
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Actualizar una especialidad.
     *
     * @param int         $id
     * @param string|null $nombre
     * @param string|null $descripcion
     * @param bool|null   $activo
     * @return bool
     */
    public function actualizarEspecialidad(int $id, ?string $nombre = null, ?string $descripcion = null, ?bool $activo = null): bool
    {
        return $this->execBool(
            'SELECT fun_actualizar_especialidad(:id, :nombre, :descripcion, :activo)',
            [':id' => $id, ':nombre' => $nombre, ':descripcion' => $descripcion, ':activo' => $activo]
        );
    }

    /**
     * Eliminar (soft-delete) una especialidad.
     *
     * @param int $id
     * @return bool
     */
    public function eliminarEspecialidad(int $id): bool
    {
        return $this->execBool(
            'SELECT fun_eliminar_especialidad(:id)',
            [':id' => $id]
        );
    }

    /**
     * Obtener opciones para dropdowns segun tipo.
     *
     * @param string $tipo
     * @return array<int, array<string, mixed>>
     * @throws InvalidArgumentException
     */
    public function obtenerOpciones(string $tipo): array
    {
        switch ($tipo) {
            case 'areas':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion FROM fun_obtener_areas()',
                    [],
                    function ($row) {
                        return ['value' => $row['id'], 'label' => $row['nombre'], 'descripcion' => $row['descripcion']];
                    }
                );
            case 'tipos_proveedor':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion FROM fun_obtener_tipos_proveedor()',
                    [],
                    function ($row) {
                        return ['value' => $row['id'], 'label' => $row['nombre'], 'descripcion' => $row['descripcion']];
                    }
                );
            case 'tipos_oro':
                return $this->fetchOpciones(
                    'SELECT id, nombre, kilates, pureza_porcentaje, descripcion FROM fun_obtener_tipos_oro()',
                    [],
                    function ($row) {
                        return [
                            'value' => $row['id'],
                            'label' => $row['nombre'],
                            'kilates' => $row['kilates'],
                            'pureza' => $row['pureza_porcentaje'],
                            'descripcion' => $row['descripcion']
                        ];
                    }
                );
            case 'estados_maquinaria':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion, color FROM fun_obtener_estados_maquinaria()',
                    [],
                    function ($row) {
                        return [
                            'value' => $row['id'],
                            'label' => $row['nombre'],
                            'descripcion' => $row['descripcion'],
                            'color' => $row['color']
                        ];
                    }
                );
            case 'estados_orden':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion, color FROM fun_obtener_estados_orden()',
                    [],
                    function ($row) {
                        return [
                            'value' => $row['id'],
                            'label' => $row['nombre'],
                            'descripcion' => $row['descripcion'],
                            'color' => $row['color']
                        ];
                    }
                );
            case 'prioridades':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion, color FROM fun_obtener_prioridades()',
                    [],
                    function ($row) {
                        return [
                            'value' => $row['id'],
                            'label' => $row['nombre'],
                            'descripcion' => $row['descripcion'],
                            'color' => $row['color']
                        ];
                    }
                );
            case 'productos':
                return $this->fetchOpciones(
                    'SELECT id, nombre, tipo, descripcion FROM productos WHERE activo = true ORDER BY nombre',
                    [],
                    function ($row) {
                        $label = $row['nombre'];
                        if ($row['tipo'] !== null && $row['tipo'] !== '') {
                            $label .= ' (' . $row['tipo'] . ')';
                        }
                        return ['value' => $row['id'], 'label' => $label, 'tipo' => $row['tipo'], 'descripcion' => $row['descripcion']];
                    }
                );
            case 'tipos_material':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion FROM fun_obtener_tipos_material()',
                    [],
                    function ($row) {
                        return ['value' => $row['id'], 'label' => $row['nombre'], 'descripcion' => $row['descripcion']];
                    }
                );
            case 'niveles_calidad':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion FROM fun_obtener_niveles_calidad()',
                    [],
                    function ($row) {
                        return ['value' => $row['id'], 'label' => $row['nombre'], 'descripcion' => $row['descripcion']];
                    }
                );
            case 'artesanos':
                $stmt = $this->pdo->prepare('SELECT id, nombre, apellido, especialidades FROM fun_obtener_artesanos(:activo)');
                $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return array_map(function ($a) {
                    $nombre = trim($a['nombre'] . ' ' . $a['apellido']);
                    $especialidades = $a['especialidades'] ?? '';
                    $label = $nombre;
                    if ($especialidades !== null && $especialidades !== '') {
                        $label .= ' - ' . $especialidades;
                    }
                    return ['value' => $a['id'], 'label' => $label, 'especialidades' => $especialidades];
                }, $rows);
            case 'especialidades':
                return $this->fetchOpciones(
                    'SELECT id, nombre, descripcion FROM cat_especialidad WHERE activo = true ORDER BY id',
                    [],
                    function ($row) {
                        return ['value' => $row['id'], 'label' => $row['nombre'], 'descripcion' => $row['descripcion']];
                    }
                );
            default:
                throw new InvalidArgumentException('Tipo de opcion invalido.');
        }
    }

    /**
     * Obtener tipos de maquinaria.
     *
     * @param bool|null $activo
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTiposMaquinaria(?bool $activo = true): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, codigo, nombre, descripcion, activo FROM fun_obtener_tipos_maquinaria(:activo)'
        );
        $stmt->bindValue(':activo', $activo, $activo === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parseActivo(?string $value, $default)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $value = strtolower(trim($value));
        if ($value === 'all') {
            return null;
        }
        if (in_array($value, ['1', 'true', 't', 'si', 'yes'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'f', 'no'], true)) {
            return false;
        }
        return $default;
    }

    private function normalizeField($value, string $type)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '') {
            return null;
        }
        if ($type === 'int') {
            return (int) $value;
        }
        if ($type === 'decimal') {
            return (float) $value;
        }
        if ($type === 'bool') {
            return (bool) $value;
        }
        return $value;
    }

    private function fetchOpciones(string $sql, array $params, callable $mapper): array
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map($mapper, $rows);
    }
}
