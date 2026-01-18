-- ============================================
-- MIGRACIÃ“N: AÃ±adir tabla de ubicaciones
-- Fecha: 2026-01-16
-- DescripciÃ³n: Crea tabla ubicaciones y modifica 
--              inventario_maquinaria e inventario_insumos
--              para usar FK en lugar de texto libre
-- ============================================

SET search_path TO joyeria, seguridad, public;

BEGIN;

-- 1. Crear tabla de ubicaciones
CREATE TABLE IF NOT EXISTS ubicaciones (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    area VARCHAR(50),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE ubicaciones IS 'Catálogo de ubicaciones físicas para inventario y maquinaria';
COMMENT ON COLUMN ubicaciones.codigo IS 'Código único de la ubicación (ej: ALM-01, TAL-02)';
COMMENT ON COLUMN ubicaciones.area IS 'Area general (Almacen, Taller, Oficina, etc.)';

-- 2. Añadir columna ubicacion_id a inventario_maquinaria
ALTER TABLE inventario_maquinaria 
ADD COLUMN IF NOT EXISTS ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL;

-- 3. Migrar datos existentes de inventario_maquinaria si existe la columna ubicacion
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'joyeria'
          AND table_name = 'inventario_maquinaria'
          AND column_name = 'ubicacion'
    ) THEN
        INSERT INTO ubicaciones (codigo, nombre, area)
        SELECT DISTINCT 
            UPPER(REPLACE(REPLACE(ubicacion, ' ', '-'), '.', '')) AS codigo,
            ubicacion AS nombre,
            CASE 
                WHEN LOWER(ubicacion) LIKE '%almacen%' THEN 'Almacen'
                WHEN LOWER(ubicacion) LIKE '%taller%' THEN 'Taller'
                WHEN LOWER(ubicacion) LIKE '%oficina%' THEN 'Oficina'
                WHEN LOWER(ubicacion) LIKE '%bodega%' THEN 'Bodega'
                ELSE 'General'
            END AS area
        FROM inventario_maquinaria
        WHERE ubicacion IS NOT NULL AND ubicacion <> ''
        ON CONFLICT (codigo) DO NOTHING;

        UPDATE inventario_maquinaria im
        SET ubicacion_id = u.id
        FROM ubicaciones u
        WHERE im.ubicacion IS NOT NULL 
          AND im.ubicacion <> ''
          AND UPPER(REPLACE(REPLACE(im.ubicacion, ' ', '-'), '.', '')) = u.codigo;
    END IF;
END $$;

-- 4. AÇñadir columna ubicacion_id a inventario_insumos
ALTER TABLE inventario_insumos 
ADD COLUMN IF NOT EXISTS ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL;

-- 5. Migrar datos existentes de inventario_insumos si existe la columna ubicacion
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'joyeria'
          AND table_name = 'inventario_insumos'
          AND column_name = 'ubicacion'
    ) THEN
        INSERT INTO ubicaciones (codigo, nombre, area)
        SELECT DISTINCT 
            UPPER(REPLACE(REPLACE(ubicacion, ' ', '-'), '.', '')) AS codigo,
            ubicacion AS nombre,
            CASE 
                WHEN LOWER(ubicacion) LIKE '%almacen%' THEN 'Almacen'
                WHEN LOWER(ubicacion) LIKE '%taller%' THEN 'Taller'
                WHEN LOWER(ubicacion) LIKE '%oficina%' THEN 'Oficina'
                WHEN LOWER(ubicacion) LIKE '%bodega%' THEN 'Bodega'
                ELSE 'General'
            END AS area
        FROM inventario_insumos
        WHERE ubicacion IS NOT NULL AND ubicacion <> ''
        ON CONFLICT (codigo) DO NOTHING;

        UPDATE inventario_insumos ii
        SET ubicacion_id = u.id
        FROM ubicaciones u
        WHERE ii.ubicacion IS NOT NULL 
          AND ii.ubicacion <> ''
          AND UPPER(REPLACE(REPLACE(ii.ubicacion, ' ', '-'), '.', '')) = u.codigo;
    END IF;
END $$;
-- 6. Crear Ã­ndices
CREATE INDEX IF NOT EXISTS idx_ubicaciones_area ON ubicaciones(area);
CREATE INDEX IF NOT EXISTS idx_ubicaciones_activo ON ubicaciones(activo);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_ubicacion_id ON inventario_maquinaria(ubicacion_id);
CREATE INDEX IF NOT EXISTS idx_inventario_insumos_ubicacion_id ON inventario_insumos(ubicacion_id);

-- 7. Insertar ubicaciones por defecto si la tabla quedó vacía
INSERT INTO ubicaciones (codigo, nombre, area) VALUES
    ('ALM-01', 'Almacen Principal', 'Almacen'),
    ('TAL-01', 'Taller de Produccion', 'Taller'),
    ('TAL-02', 'Taller de Acabados', 'Taller'),
    ('BOD-01', 'Bodega de Insumos', 'Bodega'),
    ('OFI-01', 'Oficina Administrativa', 'Oficina')
ON CONFLICT (codigo) DO NOTHING;

COMMIT;

-- ============================================
-- NOTA: Las columnas 'ubicacion' (texto) en 
-- inventario_maquinaria e inventario_insumos
-- se mantienen por compatibilidad. Se pueden
-- eliminar en una migraciÃ³n futura cuando
-- se confirme que todo funciona correctamente.
-- ============================================






