-- ============================================
-- FUNCIONES ACTUALIZADAS PARA tipos_maquinaria
-- Ejecutar DESPUÉS de la migración 005
-- ============================================

SET search_path TO joyeria, public;

-- ============================================
-- FUNCIÓN PARA LISTAR TIPOS DE MAQUINARIA
-- ============================================
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
        tm.id,
        tm.codigo::text,
        tm.nombre::text,
        tm.descripcion::text,
        tm.activo
    FROM tipos_maquinaria tm
    WHERE (par_activo IS NULL OR tm.activo = par_activo)
    ORDER BY tm.nombre;
END;
$$;

-- ============================================
-- FUNCIÓN OBTENER INVENTARIO MAQUINARIA (ACTUALIZADA)
-- ============================================
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
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
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
    JOIN tipos_maquinaria tm ON im.tipo_maquinaria_id = tm.id
    LEFT JOIN ubicaciones u ON im.ubicacion_id = u.id
    WHERE (par_estado IS NULL OR im.estado = par_estado)
      AND (par_activo IS NULL OR im.activo = par_activo)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de inventario no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- FUNCIÓN CREAR MAQUINARIA (ACTUALIZADA)
-- ============================================
DROP FUNCTION IF EXISTS fun_crear_inventario_maquinaria(text, text, date, numeric, text, text, text, text, date, date, int);

CREATE OR REPLACE FUNCTION fun_crear_inventario_maquinaria(
    par_nombre text,
    par_tipo_maquinaria_id int,
    par_fecha_compra date,
    par_valor_compra numeric,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_numero_serie text DEFAULT NULL,
    par_estado text DEFAULT 'operativa',
    par_ultima_mantenimiento date DEFAULT NULL,
    par_proxima_mantenimiento date DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_maquinaria (nombre, tipo_maquinaria_id, marca, modelo, numero_serie, fecha_compra, valor_compra, estado, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, activo)
    VALUES (par_nombre, par_tipo_maquinaria_id, par_marca, par_modelo, par_numero_serie, par_fecha_compra, par_valor_compra, par_estado, par_ultima_mantenimiento, par_proxima_mantenimiento, par_ubicacion_id, TRUE)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- FUNCIÓN ACTUALIZAR MAQUINARIA (ACTUALIZADA)
-- ============================================
DROP FUNCTION IF EXISTS fun_actualizar_inventario_maquinaria(int, text, text, text, text, text, date, numeric, text, date, date, int);

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_maquinaria(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_tipo_maquinaria_id int DEFAULT NULL,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_numero_serie text DEFAULT NULL,
    par_fecha_compra date DEFAULT NULL,
    par_valor_compra numeric DEFAULT NULL,
    par_estado text DEFAULT NULL,
    par_ultima_mantenimiento date DEFAULT NULL,
    par_proxima_mantenimiento date DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_maquinaria
    SET 
        nombre = COALESCE(par_nombre, nombre),
        tipo_maquinaria_id = COALESCE(par_tipo_maquinaria_id, tipo_maquinaria_id),
        marca = COALESCE(par_marca, marca),
        modelo = COALESCE(par_modelo, modelo),
        numero_serie = COALESCE(par_numero_serie, numero_serie),
        fecha_compra = COALESCE(par_fecha_compra, fecha_compra),
        valor_compra = COALESCE(par_valor_compra, valor_compra),
        estado = COALESCE(par_estado, estado),
        ultima_mantenimiento = COALESCE(par_ultima_mantenimiento, ultima_mantenimiento),
        proxima_mantenimiento = COALESCE(par_proxima_mantenimiento, proxima_mantenimiento),
        ubicacion_id = COALESCE(par_ubicacion_id, ubicacion_id)
    WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Maquinaria no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;
