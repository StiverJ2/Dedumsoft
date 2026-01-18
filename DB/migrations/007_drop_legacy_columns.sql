-- Migración 007: Eliminar columnas redundantes de texto libre
-- Fecha: 2026-01-18
-- Descripción: Eliminar las columnas antiguas (area, tipo_oro, tipo) que fueron reemplazadas por FKs
-- Prerequisito: Migración 006 debe estar aplicada y funcionando correctamente

BEGIN;
SET search_path TO joyeria, seguridad, public;

-- ============================================================================
-- VERIFICACIÓN PREVIA
-- ============================================================================
-- Verificar que todas las FKs están pobladas antes de eliminar las columnas antiguas
DO $$
DECLARE
    ubicaciones_sin_fk INTEGER;
    oro_sin_fk INTEGER;
    proveedores_sin_fk INTEGER;
BEGIN
    -- Contar ubicaciones sin area_id
    SELECT COUNT(*) INTO ubicaciones_sin_fk
    FROM ubicaciones
    WHERE area_id IS NULL;
    
    IF ubicaciones_sin_fk > 0 THEN
        RAISE EXCEPTION 'ERROR: % ubicaciones tienen area_id NULL. Ejecuta la migración 006 primero.', ubicaciones_sin_fk;
    END IF;
    
    -- Contar inventario_oro sin tipo_oro_id
    SELECT COUNT(*) INTO oro_sin_fk
    FROM inventario_oro
    WHERE tipo_oro_id IS NULL;
    
    IF oro_sin_fk > 0 THEN
        RAISE EXCEPTION 'ERROR: % registros de oro tienen tipo_oro_id NULL. Ejecuta la migración 006 primero.', oro_sin_fk;
    END IF;
    
    -- Contar proveedores sin tipo_proveedor_id
    SELECT COUNT(*) INTO proveedores_sin_fk
    FROM proveedores
    WHERE tipo_proveedor_id IS NULL;
    
    IF proveedores_sin_fk > 0 THEN
        RAISE EXCEPTION 'ERROR: % proveedores tienen tipo_proveedor_id NULL. Ejecuta la migración 006 primero.', proveedores_sin_fk;
    END IF;
    
    RAISE NOTICE 'Verificación exitosa: Todas las FKs están pobladas correctamente';
END $$;

-- ============================================================================
-- 1. ELIMINAR COLUMNA: ubicaciones.area
-- ============================================================================
-- Eliminando columna ubicaciones.area...

ALTER TABLE ubicaciones DROP COLUMN IF EXISTS area;

COMMENT ON COLUMN ubicaciones.area_id IS 'FK a tabla de catálogo de áreas';

-- ============================================================================
-- 2. ELIMINAR COLUMNA: inventario_oro.tipo_oro
-- ============================================================================
-- Eliminando columna inventario_oro.tipo_oro...

ALTER TABLE inventario_oro DROP COLUMN IF EXISTS tipo_oro;

COMMENT ON COLUMN inventario_oro.tipo_oro_id IS 'FK a tabla de catálogo de tipos de oro';

-- ============================================================================
-- 3. ELIMINAR COLUMNA: proveedores.tipo
-- ============================================================================
-- Eliminando columna proveedores.tipo...

ALTER TABLE proveedores DROP COLUMN IF EXISTS tipo;

COMMENT ON COLUMN proveedores.tipo_proveedor_id IS 'FK a tabla de catálogo de tipos de proveedor';

-- ============================================================================
-- 4. ACTUALIZAR FUNCIÓN: fun_obtener_ubicaciones
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
    area_id INTEGER,
    area_codigo VARCHAR,
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
        u.area_id,
        a.codigo as area_codigo,
        a.nombre as area_nombre,
        u.capacidad,
        u.activo,
        u.fecha_creacion
    FROM ubicaciones u
    INNER JOIN areas a ON u.area_id = a.id
    WHERE 
        (p_area IS NULL OR a.codigo = p_area OR a.nombre ILIKE '%' || p_area || '%')
        AND (p_activo IS NULL OR u.activo = p_activo)
    ORDER BY u.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- 5. ACTUALIZAR FUNCIÓN: fun_obtener_inventario_oro
-- ============================================================================
CREATE OR REPLACE FUNCTION fun_obtener_inventario_oro(
    p_offset INTEGER DEFAULT 0,
    p_limit INTEGER DEFAULT 50,
    p_tipo VARCHAR DEFAULT NULL,
    p_activo BOOLEAN DEFAULT NULL
)
RETURNS TABLE (
    id INTEGER,
    tipo_oro_id INTEGER,
    tipo_oro_codigo VARCHAR,
    tipo_oro_nombre VARCHAR,
    tipo_oro_kilates DECIMAL,
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
        io.tipo_oro_id,
        t.codigo as tipo_oro_codigo,
        t.nombre as tipo_oro_nombre,
        t.kilates as tipo_oro_kilates,
        io.peso_gramos,
        io.precio_gramo,
        io.proveedor_id,
        p.nombre as proveedor_nombre,
        io.fecha_creacion
    FROM inventario_oro io
    INNER JOIN tipos_oro t ON io.tipo_oro_id = t.id
    LEFT JOIN proveedores p ON io.proveedor_id = p.id
    WHERE 
        (p_tipo IS NULL OR t.codigo = p_tipo OR t.nombre ILIKE '%' || p_tipo || '%')
        AND (p_activo IS NULL OR io.activo = p_activo)
    ORDER BY io.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- 6. ACTUALIZAR FUNCIÓN: fun_obtener_proveedores
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
    tipo_proveedor_id INTEGER,
    tipo_proveedor_codigo VARCHAR,
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
        p.tipo_proveedor_id,
        t.codigo as tipo_proveedor_codigo,
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
        (p_tipo IS NULL OR t.codigo = p_tipo OR t.nombre ILIKE '%' || p_tipo || '%')
        AND (p_activo IS NULL OR p.activo = p_activo)
    ORDER BY p.id DESC
    OFFSET p_offset
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- 7. RESUMEN DE CAMBIOS
-- ============================================================================
DO $$
BEGIN
    RAISE NOTICE '========================================';
    RAISE NOTICE 'MIGRACIÓN 007 COMPLETADA EXITOSAMENTE';
    RAISE NOTICE '========================================';
    RAISE NOTICE '';
    RAISE NOTICE 'Columnas eliminadas:';
    RAISE NOTICE '  ✓ ubicaciones.area';
    RAISE NOTICE '  ✓ inventario_oro.tipo_oro';
    RAISE NOTICE '  ✓ proveedores.tipo';
    RAISE NOTICE '';
    RAISE NOTICE 'Funciones actualizadas:';
    RAISE NOTICE '  ✓ fun_obtener_ubicaciones()';
    RAISE NOTICE '  ✓ fun_obtener_inventario_oro()';
    RAISE NOTICE '  ✓ fun_obtener_proveedores()';
    RAISE NOTICE '';
    RAISE NOTICE 'IMPORTANTE: Actualiza las APIs y vistas para usar:';
    RAISE NOTICE '  - area_id y area_codigo en lugar de area';
    RAISE NOTICE '  - tipo_oro_id y tipo_oro_codigo en lugar de tipo_oro';
    RAISE NOTICE '  - tipo_proveedor_id y tipo_proveedor_codigo en lugar de tipo';
    RAISE NOTICE '========================================';
END $$;

COMMIT;
