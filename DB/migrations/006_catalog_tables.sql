-- Migración 006: Crear tablas de catálogo para áreas, tipos de oro y tipos de proveedor
-- Fecha: 2026-01-18
-- Descripción: Normalizar valores de catálogo que actualmente se almacenan como texto libre

BEGIN;
SET search_path TO joyeria, seguridad, public;

-- ============================================================================
-- 1. CREAR TABLA DE ÁREAS
-- ============================================================================
CREATE TABLE IF NOT EXISTS areas (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Poblar con valores existentes
INSERT INTO areas (codigo, nombre, descripcion, orden) VALUES
    ('general', 'General', 'Área general', 1),
    ('produccion', 'Producción', 'Área de producción', 2),
    ('almacen', 'Almacén', 'Área de almacenamiento', 3),
    ('ventas', 'Ventas', 'Área de ventas', 4),
    ('oficina', 'Oficina', 'Área administrativa', 5),
    ('taller', 'Taller', 'Área de taller', 6)
ON CONFLICT (codigo) DO NOTHING;

-- ============================================================================
-- 2. CREAR TABLA DE TIPOS DE ORO
-- ============================================================================
CREATE TABLE IF NOT EXISTS tipos_oro (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    kilates DECIMAL(4,2),
    pureza_porcentaje DECIMAL(5,2),
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Poblar con valores existentes
INSERT INTO tipos_oro (codigo, nombre, kilates, pureza_porcentaje, orden) VALUES
    ('10k', '10 Kilates', 10, 41.67, 1),
    ('14k', '14 Kilates', 14, 58.33, 2),
    ('18k', '18 Kilates', 18, 75.00, 3),
    ('22k', '22 Kilates', 22, 91.67, 4),
    ('24k', '24 Kilates', 24, 99.99, 5)
ON CONFLICT (codigo) DO NOTHING;

-- ============================================================================
-- 3. CREAR TABLA DE TIPOS DE PROVEEDOR
-- ============================================================================
CREATE TABLE IF NOT EXISTS tipos_proveedor (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Poblar con valores existentes
INSERT INTO tipos_proveedor (codigo, nombre, descripcion, orden) VALUES
    ('oro', 'Oro', 'Proveedor de oro y metales preciosos', 1),
    ('insumos', 'Insumos', 'Proveedor de insumos generales', 2),
    ('maquinaria', 'Maquinaria', 'Proveedor de maquinaria y equipos', 3)
ON CONFLICT (codigo) DO NOTHING;

-- ============================================================================
-- 4. AGREGAR COLUMNAS FK A UBICACIONES
-- ============================================================================
ALTER TABLE ubicaciones ADD COLUMN IF NOT EXISTS area_id INTEGER REFERENCES areas(id);

-- Migrar datos existentes de ubicaciones
UPDATE ubicaciones u
SET area_id = a.id
FROM areas a
WHERE LOWER(u.area) = a.codigo;

-- Si algún registro no tiene match, asignar 'general'
UPDATE ubicaciones
SET area_id = (SELECT id FROM areas WHERE codigo = 'general')
WHERE area_id IS NULL;

-- Hacer la FK NOT NULL después de migrar
ALTER TABLE ubicaciones ALTER COLUMN area_id SET NOT NULL;

-- Crear índice
CREATE INDEX IF NOT EXISTS idx_ubicaciones_area_id ON ubicaciones(area_id);

-- ============================================================================
-- 5. AGREGAR COLUMNAS FK A INVENTARIO_ORO
-- ============================================================================
ALTER TABLE inventario_oro ADD COLUMN IF NOT EXISTS tipo_oro_id INTEGER REFERENCES tipos_oro(id);

-- Migrar datos existentes de inventario_oro
UPDATE inventario_oro io
SET tipo_oro_id = t.id
FROM tipos_oro t
WHERE LOWER(io.tipo_oro) = t.codigo;

-- Si algún registro no tiene match, intentar por nombre
UPDATE inventario_oro io
SET tipo_oro_id = t.id
FROM tipos_oro t
WHERE io.tipo_oro_id IS NULL 
  AND (LOWER(io.tipo_oro) LIKE '%' || CAST(t.kilates AS TEXT) || '%' 
       OR io.tipo_oro = t.nombre);

-- Hacer la FK NOT NULL después de migrar
ALTER TABLE inventario_oro ALTER COLUMN tipo_oro_id SET NOT NULL;

-- Crear índice
CREATE INDEX IF NOT EXISTS idx_inventario_oro_tipo_id ON inventario_oro(tipo_oro_id);

-- ============================================================================
-- 6. AGREGAR COLUMNAS FK A PROVEEDORES
-- ============================================================================
ALTER TABLE proveedores ADD COLUMN IF NOT EXISTS tipo_proveedor_id INTEGER REFERENCES tipos_proveedor(id);

-- Migrar datos existentes de proveedores
UPDATE proveedores p
SET tipo_proveedor_id = t.id
FROM tipos_proveedor t
WHERE LOWER(p.tipo) = t.codigo;

-- Hacer la FK NOT NULL después de migrar
ALTER TABLE proveedores ALTER COLUMN tipo_proveedor_id SET NOT NULL;

-- Crear índice
CREATE INDEX IF NOT EXISTS idx_proveedores_tipo_id ON proveedores(tipo_proveedor_id);

-- ============================================================================
-- 7. MANTENER COLUMNAS ANTIGUAS POR COMPATIBILIDAD (opcional)
-- ============================================================================
-- Por ahora mantenemos las columnas antiguas para retrocompatibilidad
-- En una migración futura (007) las eliminaremos después de actualizar todas las queries

COMMENT ON TABLE areas IS 'Catálogo de áreas para ubicaciones';
COMMENT ON TABLE tipos_oro IS 'Catálogo de tipos/quilates de oro';
COMMENT ON TABLE tipos_proveedor IS 'Catálogo de tipos de proveedor';

COMMENT ON COLUMN ubicaciones.area_id IS 'FK a tabla de catálogo de áreas (reemplaza columna area)';
COMMENT ON COLUMN inventario_oro.tipo_oro_id IS 'FK a tabla de catálogo de tipos de oro (reemplaza columna tipo_oro)';
COMMENT ON COLUMN proveedores.tipo_proveedor_id IS 'FK a tabla de catálogo de tipos de proveedor (reemplaza columna tipo)';

COMMIT;
