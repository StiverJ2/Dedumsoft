<?php
/**
 * ============================================================================
 * API REST: OPCIONES DINÁMICAS PARA FORMULARIOS
 * ============================================================================
 * 
 * Endpoint centralizado para obtener listas de opciones para dropdowns.
 * Filtra tipos disponibles según los permisos del usuario.
 * 
 * Métodos soportados:
 * - GET: Obtener opciones por tipo
 * 
 * Autenticación: Requerida (JWT en sesión)
 * Autorización: Según menús asignados al rol del usuario
 * 
 * Parámetros:
 * - tipo (string, opcional): Tipo específico de opciones a obtener.
 *   Si no se envía, retorna todos los tipos permitidos para el usuario.
 * 
 * Tipos disponibles por menú:
 * - Menú 2 (Inventario): areas, tipos_oro, estados_maquinaria
 * - Menú 6 (Proveedores): tipos_proveedor
 * - Menú 3 (Producción): estados_orden, prioridades, tipos_material,
 *                        niveles_calidad, artesanos, productos
 * - Menú 5/7 (Usuarios/Configuración): especialidades
 * 
 * Formato de respuesta:
 * Cada opción tiene la estructura { value: ID, label: NOMBRE, ...extras }
 * 
 * @package Dedumsoft\API
 * @author  Equipo Dedumsoft
 */

// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';

require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Verificar autenticación (todos los tipos requieren sesión válida)
if (!require_api_auth()) {
    exit;
}

// =============================================================================
// GET: Obtener opciones dinámicas
// =============================================================================
// Este endpoint determina qué tipos de opciones puede ver el usuario
// basándose en los permisos de menú asignados a su rol.

