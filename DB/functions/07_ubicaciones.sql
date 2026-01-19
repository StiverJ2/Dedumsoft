-- ============================================
-- FUNCIONES DE UBICACIONES
-- Fecha: 2026-01-18
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- OBTENER UBICACIONES
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_ubicaciones(int, int, int, boolean);

CREATE OR REPLACE FUNCTION fun_obtener_ubicaciones(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 100,
    par_area_id int DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    area_id int,
    area_nombre text,
    activo boolean,
    created_at timestamp
)
LANGUAGE sql
AS $$
    SELECT
        u.id,
        u.nombre::text,
        u.descripcion::text,
        u.area_id,
        a.nombre::text AS area_nombre,
        u.activo,
        u.created_at
    FROM ubicaciones u
    LEFT JOIN areas a ON u.area_id = a.id
    WHERE (par_activo IS NULL OR u.activo = par_activo)
      AND (par_area_id IS NULL OR u.area_id = par_area_id)
    ORDER BY a.orden, u.nombre
    OFFSET par_offset
    LIMIT par_limit;
$$;

-- ============================================
-- CREAR UBICACIÓN
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_ubicacion(
    par_nombre text,
    par_descripcion text DEFAULT NULL,
    par_area_id int DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO ubicaciones (nombre, descripcion, area_id)
    VALUES (par_nombre, par_descripcion, par_area_id)
    RETURNING id INTO v_id;
    
    RETURN v_id;
END;
$$;

-- ============================================
-- ACTUALIZAR UBICACIÓN
-- ============================================

CREATE OR REPLACE FUNCTION fun_actualizar_ubicacion(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_area_id int DEFAULT NULL,
    par_activo boolean DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE ubicaciones
    SET 
        nombre = COALESCE(par_nombre, nombre),
        descripcion = COALESCE(par_descripcion, descripcion),
        area_id = COALESCE(par_area_id, area_id),
        activo = COALESCE(par_activo, activo),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ubicación no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- ELIMINAR UBICACIÓN (soft-delete)
-- ============================================

CREATE OR REPLACE FUNCTION fun_eliminar_ubicacion(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE ubicaciones 
    SET activo = FALSE, updated_at = CURRENT_TIMESTAMP 
    WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ubicación no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- OBTENER ÁREAS
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_areas();

CREATE OR REPLACE FUNCTION fun_obtener_areas()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    orden int
)
LANGUAGE sql
AS $$
    SELECT a.id, a.nombre::text, a.descripcion::text, a.orden
    FROM areas a
    WHERE a.activo = TRUE
    ORDER BY a.orden, a.nombre;
$$;
