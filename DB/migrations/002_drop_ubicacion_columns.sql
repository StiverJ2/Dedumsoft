-- ============================================
-- MIGRACIÓN: Eliminar columnas ubicacion redundantes
-- Fecha: 2026-01-16
-- Descripción: Elimina las columnas de texto 'ubicacion'
--              de inventario_maquinaria e inventario_insumos
--              después de migrar a ubicacion_id (FK)
-- Depende de: 001_add_ubicaciones.sql
-- ============================================

BEGIN;

-- 1. Verificar que la migración anterior fue exitosa
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'inventario_maquinaria' 
        AND column_name = 'ubicacion_id'
    ) THEN
        RAISE EXCEPTION 'La columna ubicacion_id no existe en inventario_maquinaria. Ejecute primero 001_add_ubicaciones.sql';
    END IF;
    
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'inventario_insumos' 
        AND column_name = 'ubicacion_id'
    ) THEN
        RAISE EXCEPTION 'La columna ubicacion_id no existe en inventario_insumos. Ejecute primero 001_add_ubicaciones.sql';
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
