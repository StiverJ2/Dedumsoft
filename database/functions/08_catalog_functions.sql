-- ============================================================================
-- FUNCIONES: CATÁLOGOS
-- ============================================================================
--
-- Funciones para obtener datos de tablas de catálogo.
-- Usadas principalmente para poblar dropdowns en la UI.
--
-- FUNCIONES INCLUIDAS:
-- - fun_obtener_tipos_oro(): Tipos de oro (10k, 14k, 18k, 24k)
-- - fun_obtener_tipos_proveedor(): Tipos de proveedor
-- - fun_obtener_tipos_maquinaria(): Tipos de maquinaria
-- - fun_obtener_estados_maquinaria(): Estados de maquinaria
-- - fun_obtener_estados_orden(): Estados de orden
-- - fun_obtener_prioridades(): Niveles de prioridad
-- - fun_obtener_areas(): Áreas físicas
-- - fun_obtener_productos(): Catálogo de productos
-- - fun_obtener_artesanos(): Lista de artesanos activos
--
-- CARACTERÍSTICAS:
-- - Solo retornan registros activos (activo = TRUE)
-- - Ordenados por campo 'orden' o 'nombre'
-- - Usan solo ID como identificador (¡no códigos!)
--
-- EJEMPLO DE USO:
--   SELECT id, nombre, kilates, pureza_porcentaje, descripcion, orden FROM fun_obtener_tipos_oro();
--   -- Retorna: id, nombre, kilates, pureza_porcentaje, descripcion, orden
--
-- ============================================================================
-- FUNCIONES DE CATÁLOGOS
-- Fecha: 2026-01-18
-- Nota: Las tablas de catálogo usan solo id como identificador
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- TIPOS DE ORO
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_tipos_oro();

CREATE OR REPLACE FUNCTION fun_obtener_tipos_oro()
RETURNS TABLE (
    id int,
    nombre text,
    kilates numeric,
    pureza_porcentaje numeric,
    descripcion text,
    orden int
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.nombre::text, t.kilates, t.pureza_porcentaje, t.descripcion::text, t.orden
    FROM tipos_oro t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.kilates;
END;
$$;

-- ============================================
-- TIPOS DE PROVEEDOR
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_tipos_proveedor();

CREATE OR REPLACE FUNCTION fun_obtener_tipos_proveedor()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    orden int
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.nombre::text, t.descripcion::text, t.orden
    FROM tipos_proveedor t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.nombre;
END;
$$;

-- ============================================
-- TIPOS DE MAQUINARIA
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_tipos_maquinaria();
DROP FUNCTION IF EXISTS fun_obtener_tipos_maquinaria(boolean);

CREATE OR REPLACE FUNCTION fun_obtener_tipos_maquinaria(
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    codigo text,
    nombre text,
    descripcion text,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        t.id,
        regexp_replace(lower(t.nombre), '\\s+', '_', 'g')::text AS codigo,
        t.nombre::text,
        t.descripcion::text,
        t.activo
    FROM tipos_maquinaria t
    WHERE (par_activo IS NULL OR t.activo = par_activo)
    ORDER BY t.nombre;
END;
$$;

-- ============================================
-- ESPECIALIDADES DE ARTESANOS
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_especialidades(int, int, boolean);
DROP FUNCTION IF EXISTS fun_crear_especialidad(text, text, int);
DROP FUNCTION IF EXISTS fun_crear_especialidad(text, text);
DROP FUNCTION IF EXISTS fun_actualizar_especialidad(int, text, text, int, boolean);
DROP FUNCTION IF EXISTS fun_actualizar_especialidad(int, text, text, boolean);
DROP FUNCTION IF EXISTS fun_eliminar_especialidad(int);

CREATE OR REPLACE FUNCTION fun_obtener_especialidades(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 100,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        e.id,
        e.nombre::text,
        e.descripcion::text,
        e.activo
    FROM cat_especialidad e
    WHERE (par_activo IS NULL OR e.activo = par_activo)
    ORDER BY e.id
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

CREATE OR REPLACE FUNCTION fun_crear_especialidad(
    par_nombre text,
    par_descripcion text DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
    v_nombre text;
    v_descripcion text;
BEGIN
    v_nombre := NULLIF(btrim(par_nombre), '');
    v_descripcion := NULLIF(btrim(par_descripcion), '');

    IF v_nombre IS NULL THEN
        RAISE EXCEPTION 'Nombre requerido.' USING ERRCODE = 'P0001';
    END IF;

    INSERT INTO cat_especialidad (nombre, descripcion, activo, fecha_creacion, fecha_modificacion)
    VALUES (v_nombre, v_descripcion, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO v_id;

    RETURN v_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_especialidad(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_activo boolean DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
DECLARE
    v_nombre text;
    v_descripcion text;
BEGIN
    v_nombre := NULLIF(btrim(par_nombre), '');
    v_descripcion := NULLIF(btrim(par_descripcion), '');

    UPDATE cat_especialidad
    SET
        nombre = COALESCE(v_nombre, nombre),
        descripcion = COALESCE(v_descripcion, descripcion),
        activo = COALESCE(par_activo, activo),
        fecha_modificacion = CURRENT_TIMESTAMP
    WHERE id = par_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Especialidad no encontrada.' USING ERRCODE = 'P0002';
    END IF;

    RETURN TRUE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_especialidad(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE cat_especialidad
    SET activo = FALSE,
        fecha_modificacion = CURRENT_TIMESTAMP
    WHERE id = par_id
      AND activo = TRUE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Especialidad no encontrada.' USING ERRCODE = 'P0002';
    END IF;

    RETURN TRUE;
END;
$$;

-- ============================================
-- ARTESANOS (con especialidades)
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_artesanos(boolean);

CREATE OR REPLACE FUNCTION fun_obtener_artesanos(
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    apellido text,
    especialidades text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        a.id,
        a.nombre::text,
        a.apellido::text,
        string_agg(e.nombre, ', ' ORDER BY e.nombre) AS especialidades
    FROM artesanos a
    LEFT JOIN artesano_especialidad ae ON ae.artesano_id = a.id
    LEFT JOIN cat_especialidad e ON e.id = ae.especialidad_id AND e.activo = TRUE
    WHERE (par_activo IS NULL OR a.activo = par_activo)
    GROUP BY a.id, a.nombre, a.apellido
    ORDER BY a.nombre, a.apellido;
END;
$$;
