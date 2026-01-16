-- ============================================
-- FUNCIONES PARA UBICACIONES
-- ============================================

-- Obtener ubicaciones
CREATE OR REPLACE FUNCTION fun_obtener_ubicaciones(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 100,
    par_area text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    area text,
    activo boolean,
    created_at timestamp
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.id,
        u.nombre::text,
        u.descripcion::text,
        u.area::text,
        u.activo,
        u.created_at
    FROM ubicaciones u
    WHERE (par_activo IS NULL OR u.activo = par_activo)
      AND (par_area IS NULL OR u.area = par_area)
    ORDER BY u.area, u.nombre
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener ubicaciones.' USING ERRCODE = SQLSTATE;
END;
$$;

-- Crear ubicación
CREATE OR REPLACE FUNCTION fun_crear_ubicacion(
    par_nombre text,
    par_descripcion text DEFAULT NULL,
    par_area text DEFAULT 'General'
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO ubicaciones (nombre, descripcion, area)
    VALUES (par_nombre, par_descripcion, par_area)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear ubicación.' USING ERRCODE = SQLSTATE;
END;
$$;

-- Actualizar ubicación
CREATE OR REPLACE FUNCTION fun_actualizar_ubicacion(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_area text DEFAULT NULL,
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
        area = COALESCE(par_area, area),
        activo = COALESCE(par_activo, activo),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ubicación no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar ubicación.' USING ERRCODE = SQLSTATE;
END;
$$;

-- Eliminar (soft-delete) ubicación
CREATE OR REPLACE FUNCTION fun_eliminar_ubicacion(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE ubicaciones SET activo = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Ubicación no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar ubicación.' USING ERRCODE = SQLSTATE;
END;
$$;

-- Actualizar función de maquinaria para incluir ubicacion_nombre
DROP FUNCTION IF EXISTS fun_obtener_inventario_maquinaria(int, int, text);

CREATE OR REPLACE FUNCTION fun_obtener_inventario_maquinaria(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    tipo text,
    marca text,
    modelo text,
    numero_serie text,
    fecha_compra date,
    valor_compra numeric,
    estado text,
    ultima_mantenimiento date,
    proxima_mantenimiento date,
    ubicacion_id int,
    ubicacion_nombre text,
    fecha_registro timestamp,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        im.id,
        im.nombre::text,
        im.tipo::text,
        im.marca::text,
        im.modelo::text,
        im.numero_serie::text,
        im.fecha_compra,
        im.valor_compra,
        im.estado::text,
        im.ultima_mantenimiento,
        im.proxima_mantenimiento,
        im.ubicacion_id,
        u.nombre::text AS ubicacion_nombre,
        im.fecha_registro,
        im.activo
    FROM inventario_maquinaria im
    LEFT JOIN ubicaciones u ON im.ubicacion_id = u.id
    WHERE (par_estado IS NULL OR im.estado = par_estado)
      AND (par_activo IS NULL OR im.activo = par_activo)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al obtener inventario de maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;
