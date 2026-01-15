-- ============================================
-- FUNCIONES DE PROVEEDORES
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION fun_obtener_proveedores(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_tipo text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    nombre text,
    tipo text,
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
        p.tipo::text,
        p.contacto::text,
        p.telefono::text,
        p.email::text,
        p.direccion::text,
        p.activo,
        p.fecha_registro
    FROM proveedores p
    WHERE (par_tipo IS NULL OR p.tipo = par_tipo)
      AND (par_activo IS NULL OR p.activo = par_activo)
    ORDER BY p.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de proveedores no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;
