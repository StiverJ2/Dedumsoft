-- ============================================================================
-- DEDUMSOFT - ELIMINAR TODAS LAS FUNCIONES
-- ============================================================================
--
-- Script de utilidad para eliminar todas las funciones del schema joyeria.
-- Útil para desarrollo cuando se necesita recrear funciones desde cero.
--
-- ADVERTENCIA:
-- ¡Este script elimina TODAS las funciones que empiezan con 'fun_'!
-- Solo usar en desarrollo, NUNCA en producción sin backup.
--
-- USO:
--   psql -d db_dedumsoft -f DB/nuke_functions.sql
--
-- DESPUÉS DE EJECUTAR:
-- Reinstalar funciones con:
--   psql -d db_dedumsoft -f DB/functions/01_triggers.sql
--   psql -d db_dedumsoft -f DB/functions/02_auth.sql
--   ... (demás archivos de funciones)
--
-- ============================================================================

-- Eliminar TODAS las funciones del schema joyeria
SET search_path TO joyeria, seguridad, public;

-- Bloque anónimo que itera sobre todas las funciones 'fun_*'
DO $$
DECLARE
    r RECORD;
BEGIN
    -- Buscar todas las funciones que empiezan con 'fun_' en el schema joyeria
    FOR r IN 
        SELECT proname, pg_get_function_identity_arguments(oid) as args 
        FROM pg_proc 
        WHERE pronamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'joyeria')
          AND proname LIKE 'fun_%'
    LOOP
        -- Eliminar cada función encontrada
        EXECUTE 'DROP FUNCTION IF EXISTS joyeria.' || r.proname || '(' || r.args || ') CASCADE';
        RAISE NOTICE 'Dropped: %(%)', r.proname, r.args;
    END LOOP;
END $$;
