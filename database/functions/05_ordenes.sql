-- ============================================================================
-- FUNCIONES: ÓRDENES DE PRODUCCIÓN
-- ============================================================================
--
-- Funciones para la gestión del ciclo de vida de órdenes de producción.
-- Las órdenes representan trabajos de fabricación de joyas.
--
-- FUNCIONES INCLUIDAS:
-- - fun_obtener_ordenes(offset, limit, estado): Listar órdenes
-- - fun_crear_orden(producto_id, cantidad, artesano_id, prioridad_id, ...)
-- - fun_actualizar_orden(id, estado_id, fecha_inicio, ...)
-- - fun_asignar_artesano(orden_id, artesano_id)
-- - fun_cambiar_estado_orden(orden_id, estado_id)
--
-- ESTADOS DE ORDEN (tabla estados_orden):
-- +----+------------+----------------------------------+
-- | ID | Nombre     | Descripción                      |
-- +----+------------+----------------------------------+
-- | 1  | pendiente  | Creada, esperando inicio         |
-- | 2  | en_proceso | En fabricación                   |
-- | 3  | terminada  | Completada exitosamente          |
-- | 4  | cancelada  | Cancelada                        |
-- | 5  | pausada    | Pausada temporalmente            |
-- +----+------------+----------------------------------+
--
-- PRIORIDADES (tabla prioridades):
-- +----+---------+
-- | ID | Nombre  |
-- +----+---------+
-- | 1  | baja    |
-- | 2  | media   |
-- | 3  | alta    |
-- | 4  | urgente |
-- +----+---------+
--
-- FECHAS AUTOMÁTICAS:
-- - fecha_inicio: Se establece al cambiar a 'en_proceso'
-- - fecha_fin_real: Se establece al cambiar a 'terminada'
--
-- ============================================================================
-- FUNCIONES DE ORDENES DE PRODUCCION
-- Fecha: 2024-01-18
-- Normalizado: estado_id y prioridad_id en lugar de varchar
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- OBTENER ÓRDENES (normalizado)
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_ordenes(int, int, text);
DROP FUNCTION IF EXISTS fun_obtener_ordenes(int, int, int);

