-- ============================================
-- MIGRACIÓN: Corregir firmas de funciones
-- Fecha: 2026-01-18
-- Descripción: Eliminar funciones antiguas y recrear con firmas correctas
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- CORREGIR FUNCIÓN fun_obtener_proveedores
-- ============================================

-- Eliminar todas las versiones de la función (incluyendo varchar que es diferente de text)
DROP FUNCTION IF EXISTS joyeria.fun_obtener_proveedores(int, int, int, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_proveedores(int, int, text, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_proveedores(int, int, varchar, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_proveedores();

-- Recrear función con firma correcta
CREATE OR REPLACE FUNCTION fun_obtener_proveedores(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_tipo_id int DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    tipo_proveedor_id int,
    tipo_nombre text,
    contacto text,
    telefono text,
    email text,
    direccion text,
    activo boolean,
    fecha_registro timestamp
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.id,
        p.nombre::text,
        p.tipo_proveedor_id,
        t.nombre::text AS tipo_nombre,
        p.contacto::text,
        p.telefono::text,
        p.email::text,
        p.direccion::text,
        p.activo,
        p.fecha_registro
    FROM proveedores p
    LEFT JOIN tipos_proveedor t ON p.tipo_proveedor_id = t.id
    WHERE (par_tipo_id IS NULL OR p.tipo_proveedor_id = par_tipo_id)
      AND (par_activo IS NULL OR p.activo = par_activo)
    ORDER BY p.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de proveedores no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- CORREGIR FUNCIÓN fun_obtener_ubicaciones
-- ============================================

-- Eliminar todas las versiones de la función (incluyendo varchar)
DROP FUNCTION IF EXISTS joyeria.fun_obtener_ubicaciones(int, int, int, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_ubicaciones(int, int, varchar, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_ubicaciones(int, int, text, boolean);
DROP FUNCTION IF EXISTS joyeria.fun_obtener_ubicaciones();

-- Recrear función con firma correcta (usa area_id en lugar de area varchar)
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
-- VERIFICAR QUE LAS FUNCIONES EXISTAN
-- ============================================

DO $$
BEGIN
    -- Verificar fun_obtener_proveedores
    IF NOT EXISTS (
        SELECT 1 FROM pg_proc p
        JOIN pg_namespace n ON p.pronamespace = n.oid
        WHERE p.proname = 'fun_obtener_proveedores'
    ) THEN
        RAISE EXCEPTION 'fun_obtener_proveedores no fue creada correctamente';
    END IF;

    -- Verificar fun_obtener_ubicaciones
    IF NOT EXISTS (
        SELECT 1 FROM pg_proc p
        JOIN pg_namespace n ON p.pronamespace = n.oid
        WHERE p.proname = 'fun_obtener_ubicaciones'
    ) THEN
        RAISE EXCEPTION 'fun_obtener_ubicaciones no fue creada correctamente';
    END IF;

    RAISE NOTICE 'Migración completada: funciones actualizadas correctamente';
END;
$$;