if ($method === 'GET') {
    // Obtener y sanitizar el parámetro 'tipo'
    $tipo_raw = $_GET['tipo'] ?? null;
    $tipo = $tipo_raw !== null ? trim((string) $tipo_raw) : null;
    if ($tipo === '') {
        $tipo = null;
    }

    // =========================================================================
    // CONTROL DE ACCESO: Determinar tipos permitidos según permisos
    // =========================================================================
    // Cada menú otorga acceso a diferentes tipos de opciones.
    // Un usuario puede tener múltiples menús y por lo tanto múltiples tipos.

    $allowed_types = [];

    // Menú 2 (Inventario): Acceso a catálogos de inventario
    if (dedumsoft_user_can_menu(2)) {
        $allowed_types = array_merge($allowed_types, ['areas', 'tipos_oro', 'estados_maquinaria']);
    }

    // Menú 6 (Proveedores): Acceso a tipos de proveedor
    if (dedumsoft_user_can_menu(6)) {
        $allowed_types[] = 'tipos_proveedor';
    }

    // Menú 3 (Producción): Acceso a catálogos de producción y artesanos
    if (dedumsoft_user_can_menu(3)) {
        $allowed_types = array_merge($allowed_types, [
            'estados_orden',
            'prioridades',
            'tipos_material',
            'niveles_calidad',
            'artesanos',
            'productos'
        ]);
    }

    // Menú 5 (Usuarios) o Menú 7 (Configuración): Acceso a especialidades
    if (dedumsoft_user_can_menu(5) || dedumsoft_user_can_menu(7)) {
        $allowed_types[] = 'especialidades';
    }

    // Eliminar duplicados y reindexar
    $allowed_types = array_values(array_unique($allowed_types));

    // Validar que el tipo solicitado esté permitido
    if ($tipo !== null && !in_array($tipo, $allowed_types, true)) {
        dedumsoft_forbidden();
    }

    // Determinar qué tipos procesar (uno específico o todos los permitidos)
    $requested_types = $tipo !== null ? [$tipo] : $allowed_types;
    if (!$requested_types) {
        dedumsoft_forbidden();
    }

    try {
        $opciones = [];

        // =====================================================================
        // PROCESAMIENTO DE CADA TIPO DE OPCIÓN
        // =====================================================================
        // Cada tipo tiene su propia consulta y formato de respuesta.
        // Todos los tipos solo devuelven registros activos (activo = true).

        foreach ($requested_types as $requested) {

            // -----------------------------------------------------------------
            // ÁREAS: Áreas del almacén para organización de inventario
            // -----------------------------------------------------------------
            if ($requested === 'areas') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM areas WHERE activo = true ORDER BY nombre');
                $stmt->execute();
                $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['areas'] = array_map(function ($area) {
                    return [
                        'value' => $area['id'],
                        'label' => $area['nombre'],
                        'descripcion' => $area['descripcion']
                    ];
                }, $areas);
            }
            // -----------------------------------------------------------------
            // TIPOS DE PROVEEDOR: Clasificación de proveedores
            // -----------------------------------------------------------------
            elseif ($requested === 'tipos_proveedor') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM tipos_proveedor WHERE activo = true ORDER BY nombre');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_proveedor'] = array_map(function ($tipo) {
                    return [
                        'value' => $tipo['id'],
                        'label' => $tipo['nombre'],
                        'descripcion' => $tipo['descripcion']
                    ];
                }, $tipos);
            }
            // -----------------------------------------------------------------
            // TIPOS DE ORO: Clasificación por kilates y pureza
            // -----------------------------------------------------------------
            elseif ($requested === 'tipos_oro') {
                $stmt = $connLogic->prepare('SELECT id, nombre, kilates, pureza_porcentaje, descripcion FROM tipos_oro WHERE activo = true ORDER BY kilates, nombre');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_oro'] = array_map(function ($tipo) {
                    return [
                        'value' => $tipo['id'],
                        'label' => $tipo['nombre'],
                        'kilates' => $tipo['kilates'],
                        'pureza' => $tipo['pureza_porcentaje'],
                        'descripcion' => $tipo['descripcion']
                    ];
                }, $tipos);
            }
            // -----------------------------------------------------------------
            // ESTADOS DE MAQUINARIA: Estados operativos de equipos
            // -----------------------------------------------------------------
            elseif ($requested === 'estados_maquinaria') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM estados_maquinaria WHERE activo = true ORDER BY id');
                $stmt->execute();
                $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['estados_maquinaria'] = array_map(function ($est) {
                    return [
                        'value' => $est['id'],
                        'label' => $est['nombre'],
                        'descripcion' => $est['descripcion'],
                        'color' => $est['color']
                    ];
                }, $estados);
            }
            // -----------------------------------------------------------------
            // ESTADOS DE ORDEN: Estados del flujo de producción
            // -----------------------------------------------------------------
            elseif ($requested === 'estados_orden') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM estados_orden WHERE activo = true ORDER BY id');
                $stmt->execute();
                $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['estados_orden'] = array_map(function ($est) {
                    return [
                        'value' => $est['id'],
                        'label' => $est['nombre'],
                        'descripcion' => $est['descripcion'],
                        'color' => $est['color']
                    ];
                }, $estados);
            }
            // -----------------------------------------------------------------
            // PRIORIDADES: Niveles de urgencia para órdenes
            // -----------------------------------------------------------------
            elseif ($requested === 'prioridades') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion, color FROM prioridades WHERE activo = true ORDER BY id DESC');
                $stmt->execute();
                $prioridades = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['prioridades'] = array_map(function ($p) {
                    return [
                        'value' => $p['id'],
                        'label' => $p['nombre'],
                        'descripcion' => $p['descripcion'],
                        'color' => $p['color']
                    ];
                }, $prioridades);
            }
            // -----------------------------------------------------------------
            // PRODUCTOS: Catálogo de productos disponibles para órdenes
            // -----------------------------------------------------------------
            elseif ($requested === 'productos') {
                $stmt = $connLogic->prepare('SELECT id, nombre, tipo, descripcion FROM productos WHERE activo = true ORDER BY nombre');
                $stmt->execute();
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['productos'] = array_map(function ($p) {
                    $label = $p['nombre'];
                    if ($p['tipo'] !== null && $p['tipo'] !== '') {
                        $label .= ' (' . $p['tipo'] . ')';
                    }
                    return [
                        'value' => $p['id'],
                        'label' => $label,
                        'tipo' => $p['tipo'],
                        'descripcion' => $p['descripcion']
                    ];
                }, $productos);
            }
            // -----------------------------------------------------------------
            // TIPOS DE MATERIAL: Categorías de materiales consumibles
            // -----------------------------------------------------------------
            elseif ($requested === 'tipos_material') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM tipos_material WHERE activo = true ORDER BY id');
                $stmt->execute();
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['tipos_material'] = array_map(function ($t) {
                    return [
                        'value' => $t['id'],
                        'label' => $t['nombre'],
                        'descripcion' => $t['descripcion']
                    ];
                }, $tipos);
            }
            // -----------------------------------------------------------------
            // NIVELES DE CALIDAD: Clasificación de calidad de productos
            // -----------------------------------------------------------------
            elseif ($requested === 'niveles_calidad') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM niveles_calidad WHERE activo = true ORDER BY id');
                $stmt->execute();
                $niveles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['niveles_calidad'] = array_map(function ($n) {
                    return [
                        'value' => $n['id'],
                        'label' => $n['nombre'],
                        'descripcion' => $n['descripcion']
                    ];
                }, $niveles);
            }
            // -----------------------------------------------------------------
            // ARTESANOS: Lista de artesanos activos para asignación
            // -----------------------------------------------------------------
            elseif ($requested === 'artesanos') {
                $stmt = $connLogic->prepare(
                    'SELECT id, nombre, apellido, especialidades FROM fun_obtener_artesanos(:activo)'
                );
                $stmt->bindValue(':activo', true, PDO::PARAM_BOOL);
                $stmt->execute();
                $artesanos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['artesanos'] = array_map(function ($a) {
                    $nombre = trim($a['nombre'] . ' ' . $a['apellido']);
                    $especialidades = $a['especialidades'] ?? '';
                    $label = $nombre;
                    if ($especialidades !== null && $especialidades !== '') {
                        $label .= ' - ' . $especialidades;
                    }
                    return [
                        'value' => $a['id'],
                        'label' => $label,
                        'especialidades' => $especialidades
                    ];
                }, $artesanos);
            }
            // -----------------------------------------------------------------
            // ESPECIALIDADES: Catálogo de especialidades de artesanos
            // -----------------------------------------------------------------
            elseif ($requested === 'especialidades') {
                $stmt = $connLogic->prepare('SELECT id, nombre, descripcion FROM cat_especialidad WHERE activo = true ORDER BY id');
                $stmt->execute();
                $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $opciones['especialidades'] = array_map(function ($e) {
                    return [
                        'value' => $e['id'],
                        'label' => $e['nombre'],
                        'descripcion' => $e['descripcion']
                    ];
                }, $especialidades);
            }
        }

        // =====================================================================
        // FORMATO DE RESPUESTA
        // =====================================================================
        // Si se solicitó un tipo específico, retornar solo ese array.
        // Si no, retornar objeto con todos los tipos como propiedades.

        if ($tipo && isset($opciones[$tipo])) {
            echo json_encode(['CODIGO' => 200, 'DATOS' => $opciones[$tipo]]);
        } else {
            echo json_encode(['CODIGO' => 200, 'DATOS' => $opciones]);
        }
    } catch (PDOException $e) {
        error_log('opciones GET error: ' . $e->getMessage() . ' SQLSTATE=' . $e->getCode());
        http_response_code(500);
        echo json_encode(['CODIGO' => 500, 'MENSAJE' => 'Error interno del servidor.']);
    }
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(['CODIGO' => 405, 'MENSAJE' => 'Método no permitido.']);
