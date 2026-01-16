-- ============================================
-- MIGRACIÓN: Añadir tabla de ubicaciones
-- Fecha: 2026-01-16
-- Descripción: Crea tabla ubicaciones y modifica 
--              inventario_maquinaria e inventario_insumos
--              para usar FK en lugar de texto libre
-- ============================================

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
COMMENT ON COLUMN ubicaciones.area IS 'Área general (Almacén, Taller, Oficina, etc.)';

-- 2. Insertar ubicaciones basadas en datos existentes
INSERT INTO ubicaciones (codigo, nombre, area)
SELECT DISTINCT 
    UPPER(REPLACE(REPLACE(ubicacion, ' ', '-'), '.', '')) AS codigo,
    ubicacion AS nombre,
    CASE 
        WHEN LOWER(ubicacion) LIKE '%almacen%' OR LOWER(ubicacion) LIKE '%almacén%' THEN 'Almacén'
        WHEN LOWER(ubicacion) LIKE '%taller%' THEN 'Taller'
        WHEN LOWER(ubicacion) LIKE '%oficina%' THEN 'Oficina'
        WHEN LOWER(ubicacion) LIKE '%bodega%' THEN 'Bodega'
        ELSE 'General'
    END AS area
FROM inventario_maquinaria
WHERE ubicacion IS NOT NULL AND ubicacion <> ''
ON CONFLICT (codigo) DO NOTHING;

INSERT INTO ubicaciones (codigo, nombre, area)
SELECT DISTINCT 
    UPPER(REPLACE(REPLACE(ubicacion, ' ', '-'), '.', '')) AS codigo,
    ubicacion AS nombre,
    CASE 
        WHEN LOWER(ubicacion) LIKE '%almacen%' OR LOWER(ubicacion) LIKE '%almacén%' THEN 'Almacén'
        WHEN LOWER(ubicacion) LIKE '%taller%' THEN 'Taller'
        WHEN LOWER(ubicacion) LIKE '%oficina%' THEN 'Oficina'
        WHEN LOWER(ubicacion) LIKE '%bodega%' THEN 'Bodega'
        ELSE 'General'
    END AS area
FROM inventario_insumos
WHERE ubicacion IS NOT NULL AND ubicacion <> ''
ON CONFLICT (codigo) DO NOTHING;

-- 3. Añadir columna ubicacion_id a inventario_maquinaria
ALTER TABLE inventario_maquinaria 
ADD COLUMN IF NOT EXISTS ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL;

-- 4. Migrar datos existentes de ubicacion a ubicacion_id
UPDATE inventario_maquinaria im
SET ubicacion_id = u.id
FROM ubicaciones u
WHERE im.ubicacion IS NOT NULL 
  AND im.ubicacion <> ''
  AND UPPER(REPLACE(REPLACE(im.ubicacion, ' ', '-'), '.', '')) = u.codigo;

-- 5. Añadir columna ubicacion_id a inventario_insumos
ALTER TABLE inventario_insumos 
ADD COLUMN IF NOT EXISTS ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL;

-- 6. Migrar datos existentes de ubicacion a ubicacion_id
UPDATE inventario_insumos ii
SET ubicacion_id = u.id
FROM ubicaciones u
WHERE ii.ubicacion IS NOT NULL 
  AND ii.ubicacion <> ''
  AND UPPER(REPLACE(REPLACE(ii.ubicacion, ' ', '-'), '.', '')) = u.codigo;

-- 7. Crear índices
CREATE INDEX IF NOT EXISTS idx_ubicaciones_area ON ubicaciones(area);
CREATE INDEX IF NOT EXISTS idx_ubicaciones_activo ON ubicaciones(activo);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_ubicacion_id ON inventario_maquinaria(ubicacion_id);
CREATE INDEX IF NOT EXISTS idx_inventario_insumos_ubicacion_id ON inventario_insumos(ubicacion_id);

-- 8. Insertar ubicaciones por defecto si la tabla quedó vacía
INSERT INTO ubicaciones (codigo, nombre, area) VALUES
    ('ALM-01', 'Almacén Principal', 'Almacén'),
    ('TAL-01', 'Taller de Producción', 'Taller'),
    ('TAL-02', 'Taller de Acabados', 'Taller'),
    ('BOD-01', 'Bodega de Insumos', 'Bodega'),
    ('OFI-01', 'Oficina Administrativa', 'Oficina')
ON CONFLICT (codigo) DO NOTHING;

COMMIT;

-- ============================================
-- NOTA: Las columnas 'ubicacion' (texto) en 
-- inventario_maquinaria e inventario_insumos
-- se mantienen por compatibilidad. Se pueden
-- eliminar en una migración futura cuando
-- se confirme que todo funciona correctamente.
-- ============================================
