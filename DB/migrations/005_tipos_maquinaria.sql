-- ============================================================
-- Migration 005: Normalizar inventario_maquinaria.tipo
-- Crear tabla tipos_maquinaria y FK
-- ============================================================

SET search_path TO joyeria, seguridad, public;

BEGIN;

-- 1. Crear tabla de referencia para tipos de maquinaria
CREATE TABLE IF NOT EXISTS tipos_maquinaria (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Insertar tipos base (se pueden agregar más después)
INSERT INTO tipos_maquinaria (codigo, nombre, descripcion) VALUES
    ('FUNDICION', 'Fundicion', 'Equipos para fundicion de metales'),
    ('CORTE', 'Corte', 'Maquinas de corte y precision'),
    ('PULIDO', 'Pulido', 'Equipos de pulido y acabado'),
    ('SOLDADURA', 'Soldadura', 'Equipos de soldadura'),
    ('GRABADO', 'Grabado', 'Maquinas de grabado y marcado'),
    ('LAMINADO', 'Laminado', 'Equipos de laminacion'),
    ('LIMPIEZA', 'Limpieza', 'Equipos de limpieza ultrasonica y quimica'),
    ('MEDICION', 'Medicion', 'Instrumentos de medicion y pesaje'),
    ('OTRO', 'Otro', 'Otros tipos de maquinaria')
ON CONFLICT (codigo) DO NOTHING;

-- 3. Agregar columna tipo_maquinaria_id a inventario_maquinaria
ALTER TABLE inventario_maquinaria
ADD COLUMN IF NOT EXISTS tipo_maquinaria_id INTEGER;

-- 4. Migrar datos existentes: mapear texto libre a tipos conocidos
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'joyeria'
          AND table_name = 'inventario_maquinaria'
          AND column_name = 'tipo'
    ) THEN
        -- Primero insertar tipos unicos que no existan
        INSERT INTO tipos_maquinaria (codigo, nombre)
        SELECT DISTINCT 
            UPPER(REGEXP_REPLACE(tipo, '[^a-zA-Z0-9]', '_', 'g')),
            INITCAP(tipo)
        FROM inventario_maquinaria
        WHERE tipo IS NOT NULL 
          AND tipo != ''
          AND tipo NOT LIKE '%<%'  -- Excluir HTML corrupto
          AND NOT EXISTS (
            SELECT 1 FROM tipos_maquinaria tm 
            WHERE LOWER(tm.nombre) = LOWER(inventario_maquinaria.tipo)
          )
        ON CONFLICT (codigo) DO NOTHING;

        -- Actualizar tipo_maquinaria_id basado en el texto tipo existente
        UPDATE inventario_maquinaria im
        SET tipo_maquinaria_id = tm.id
        FROM tipos_maquinaria tm
        WHERE LOWER(tm.nombre) = LOWER(im.tipo)
          AND im.tipo_maquinaria_id IS NULL;
    END IF;
END $$;
-- 6. Asignar tipo "Otro" a registros sin match o con HTML corrupto
UPDATE inventario_maquinaria
SET tipo_maquinaria_id = (SELECT id FROM tipos_maquinaria WHERE codigo = 'OTRO')
WHERE tipo_maquinaria_id IS NULL;

-- 7. Agregar FK constraint
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'fk_maquinaria_tipo'
          AND conrelid = 'inventario_maquinaria'::regclass
    ) THEN
        ALTER TABLE inventario_maquinaria
        ADD CONSTRAINT fk_maquinaria_tipo
        FOREIGN KEY (tipo_maquinaria_id) 
        REFERENCES tipos_maquinaria(id);
    END IF;
END $$;
-- 8. Eliminar columna tipo antigua (texto libre)
ALTER TABLE inventario_maquinaria
DROP COLUMN IF EXISTS tipo;

COMMIT;




