-- ============================================
-- MIGRACIÓN: Eliminar columnas ubicacion redundantes
-- Fecha: 2026-01-16
-- Descripción: Elimina las columnas de texto 'ubicacion'
--              de inventario_maquinaria e inventario_insumos
--              después de migrar a ubicacion_id (FK)
-- Depende de: 001_add_ubicaciones.sql
-- ============================================

SET search_path TO joyeria, seguridad, public;

BEGIN;

-- 1. Verificar existencia de columnas (sin abortar)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'joyeria'
          AND table_name = 'inventario_maquinaria' 
          AND column_name = 'ubicacion_id'
    ) THEN
        RAISE NOTICE 'La columna ubicacion_id no existe en inventario_maquinaria.';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'joyeria'
          AND table_name = 'inventario_insumos' 
          AND column_name = 'ubicacion_id'
    ) THEN
        RAISE NOTICE 'La columna ubicacion_id no existe en inventario_insumos.';
    END IF;
END $$;
-- 2. Eliminar columna ubicacion de inventario_maquinaria
ALTER TABLE inventario_maquinaria 
DROP COLUMN IF EXISTS ubicacion;

-- 3. Eliminar columna ubicacion de inventario_insumos
ALTER TABLE inventario_insumos 
DROP COLUMN IF EXISTS ubicacion;

COMMIT;

-- ============================================
-- NOTA: Esta migración es irreversible.
-- Los datos de ubicacion ya fueron migrados
-- a la tabla ubicaciones en 001_add_ubicaciones.sql
-- ============================================



