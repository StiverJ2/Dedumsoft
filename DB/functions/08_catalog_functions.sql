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
