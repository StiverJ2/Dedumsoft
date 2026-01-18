-- ============================================
-- FUNCIONES PARA UBICACIONES
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- Obtener ubicaciones
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

-- Crear ubicación
CREATE OR REPLACE FUNCTION fun_crear_ubicacion(
    par_nombre text,
    par_descripcion text DEFAULT NULL,
    par_area_id int DEFAULT 1
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

-- Actualizar función de maquinaria para incluir ubicacion_nombre y tipo_maquinaria
DROP FUNCTION IF EXISTS fun_obtener_inventario_maquinaria(int, int, text);
DROP FUNCTION IF EXISTS fun_obtener_inventario_maquinaria(int, int, text, boolean);

CREATE OR REPLACE FUNCTION fun_obtener_inventario_maquinaria(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    tipo_maquinaria_id int,
    tipo_codigo text,
    tipo_nombre text,
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
LANGUAGE sql
AS $$
    SELECT
        im.id,
        im.nombre::text,
        im.tipo_maquinaria_id,
        tm.codigo::text AS tipo_codigo,
        tm.nombre::text AS tipo_nombre,
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
    LEFT JOIN tipos_maquinaria tm ON im.tipo_maquinaria_id = tm.id
    WHERE (par_estado IS NULL OR im.estado = par_estado)
      AND (par_activo IS NULL OR im.activo = par_activo)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
$$;
