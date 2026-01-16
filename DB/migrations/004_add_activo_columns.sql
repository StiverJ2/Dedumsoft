-- ============================================
-- MIGRACIÓN 004: Añadir columna `activo` para soft-delete
-- Fecha: 2026-01-16
-- Descripción: Añade columna booleana `activo` a las tablas 
--              que soportan CRUD para habilitar borrado lógico.
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- TABLAS AFECTADAS (esquema joyeria):
-- - inventario_oro (nueva)
-- - inventario_insumos (nueva)
-- - inventario_maquinaria (nueva)
-- - productos (ya existe, verificar)
-- - artesanos (ya existe, verificar)
-- - ordenes_produccion (nueva - usar estado 'cancelada' como alternativa)
--
-- TABLAS YA CON `activo`:
-- - proveedores (ya tiene activo BOOLEAN DEFAULT TRUE)
-- - ubicaciones (ya tiene activo BOOLEAN NOT NULL DEFAULT TRUE)
-- - productos (ya tiene activo BOOLEAN DEFAULT TRUE)
-- - artesanos (ya tiene activo BOOLEAN DEFAULT TRUE)
-- ============================================

-- ============================================
-- 1. INVENTARIO_ORO
-- ============================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'joyeria' 
          AND table_name = 'inventario_oro' 
          AND column_name = 'activo'
    ) THEN
        ALTER TABLE inventario_oro 
            ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;
        
        RAISE NOTICE 'Columna activo añadida a inventario_oro';
    ELSE
        RAISE NOTICE 'Columna activo ya existe en inventario_oro';
    END IF;
END $$;

-- Índice parcial para consultas de registros activos
CREATE INDEX IF NOT EXISTS idx_inventario_oro_activo 
    ON inventario_oro (id) 
    WHERE activo = TRUE;

-- ============================================
-- 2. INVENTARIO_INSUMOS
-- ============================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'joyeria' 
          AND table_name = 'inventario_insumos' 
          AND column_name = 'activo'
    ) THEN
        ALTER TABLE inventario_insumos 
            ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;
        
        RAISE NOTICE 'Columna activo añadida a inventario_insumos';
    ELSE
        RAISE NOTICE 'Columna activo ya existe en inventario_insumos';
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_inventario_insumos_activo 
    ON inventario_insumos (id) 
    WHERE activo = TRUE;

-- ============================================
-- 3. INVENTARIO_MAQUINARIA
-- ============================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'joyeria' 
          AND table_name = 'inventario_maquinaria' 
          AND column_name = 'activo'
    ) THEN
        ALTER TABLE inventario_maquinaria 
            ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE;
        
        RAISE NOTICE 'Columna activo añadida a inventario_maquinaria';
    ELSE
        RAISE NOTICE 'Columna activo ya existe en inventario_maquinaria';
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_activo 
    ON inventario_maquinaria (id) 
    WHERE activo = TRUE;

-- ============================================
-- 4. VERIFICAR EXISTENTES (solo asegurar índices)
-- ============================================

-- Proveedores (ya tiene activo)
CREATE INDEX IF NOT EXISTS idx_proveedores_activo 
    ON proveedores (id) 
    WHERE activo = TRUE;

-- Ubicaciones (ya tiene activo)
CREATE INDEX IF NOT EXISTS idx_ubicaciones_activo 
    ON ubicaciones (id) 
    WHERE activo = TRUE;

-- Productos (ya tiene activo)
CREATE INDEX IF NOT EXISTS idx_productos_activo 
    ON productos (id) 
    WHERE activo = TRUE;

-- Artesanos (ya tiene activo)
CREATE INDEX IF NOT EXISTS idx_artesanos_activo 
    ON artesanos (id) 
    WHERE activo = TRUE;

-- ============================================
-- 5. RESUMEN DE TABLAS CON SOFT-DELETE
-- ============================================
-- Después de esta migración, las siguientes tablas tienen `activo`:
-- 
-- | Tabla                  | activo | Índice                          |
-- |------------------------|--------|---------------------------------|
-- | proveedores            | ✓      | idx_proveedores_activo          |
-- | ubicaciones            | ✓      | idx_ubicaciones_activo          |
-- | productos              | ✓      | idx_productos_activo            |
-- | artesanos              | ✓      | idx_artesanos_activo            |
-- | inventario_oro         | ✓ NEW  | idx_inventario_oro_activo       |
-- | inventario_insumos     | ✓ NEW  | idx_inventario_insumos_activo   |
-- | inventario_maquinaria  | ✓ NEW  | idx_inventario_maquinaria_activo|
--
-- Para ordenes_produccion se usa el campo `estado` con valor 'cancelada'
-- para representar órdenes eliminadas lógicamente.
-- ============================================

COMMENT ON COLUMN inventario_oro.activo IS 'Soft-delete: FALSE = registro eliminado lógicamente';
COMMENT ON COLUMN inventario_insumos.activo IS 'Soft-delete: FALSE = registro eliminado lógicamente';
COMMENT ON COLUMN inventario_maquinaria.activo IS 'Soft-delete: FALSE = registro eliminado lógicamente';
