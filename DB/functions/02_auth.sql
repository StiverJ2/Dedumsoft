-- ============================================================================
-- FUNCIONES: AUTENTICACIÓN
-- ============================================================================
--
-- Funciones para el sistema de autenticación y autorización.
-- Trabajan con las tablas del schema 'seguridad'.
--
-- FUNCIONES INCLUIDAS:
-- - fun_iniciar_sesion(username): Valida usuario y retorna datos de sesión
--
-- SEGURIDAD:
-- - La validación del hash de contraseña se hace en PHP (password_verify)
-- - Esta función solo retorna el hash para comparación
-- - Usuarios eliminados (deleted_at NOT NULL) no pueden iniciar sesión
--
-- RETORNO DE fun_iniciar_sesion:
-- +-------------+--------+------------------------------------------+
-- | Campo       | Tipo   | Descripción                              |
-- +-------------+--------+------------------------------------------+
-- | codigo      | int    | 200=éxito, 401=no encontrado             |
-- | mensaje     | text   | Mensaje descriptivo                      |
-- | username    | text   | Nombre de usuario                        |
-- | id_usuario  | int    | ID del usuario                           |
-- | rolid       | int    | ID del rol (1=ADMIN, 2=OPERADOR, 3=LECTURA) |
-- | hash        | text   | Hash bcrypt de la contraseña             |
-- | nombre      | text   | Nombre completo del usuario              |
-- | artesano_id | int    | ID del artesano (si es OPERADOR)         |
-- +-------------+--------+------------------------------------------+
--
-- ============================================================================
-- FUNCIONES DE AUTENTICACION
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- -----------------------------------------------------------------------------
-- fun_iniciar_sesion: Busca usuario por username y retorna datos de sesión
-- Parámetros:
--   par_username: Nombre de usuario a buscar
-- Retorna:
--   Registro con datos del usuario o código 401 si no existe
-- -----------------------------------------------------------------------------
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
    nombre text,
    artesano_id int
)
LANGUAGE plpgsql
AS $$
DECLARE
    w_usuario record;
    w_artesano_id int;
BEGIN
    -- Buscar usuario activo (no eliminado)
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
            NULL::text,
            NULL::int;
        RETURN;
    END IF;

    -- Si es OPERADOR (rol 2), buscar su artesano_id
    w_artesano_id := NULL;
    IF w_usuario.rolid = 2 THEN
        SELECT a.id INTO w_artesano_id
        FROM joyeria.artesanos a
        WHERE a.usuario_id = w_usuario.id_usuario
          AND a.activo = TRUE
        LIMIT 1;
    END IF;

    RETURN QUERY SELECT
        200,
        'Inicio de sesion exitoso.',
        w_usuario.username,
        w_usuario.id_usuario,
        w_usuario.rolid,
        w_usuario.clave,
        w_usuario.nombre,
        w_artesano_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de autenticacion no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;