CREATE OR REPLACE FUNCTION fun_obtener_ordenes(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL
)
RETURNS TABLE (
    id int,
    producto_id int,
    producto_nombre text,
    cantidad int,
    fecha_creacion timestamp,
    fecha_inicio timestamp,
    fecha_fin_estimada timestamp,
    fecha_fin_real timestamp,
    artesano_id int,
    artesano_nombre text,
    estado text,
    prioridad text,
    observaciones text,
    observaciones_terminada text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        op.id,
        op.producto_id,
        pr.nombre::text AS producto_nombre,
        op.cantidad,
        op.fecha_creacion,
        op.fecha_inicio,
        op.fecha_fin_estimada,
        op.fecha_fin_real,
        op.artesano_id,
        (a.nombre || ' ' || a.apellido)::text AS artesano_nombre,
        eo.nombre::text AS estado,
        p.nombre::text AS prioridad,
        op.observaciones::text,
        (
            SELECT ct.observaciones::text
            FROM creaciones_terminadas ct
            WHERE ct.orden_id = op.id
            ORDER BY ct.fecha_terminado DESC, ct.id DESC
            LIMIT 1
        ) AS observaciones_terminada
    FROM ordenes_produccion op
    INNER JOIN productos pr ON op.producto_id = pr.id
    LEFT JOIN artesanos a ON op.artesano_id = a.id
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    LEFT JOIN prioridades p ON op.prioridad_id = p.id
    WHERE (par_estado IS NULL OR eo.nombre = par_estado)
    ORDER BY p.id DESC, op.fecha_creacion DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_ordenes(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado_id int DEFAULT NULL
)
RETURNS TABLE (
    id int,
    producto_id int,
    producto_nombre text,
    cantidad int,
    fecha_creacion timestamp,
    fecha_inicio timestamp,
    fecha_fin_estimada timestamp,
    fecha_fin_real timestamp,
    artesano_id int,
    artesano_nombre text,
    estado_id int,
    estado_nombre text,
    estado_color text,
    prioridad_id int,
    prioridad_nombre text,
    prioridad_color text,
    observaciones text,
    observaciones_terminada text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        op.id,
        op.producto_id,
        pr.nombre::text AS producto_nombre,
        op.cantidad,
        op.fecha_creacion,
        op.fecha_inicio,
        op.fecha_fin_estimada,
        op.fecha_fin_real,
        op.artesano_id,
        (a.nombre || ' ' || a.apellido)::text AS artesano_nombre,
        op.estado_id,
        eo.nombre::text AS estado_nombre,
        eo.color::text AS estado_color,
        op.prioridad_id,
        p.nombre::text AS prioridad_nombre,
        p.color::text AS prioridad_color,
        op.observaciones::text,
        (
            SELECT ct.observaciones::text
            FROM creaciones_terminadas ct
            WHERE ct.orden_id = op.id
            ORDER BY ct.fecha_terminado DESC, ct.id DESC
            LIMIT 1
        ) AS observaciones_terminada
    FROM ordenes_produccion op
    INNER JOIN productos pr ON op.producto_id = pr.id
    LEFT JOIN artesanos a ON op.artesano_id = a.id
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    LEFT JOIN prioridades p ON op.prioridad_id = p.id
    WHERE (par_estado_id IS NULL OR op.estado_id = par_estado_id)
    ORDER BY p.id DESC, op.fecha_creacion DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

-- ============================================
-- OBTENER CATÁLOGOS PARA DROPDOWNS
-- ============================================

CREATE OR REPLACE FUNCTION fun_obtener_estados_orden()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    color text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT eo.id, eo.nombre::text, eo.descripcion::text, eo.color::text
    FROM estados_orden eo
    WHERE eo.activo = TRUE
    ORDER BY eo.id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_prioridades()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    color text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT p.id, p.nombre::text, p.descripcion::text, p.color::text
    FROM prioridades p
    WHERE p.activo = TRUE
    ORDER BY p.id DESC;
END;
$$;

-- ============================================
-- CREAR ORDEN
-- ============================================

DROP FUNCTION IF EXISTS fun_crear_orden(int, int, int, int, text, text);
DROP FUNCTION IF EXISTS fun_crear_orden(int, int, int, int, int, int);

CREATE OR REPLACE FUNCTION fun_crear_orden(
    par_producto_id int,
    par_cantidad int DEFAULT 1,
    par_artesano_id int DEFAULT NULL,
    par_prioridad_id int DEFAULT 2,
    par_observaciones text DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    -- Generar código único de orden
    INSERT INTO ordenes_produccion (
        producto_id, cantidad, artesano_id,
        estado_id, prioridad_id, observaciones
    )
    VALUES (
        par_producto_id, par_cantidad, par_artesano_id,
        1, -- pendiente
        par_prioridad_id, par_observaciones
    )
    RETURNING id INTO v_id;
    
    RETURN v_id;
END;
$$;

-- ============================================
-- ACTUALIZAR ORDEN
-- ============================================

DROP FUNCTION IF EXISTS fun_actualizar_orden(int, int, int, int, text, text);
DROP FUNCTION IF EXISTS fun_actualizar_orden(int, int, int, int, int, int, text);

CREATE OR REPLACE FUNCTION fun_actualizar_orden(
    par_id int,
    par_producto_id int DEFAULT NULL,
    par_cantidad int DEFAULT NULL,
    par_artesano_id int DEFAULT NULL,
    par_estado_id int DEFAULT NULL,
    par_prioridad_id int DEFAULT NULL,
    par_observaciones text DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE ordenes_produccion
    SET 
        producto_id = COALESCE(par_producto_id, producto_id),
        cantidad = COALESCE(par_cantidad, cantidad),
        artesano_id = COALESCE(par_artesano_id, artesano_id),
        estado_id = COALESCE(par_estado_id, estado_id),
        prioridad_id = COALESCE(par_prioridad_id, prioridad_id),
        observaciones = COALESCE(par_observaciones, observaciones),
        -- Actualizar fechas según estado
        fecha_inicio = CASE 
            WHEN par_estado_id = 2 AND fecha_inicio IS NULL THEN NOW() 
            ELSE fecha_inicio 
        END,
        fecha_fin_real = CASE 
            WHEN par_estado_id IN (3, 4) AND fecha_fin_real IS NULL THEN NOW() 
            ELSE fecha_fin_real 
        END
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Orden no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- CAMBIAR ESTADO DE ORDEN
-- ============================================

CREATE OR REPLACE FUNCTION fun_cambiar_estado_orden(
    par_id int,
    par_estado_id int
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    -- Validar que el estado exista
    IF NOT EXISTS (SELECT 1 FROM estados_orden WHERE id = par_estado_id AND activo = TRUE) THEN
        RAISE EXCEPTION 'Estado no válido.' USING ERRCODE = 'P0003';
    END IF;
    
    UPDATE ordenes_produccion
    SET 
        estado_id = par_estado_id,
        fecha_inicio = CASE 
            WHEN par_estado_id = 2 AND fecha_inicio IS NULL THEN NOW() 
            ELSE fecha_inicio 
        END,
        fecha_fin_real = CASE 
            WHEN par_estado_id IN (3, 4) AND fecha_fin_real IS NULL THEN NOW() 
            ELSE fecha_fin_real 
        END
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Orden no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- ELIMINAR ORDEN (soft delete)
-- ============================================

CREATE OR REPLACE FUNCTION fun_eliminar_orden(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    -- Cancelamos la orden en lugar de eliminarla
    UPDATE ordenes_produccion 
    SET estado_id = 4 -- cancelada
    WHERE id = par_id 
      AND estado_id NOT IN (3, 4); -- No se puede cancelar si ya terminó o ya está cancelada
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Orden no encontrada o ya finalizada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- OBTENER TIPOS DE MATERIAL (para recetas)
-- ============================================

CREATE OR REPLACE FUNCTION fun_obtener_tipos_material()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT tm.id, tm.nombre::text, tm.descripcion::text
    FROM tipos_material tm
    WHERE tm.activo = TRUE
    ORDER BY tm.id;
END;
$$;

-- ============================================
-- OBTENER NIVELES DE CALIDAD
-- ============================================

CREATE OR REPLACE FUNCTION fun_obtener_niveles_calidad()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT nc.id, nc.nombre::text, nc.descripcion::text
    FROM niveles_calidad nc
    WHERE nc.activo = TRUE
    ORDER BY nc.id;
END;
$$;
