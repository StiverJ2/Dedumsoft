-- ============================================
-- FUNCIONES DE ORDENES DE PRODUCCION
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION fun_obtener_ordenes(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL
)
RETURNS TABLE (
    id int,
    codigo_orden text,
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
    observaciones text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        op.id,
        op.codigo_orden::text,
        op.producto_id,
        pr.nombre::text AS producto_nombre,
        op.cantidad,
        op.fecha_creacion,
        op.fecha_inicio,
        op.fecha_fin_estimada,
        op.fecha_fin_real,
        op.artesano_id,
        a.nombre || ' ' || a.apellido AS artesano_nombre,
        op.estado::text,
        op.prioridad::text,
        op.observaciones
    FROM ordenes_produccion op
    INNER JOIN productos pr ON op.producto_id = pr.id
    LEFT JOIN artesanos a ON op.artesano_id = a.id
    WHERE (par_estado IS NULL OR op.estado = par_estado)
    ORDER BY op.fecha_creacion DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;
