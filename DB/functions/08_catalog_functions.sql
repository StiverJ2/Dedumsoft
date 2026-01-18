-- Funciones para gestión de catálogos
-- Fecha: 2026-01-18
-- Nota: Las tablas de catálogo usan solo id como identificador (sin columna codigo)

SET search_path TO joyeria, seguridad, public;

-- ============================================================================
-- FUNCIÓN: Obtener áreas activas
-- ============================================================================
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

-- ============================================================================
-- FUNCIÓN: Obtener tipos de oro activos
-- ============================================================================
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

-- ============================================================================
-- FUNCIÓN: Obtener tipos de proveedor activos
-- ============================================================================
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

-- ============================================================================
-- FUNCIÓN: Obtener tipos de maquinaria activos
-- ============================================================================
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
