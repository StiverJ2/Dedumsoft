-- ============================================
-- MIGRACIÓN: Eliminar columna codigo de tablas de catálogo
-- Fecha: 2026-01-18
-- Descripción: Eliminar la columna redundante 'codigo' de las tablas de catálogo
--              ya que viola la normalización (id es suficiente para identificar registros)
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- 1. ELIMINAR COLUMNA CODIGO DE TABLAS DE CATÁLOGO
-- ============================================

ALTER TABLE areas DROP COLUMN IF EXISTS codigo;
ALTER TABLE tipos_maquinaria DROP COLUMN IF EXISTS codigo;
ALTER TABLE tipos_oro DROP COLUMN IF EXISTS codigo;
ALTER TABLE tipos_proveedor DROP COLUMN IF EXISTS codigo;

-- ============================================
-- 2. ACTUALIZAR FUNCIONES DE CATÁLOGO
-- ============================================

-- Obtener áreas (sin codigo)
DROP FUNCTION IF EXISTS fun_obtener_areas();
CREATE OR REPLACE FUNCTION fun_obtener_areas()
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT a.id, a.nombre, a.descripcion, a.orden
    FROM areas a
    WHERE a.activo = TRUE
    ORDER BY a.orden, a.nombre;
END;
$$ LANGUAGE plpgsql STABLE;

-- Obtener tipos de oro (sin codigo)
DROP FUNCTION IF EXISTS fun_obtener_tipos_oro();
CREATE OR REPLACE FUNCTION fun_obtener_tipos_oro()
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    kilates DECIMAL,
    pureza_porcentaje DECIMAL,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.nombre, t.kilates, t.pureza_porcentaje, t.descripcion, t.orden
    FROM tipos_oro t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.kilates;
END;
$$ LANGUAGE plpgsql STABLE;

-- Obtener tipos de proveedor (sin codigo)
DROP FUNCTION IF EXISTS fun_obtener_tipos_proveedor();
CREATE OR REPLACE FUNCTION fun_obtener_tipos_proveedor()
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.nombre, t.descripcion, t.orden
    FROM tipos_proveedor t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.nombre;
END;
$$ LANGUAGE plpgsql STABLE;

-- Obtener tipos de maquinaria (sin codigo)
DROP FUNCTION IF EXISTS fun_obtener_tipos_maquinaria();
CREATE OR REPLACE FUNCTION fun_obtener_tipos_maquinaria()
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.nombre, t.descripcion, t.orden
    FROM tipos_maquinaria t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.nombre;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================
-- 3. ACTUALIZAR FUNCIÓN INVENTARIO MAQUINARIA (sin tipo_codigo)
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

-- ============================================
-- VERIFICACIÓN
-- ============================================
DO $$
BEGIN
    RAISE NOTICE 'Migración completada: columna codigo eliminada de tablas de catálogo';
END;
$$;
