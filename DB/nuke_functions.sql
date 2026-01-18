-- Eliminar TODAS las funciones del schema joyeria
SET search_path TO joyeria, seguridad, public;

DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN 
        SELECT proname, pg_get_function_identity_arguments(oid) as args 
        FROM pg_proc 
        WHERE pronamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'joyeria')
          AND proname LIKE 'fun_%'
    LOOP
        EXECUTE 'DROP FUNCTION IF EXISTS joyeria.' || r.proname || '(' || r.args || ') CASCADE';
        RAISE NOTICE 'Dropped: %(%)', r.proname, r.args;
    END LOOP;
END $$;
