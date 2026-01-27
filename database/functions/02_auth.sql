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

-- -----------------------------------------------------------------------------
-- fun_crear_reset_token: Genera token de recuperación de contraseña
-- Parámetros:
--   par_username: Username del usuario
--   par_email: Email del usuario
--   par_ip: IP del solicitante (opcional)
--   par_user_agent: User-Agent del navegador (opcional)
-- Retorna:
--   Token generado si el usuario y email existen, mensaje genérico si no
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_crear_reset_token(
    par_username TEXT,
    par_email VARCHAR(255),
    par_ip INET DEFAULT NULL,
    par_user_agent VARCHAR(500) DEFAULT NULL
)
RETURNS TABLE (
    codigo INTEGER,
    mensaje TEXT,
    token TEXT,
    usuario_id INTEGER,
    nombre TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_usuario_id INTEGER;
    v_nombre TEXT;
    v_token TEXT;
    v_recent_count INTEGER;
BEGIN
    -- Buscar usuario por username y email
    SELECT u.id_usuario, u.nombre INTO v_usuario_id, v_nombre
    FROM seguridad.seg_usuario u
    WHERE u.username = par_username
      AND LOWER(u.email) = LOWER(par_email)
      AND u.deleted_at IS NULL;

    IF v_usuario_id IS NULL THEN
        -- No revelar si el usuario/email existe o no (seguridad)
        RETURN QUERY SELECT 200, 'Si el usuario y el email existen, recibiras instrucciones.'::TEXT, NULL::TEXT, NULL::INTEGER, NULL::TEXT;
        RETURN;
    END IF;

    -- Rate limit por usuario: max 5 solicitudes en 15 minutos
    SELECT COUNT(1) INTO v_recent_count
    FROM seguridad.seg_password_reset r
    WHERE r.usuario_id = v_usuario_id
      AND r.created_at > NOW() - INTERVAL '15 minutes';

    IF v_recent_count >= 5 THEN
        RETURN QUERY SELECT 429, 'Demasiadas solicitudes. Intenta mas tarde.'::TEXT, NULL::TEXT, v_usuario_id, v_nombre;
        RETURN;
    END IF;

    -- Invalidar tokens anteriores del mismo usuario
    UPDATE seguridad.seg_password_reset
    SET used_at = NOW()
    WHERE usuario_id = v_usuario_id AND used_at IS NULL;

    -- Generar nuevo token (64 caracteres hex = 32 bytes)
    v_token := encode(gen_random_bytes(32), 'hex');

    -- Insertar token con expiración de 1 hora
    INSERT INTO seguridad.seg_password_reset 
        (usuario_id, token, expires_at, ip_address, user_agent)
    VALUES 
        (v_usuario_id, v_token, NOW() + INTERVAL '1 hour', par_ip, par_user_agent);

    RETURN QUERY SELECT 200, 'Token creado exitosamente.'::TEXT, v_token, v_usuario_id, v_nombre;
END;
$$;

-- -----------------------------------------------------------------------------
-- fun_validar_reset_token: Verifica si un token de recuperación es válido
-- Parámetros:
--   par_token: Token de 64 caracteres hex
-- Retorna:
--   Datos del usuario si el token es válido, error si no
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_validar_reset_token(
    par_token VARCHAR(64)
)
RETURNS TABLE (
    codigo INTEGER,
    mensaje TEXT,
    usuario_id INTEGER,
    username TEXT,
    nombre TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_reset RECORD;
BEGIN
    -- Buscar token válido (no usado, no expirado)
    SELECT r.usuario_id, r.expires_at, u.username, u.nombre
    INTO v_reset
    FROM seguridad.seg_password_reset r
    JOIN seguridad.seg_usuario u ON r.usuario_id = u.id_usuario
    WHERE r.token = par_token
      AND r.used_at IS NULL
      AND r.expires_at > NOW()
      AND u.deleted_at IS NULL;

    IF v_reset IS NULL THEN
        RETURN QUERY SELECT 400, 'Token inválido o expirado.'::TEXT, NULL::INTEGER, NULL::TEXT, NULL::TEXT;
        RETURN;
    END IF;

    RETURN QUERY SELECT 200, 'Token válido.'::TEXT, v_reset.usuario_id, v_reset.username, v_reset.nombre;
END;
$$;

-- -----------------------------------------------------------------------------
-- fun_reset_password: Cambia la contraseña usando un token válido
-- Parámetros:
--   par_token: Token de 64 caracteres hex
--   par_new_password_hash: Hash de la nueva contraseña (generado en PHP)
-- Retorna:
--   Código 200 si éxito, 400 si token inválido
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_reset_password(
    par_token VARCHAR(64),
    par_new_password_hash TEXT
)
RETURNS TABLE (
    codigo INTEGER,
    mensaje TEXT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_usuario_id INTEGER;
BEGIN
    -- Validar token primero
    SELECT r.usuario_id INTO v_usuario_id
    FROM seguridad.seg_password_reset r
    JOIN seguridad.seg_usuario u ON r.usuario_id = u.id_usuario
    WHERE r.token = par_token
      AND r.used_at IS NULL
      AND r.expires_at > NOW()
      AND u.deleted_at IS NULL;

    IF v_usuario_id IS NULL THEN
        RETURN QUERY SELECT 400, 'Token inválido o expirado.'::TEXT;
        RETURN;
    END IF;

    -- Actualizar contraseña
    UPDATE seguridad.seg_usuario
    SET clave = par_new_password_hash,
        updated_at = NOW()
    WHERE id_usuario = v_usuario_id;

    -- Marcar token como usado
    UPDATE seguridad.seg_password_reset
    SET used_at = NOW()
    WHERE token = par_token;

    -- Invalidar todas las sesiones del usuario
    UPDATE seguridad.seg_login
    SET estado_token = FALSE
    WHERE usuarioid = v_usuario_id AND estado_token = TRUE;

    RETURN QUERY SELECT 200, 'Contraseña actualizada exitosamente.'::TEXT;
END;
$$;

-- -----------------------------------------------------------------------------
-- fun_cleanup_reset_tokens: Elimina tokens expirados (para cron job)
-- Parámetros:
--   par_days: Días de antigüedad para eliminar (default 7)
-- Retorna:
--   Número de registros eliminados
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_cleanup_reset_tokens(
    par_days INTEGER DEFAULT 7
)
RETURNS INTEGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_deleted INTEGER;
BEGIN
    DELETE FROM seguridad.seg_password_reset
    WHERE expires_at < NOW() - (par_days || ' days')::INTERVAL;
    
    GET DIAGNOSTICS v_deleted = ROW_COUNT;
    RETURN v_deleted;
END;
$$;

-- -----------------------------------------------------------------------------
-- fun_actualizar_usuario_estado: Activa o desactiva usuarios (soft-delete)
-- Parámetros:
--   par_id: ID del usuario
--   par_activo: true para activar, false para desactivar
-- Retorna:
--   TRUE si la operación fue exitosa
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_actualizar_usuario_estado(
    par_id int,
    par_activo boolean
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE seguridad.seg_usuario
    SET deleted_at = CASE WHEN par_activo THEN NULL ELSE CURRENT_TIMESTAMP END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_usuario = par_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Usuario no encontrado.' USING ERRCODE = 'P0002';
    END IF;

    IF par_activo = FALSE THEN
        UPDATE seguridad.seg_login
        SET estado_token = FALSE
        WHERE usuarioid = par_id AND estado_token = TRUE;
    END IF;

    RETURN TRUE;
END;
$$;

-- -----------------------------------------------------------------------------
-- fun_crear_usuario: Crea un nuevo usuario del sistema
-- Parámetros:
--   par_username: Username único
--   par_nombre: Nombre
--   par_clave: Hash bcrypt de la contraseña (generado en PHP)
--   par_rolid: ID del rol
--   par_email: Email (opcional)
--   par_apellido: Apellido (obligatorio si rol=OPERADOR)
--   par_especialidad_id: ID de especialidad (opcional, se guarda en artesano_especialidad)
--   par_telefono: Teléfono (opcional)
-- Retorna:
--   ID del usuario creado
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION seguridad.fun_crear_usuario(
    par_username text,
    par_nombre text,
    par_clave text,
    par_rolid int,
    par_email text DEFAULT NULL,
    par_apellido text DEFAULT NULL,
    par_especialidad_id int DEFAULT NULL,
    par_telefono text DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
    v_role int;
    v_exists int;
    v_email text;
    v_apellido text;
    v_especialidad_id int;
    v_especialidad_exists int;
    v_telefono text;
    v_nombre_seg text;
    v_artesano_id int;
BEGIN
    par_username := btrim(par_username);
    par_nombre := btrim(par_nombre);
    par_clave := NULLIF(par_clave, '');
    v_email := NULLIF(btrim(par_email), '');
    v_apellido := NULLIF(btrim(par_apellido), '');
    v_especialidad_id := par_especialidad_id;
    v_telefono := NULLIF(btrim(par_telefono), '');
    v_nombre_seg := par_nombre;
    IF v_apellido IS NOT NULL AND v_apellido <> '' THEN
        v_nombre_seg := par_nombre || ' ' || v_apellido;
    END IF;
    IF v_especialidad_id IS NOT NULL AND v_especialidad_id <= 0 THEN
        v_especialidad_id := NULL;
    END IF;

    IF par_username IS NULL OR par_username = '' THEN
        RAISE EXCEPTION 'Username requerido.' USING ERRCODE = 'P0001';
    END IF;

    IF par_nombre IS NULL OR par_nombre = '' THEN
        RAISE EXCEPTION 'Nombre requerido.' USING ERRCODE = 'P0001';
    END IF;

    IF par_clave IS NULL THEN
        RAISE EXCEPTION 'Contrasena requerida.' USING ERRCODE = 'P0001';
    END IF;

    IF par_rolid IS NULL OR par_rolid <= 0 THEN
        RAISE EXCEPTION 'Rol invalido.' USING ERRCODE = 'P0001';
    END IF;

    SELECT r.id_rol INTO v_role
    FROM seguridad.seg_rol r
    WHERE r.id_rol = par_rolid
      AND r.deleted_at IS NULL;

    IF v_role IS NULL THEN
        RAISE EXCEPTION 'Rol invalido.' USING ERRCODE = 'P0001';
    END IF;

    IF par_rolid = 2 AND (v_apellido IS NULL OR v_apellido = '') THEN
        RAISE EXCEPTION 'Apellido requerido.' USING ERRCODE = 'P0001';
    END IF;

    SELECT u.id_usuario INTO v_exists
    FROM seguridad.seg_usuario u
    WHERE u.username = par_username
    LIMIT 1;

    IF v_exists IS NOT NULL THEN
        RAISE EXCEPTION 'Usuario ya existe.' USING ERRCODE = 'P0001';
    END IF;

    IF v_email IS NOT NULL THEN
        SELECT u.id_usuario INTO v_exists
        FROM seguridad.seg_usuario u
        WHERE LOWER(u.email) = LOWER(v_email)
        LIMIT 1;

        IF v_exists IS NOT NULL THEN
            RAISE EXCEPTION 'Email ya registrado.' USING ERRCODE = 'P0001';
        END IF;
    END IF;

    IF par_rolid = 2 AND v_especialidad_id IS NOT NULL THEN
        SELECT e.id INTO v_especialidad_exists
        FROM joyeria.cat_especialidad e
        WHERE e.id = v_especialidad_id
          AND e.activo = TRUE;

        IF v_especialidad_exists IS NULL THEN
            RAISE EXCEPTION 'Especialidad invalida.' USING ERRCODE = 'P0001';
        END IF;
    END IF;

    INSERT INTO seguridad.seg_usuario (username, nombre, clave, email, rolid)
    VALUES (par_username, v_nombre_seg, par_clave, v_email, par_rolid)
    RETURNING id_usuario INTO v_id;

    IF par_rolid = 2 THEN
        INSERT INTO joyeria.artesanos (usuario_id, nombre, apellido, telefono, email, activo)
        VALUES (v_id, par_nombre, v_apellido, v_telefono, v_email, TRUE)
        RETURNING id INTO v_artesano_id;

        IF v_especialidad_id IS NOT NULL THEN
            INSERT INTO joyeria.artesano_especialidad (artesano_id, especialidad_id)
            VALUES (v_artesano_id, v_especialidad_id)
            ON CONFLICT (artesano_id, especialidad_id) DO NOTHING;
        END IF;
    END IF;

    RETURN v_id;
END;
$$;
