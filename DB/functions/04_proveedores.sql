-- ============================================
-- FUNCIONES DE PROVEEDORES
-- ============================================

SET search_path TO joyeria, seguridad, public;

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
-- FUNCIONES CRUD PARA PROVEEDORES
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_proveedor(
    par_nombre text,
    par_tipo_proveedor_id int DEFAULT 1,
    par_contacto text DEFAULT NULL,
    par_telefono text DEFAULT NULL,
    par_email text DEFAULT NULL,
    par_direccion text DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
    v_tipo text;
BEGIN
    SELECT lower(t.nombre)::text INTO v_tipo
    FROM tipos_proveedor t
    WHERE t.id = par_tipo_proveedor_id;

    INSERT INTO proveedores (nombre, tipo_proveedor_id, tipo, contacto, telefono, email, direccion, activo)
    VALUES (par_nombre, par_tipo_proveedor_id, v_tipo, par_contacto, par_telefono, par_email, par_direccion, TRUE)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear proveedor.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_proveedor(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_tipo_proveedor_id int DEFAULT NULL,
    par_contacto text DEFAULT NULL,
    par_telefono text DEFAULT NULL,
    par_email text DEFAULT NULL,
    par_direccion text DEFAULT NULL,
    par_activo boolean DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE proveedores
    SET 
        nombre = COALESCE(par_nombre, nombre),
        tipo_proveedor_id = COALESCE(par_tipo_proveedor_id, tipo_proveedor_id),
        tipo = CASE
            WHEN par_tipo_proveedor_id IS NOT NULL THEN (
                SELECT lower(t.nombre)::text FROM tipos_proveedor t WHERE t.id = par_tipo_proveedor_id
            )
            ELSE tipo
        END,
        contacto = COALESCE(par_contacto, contacto),
        telefono = COALESCE(par_telefono, telefono),
        email = COALESCE(par_email, email),
        direccion = COALESCE(par_direccion, direccion),
        activo = COALESCE(par_activo, activo)
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Proveedor no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar proveedor.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_proveedor(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE proveedores SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Proveedor no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar proveedor.' USING ERRCODE = SQLSTATE;
END;
$$;
