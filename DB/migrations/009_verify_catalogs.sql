-- Verificar que existan registros con ID=1 en las tablas de catálogo
-- Esto asegura que los valores por defecto funcionen correctamente

SET search_path TO joyeria, seguridad, public;

-- Verificar areas (ID=1 debe existir)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM areas WHERE id = 1) THEN
        RAISE NOTICE 'Advertencia: No existe area con ID=1. Se recomienda crear un área "General" con ID=1';
    END IF;
END $$;

-- Verificar tipos_oro (ID=1 debe existir)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tipos_oro WHERE id = 1) THEN
        RAISE NOTICE 'Advertencia: No existe tipo_oro con ID=1. Se recomienda crear un tipo por defecto con ID=1';
    END IF;
END $$;

-- Verificar tipos_proveedor (ID=1 debe existir)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tipos_proveedor WHERE id = 1) THEN
        RAISE NOTICE 'Advertencia: No existe tipo_proveedor con ID=1. Se recomienda crear un tipo por defecto con ID=1';
    END IF;
END $$;

-- Mostrar los registros actuales
SELECT 'Áreas actuales:' AS info;
SELECT id, codigo, nombre, activo FROM areas ORDER BY id;

SELECT 'Tipos de oro actuales:' AS info;
SELECT id, codigo, nombre, kilates, activo FROM tipos_oro ORDER BY id;

SELECT 'Tipos de proveedor actuales:' AS info;
SELECT id, codigo, nombre, activo FROM tipos_proveedor ORDER BY id;
