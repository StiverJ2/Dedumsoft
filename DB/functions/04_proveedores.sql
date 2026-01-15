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
        p.nombre,
        p.tipo,
        p.contacto,
        p.telefono,
        p.email,
        p.direccion,
        p.activo,
        p.fecha_registro
    FROM proveedores p
    WHERE (par_tipo IS NULL OR p.tipo = par_tipo)
      AND (par_activo IS NULL OR p.activo = par_activo)
    ORDER BY p.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;
