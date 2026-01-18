-- Script para aplicar funciones actualizadas (post-migración 007)
-- Este script debe ejecutarse después de migración 007

SET search_path TO joyeria, seguridad, public;

-- Aplicar funciones de ubicaciones actualizadas
\i DB/functions/07_ubicaciones.sql

-- Aplicar funciones de inventario actualizadas
\i DB/functions/03_inventario.sql

-- Aplicar funciones de proveedores actualizadas
\i DB/functions/04_proveedores.sql

-- Verificar que las funciones se crearon correctamente
SELECT 'Verificando funciones actualizadas...' AS mensaje;

-- Test ubicaciones
SELECT fun_obtener_ubicaciones(0, 5, NULL, TRUE) AS test_ubicaciones LIMIT 1;

-- Test inventario_oro  
SELECT fun_obtener_inventario_oro(0, 5, NULL, TRUE) AS test_inventario LIMIT 1;

-- Test proveedores
SELECT fun_obtener_proveedores(0, 5, NULL, TRUE) AS test_proveedores LIMIT 1;

SELECT 'Funciones actualizadas correctamente' AS mensaje;
