-- ============================================
-- FUNCIONES DE AUTENTICACION
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION seguridad.fun_iniciar_sesion(
    par_username text
)
RETURNS TABLE (
    codigo int,
    mensaje text,
    username text,
    id_usuario int,
    rolid int,
    hash text,
    nombre text
)
LANGUAGE plpgsql
AS $$
DECLARE
    w_usuario record;
BEGIN
    SELECT u.username, u.id_usuario, u.rolid, u.clave, u.nombre
      INTO w_usuario
    FROM seguridad.seg_usuario u
    WHERE u.username = par_username
      AND u.deleted_at IS NULL
    LIMIT 1;

    IF NOT FOUND OR w_usuario.username IS NULL THEN
        RETURN QUERY SELECT
            401,
            'Usuario o contrasena incorrectos.',
            NULL::text,
            NULL::int,
            NULL::int,
            NULL::text,
            NULL::text;
        RETURN;
    END IF;

    RETURN QUERY SELECT
        200,
        'Inicio de sesion exitoso.',
        w_usuario.username,
        w_usuario.id_usuario,
        w_usuario.rolid,
        w_usuario.clave,
        w_usuario.nombre;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de autenticacion no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;
