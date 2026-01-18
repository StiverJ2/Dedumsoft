-- Funciones para gestión de catálogos
-- Fecha: 2026-01-18

SET search_path TO joyeria, seguridad, public;

-- ============================================================================
-- FUNCIÓN: Obtener áreas activas
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_areas()
RETURNS TABLE (
    id INTEGER,
    codigo VARCHAR,
    nombre VARCHAR,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT a.id, a.codigo, a.nombre, a.descripcion, a.orden
    FROM areas a
    WHERE a.activo = TRUE
    ORDER BY a.orden, a.nombre;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- FUNCIÓN: Obtener tipos de oro activos
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_tipos_oro()
RETURNS TABLE (
    id INTEGER,
    codigo VARCHAR,
    nombre VARCHAR,
    kilates DECIMAL,
    pureza_porcentaje DECIMAL,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.codigo, t.nombre, t.kilates, t.pureza_porcentaje, t.descripcion, t.orden
    FROM tipos_oro t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.kilates;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- FUNCIÓN: Obtener tipos de proveedor activos
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_tipos_proveedor()
RETURNS TABLE (
    id INTEGER,
    codigo VARCHAR,
    nombre VARCHAR,
    descripcion TEXT,
    orden INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.id, t.codigo, t.nombre, t.descripcion, t.orden
    FROM tipos_proveedor t
    WHERE t.activo = TRUE
    ORDER BY t.orden, t.nombre;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- ACTUALIZAR FUNCIÓN: fun_obtener_ubicaciones
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_ubicaciones(
    p_offset INTEGER DEFAULT 0,
    p_limit INTEGER DEFAULT 50,
    p_area VARCHAR DEFAULT NULL,
    p_activo BOOLEAN DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    descripcion TEXT,
    area VARCHAR,
    area_id INTEGER,
    area_nombre VARCHAR,
    capacidad INTEGER,
    activo BOOLEAN,
    fecha_creacion TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.nombre,
        u.descripcion,
        u.area,  -- Mantener columna antigua por compatibilidad
        u.area_id,
        a.nombre as area_nombre,
        u.capacidad,
        u.activo,
        u.fecha_creacion
    FROM ubicaciones u
    INNER JOIN areas a ON u.area_id = a.id
    WHERE 
        (p_area IS NULL OR u.area = p_area OR a.codigo = p_area)
        AND (p_activo IS NULL OR u.activo = p_activo)
    ORDER BY u.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- ACTUALIZAR FUNCIÓN: fun_obtener_inventario_oro
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_inventario_oro(
    p_offset INTEGER DEFAULT 0,
    p_limit INTEGER DEFAULT 50,
    p_tipo VARCHAR DEFAULT NULL,
    p_activo BOOLEAN DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    tipo_oro VARCHAR,
    tipo_oro_id INTEGER,
    tipo_oro_nombre VARCHAR,
    peso_gramos DECIMAL,
    precio_gramo DECIMAL,
    proveedor_id INTEGER,
    proveedor_nombre VARCHAR,
    fecha_creacion TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        io.id,
        io.tipo_oro,  -- Mantener columna antigua por compatibilidad
        io.tipo_oro_id,
        t.nombre as tipo_oro_nombre,
        io.peso_gramos,
        io.precio_gramo,
        io.proveedor_id,
        p.nombre as proveedor_nombre,
        io.fecha_creacion
    FROM inventario_oro io
    INNER JOIN tipos_oro t ON io.tipo_oro_id = t.id
    LEFT JOIN proveedores p ON io.proveedor_id = p.id
    WHERE 
        (p_tipo IS NULL OR io.tipo_oro = p_tipo OR t.codigo = p_tipo)
        AND (p_activo IS NULL OR io.activo = p_activo)
    ORDER BY io.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- ACTUALIZAR FUNCIÓN: fun_obtener_proveedores
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_proveedores(
    p_offset INTEGER DEFAULT 0,
    p_limit INTEGER DEFAULT 50,
    p_tipo VARCHAR DEFAULT NULL,
    p_activo BOOLEAN DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    nombre VARCHAR,
    tipo VARCHAR,
    tipo_proveedor_id INTEGER,
    tipo_proveedor_nombre VARCHAR,
    contacto VARCHAR,
    telefono VARCHAR,
    email VARCHAR,
    direccion TEXT,
    activo BOOLEAN,
    fecha_creacion TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id,
        p.nombre,
        p.tipo,  -- Mantener columna antigua por compatibilidad
        p.tipo_proveedor_id,
        t.nombre as tipo_proveedor_nombre,
        p.contacto,
        p.telefono,
        p.email,
        p.direccion,
        p.activo,
        p.fecha_creacion
    FROM proveedores p
    INNER JOIN tipos_proveedor t ON p.tipo_proveedor_id = t.id
    WHERE 
        (p_tipo IS NULL OR p.tipo = p_tipo OR t.codigo = p_tipo)
        AND (p_activo IS NULL OR p.activo = p_activo)
    ORDER BY p.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;
