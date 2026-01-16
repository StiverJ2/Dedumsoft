-- ============================================
-- MIGRACIÓN: Eliminar columna codigo de ubicaciones
-- Fecha: 2026-01-16
-- Descripción: Elimina la columna codigo redundante
--              de la tabla ubicaciones (el id SERIAL
--              es suficiente como identificador)
-- Depende de: 001_add_ubicaciones.sql
-- ============================================

BEGIN;

-- 1. Eliminar la constraint UNIQUE de codigo (si existe)
ALTER TABLE ubicaciones 
DROP CONSTRAINT IF EXISTS ubicaciones_codigo_key;

-- 2. Eliminar la columna codigo
ALTER TABLE ubicaciones 
DROP COLUMN IF EXISTS codigo;

COMMIT;
