-- Script complementario al seed.sql que incluye los catálogos normalizados
-- Este archivo debe ejecutarse DESPUÉS de la migración 006
-- Fecha: 2026-01-18

BEGIN;
SET search_path TO joyeria, public;

-- ============================================
-- CATÁLOGOS BASE
-- ============================================

-- ÁREAS
INSERT INTO areas (codigo, nombre, descripcion, orden, activo) VALUES
('general', 'General', 'Área general de uso múltiple', 1, TRUE),
('produccion', 'Producción', 'Área dedicada a la producción y manufactura', 2, TRUE),
('almacen', 'Almacén', 'Área de almacenamiento de materiales', 3, TRUE),
('ventas', 'Ventas', 'Área comercial y de atención al cliente', 4, TRUE),
('oficina', 'Oficina', 'Área administrativa y de gestión', 5, TRUE),
('taller', 'Taller', 'Taller de trabajo especializado', 6, TRUE)
ON CONFLICT (codigo) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- TIPOS DE ORO
INSERT INTO tipos_oro (codigo, nombre, kilates, pureza_porcentaje, descripcion, orden, activo) VALUES
('10k', '10 Kilates', 10.00, 41.67, 'Oro de 10 quilates - 41.67% de pureza', 1, TRUE),
('14k', '14 Kilates', 14.00, 58.33, 'Oro de 14 quilates - 58.33% de pureza', 2, TRUE),
('18k', '18 Kilates', 18.00, 75.00, 'Oro de 18 quilates - 75% de pureza', 3, TRUE),
('22k', '22 Kilates', 22.00, 91.67, 'Oro de 22 quilates - 91.67% de pureza', 4, TRUE),
('24k', '24 Kilates', 24.00, 99.99, 'Oro puro de 24 quilates - 99.99% de pureza', 5, TRUE)
ON CONFLICT (codigo) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    kilates = EXCLUDED.kilates,
    pureza_porcentaje = EXCLUDED.pureza_porcentaje,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- TIPOS DE PROVEEDOR
INSERT INTO tipos_proveedor (codigo, nombre, descripcion, orden, activo) VALUES
('oro', 'Oro', 'Proveedores de oro y metales preciosos', 1, TRUE),
('insumos', 'Insumos', 'Proveedores de insumos y materiales generales', 2, TRUE),
('maquinaria', 'Maquinaria', 'Proveedores de maquinaria, equipos y herramientas', 3, TRUE)
ON CONFLICT (codigo) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

COMMIT;

-- ============================================
-- NOTAS DE USO
-- ============================================
-- Después de ejecutar este script, los INSERT en seed.sql deben actualizarse para:
--
-- PROVEEDORES: Usar tipo_proveedor_id en lugar de tipo
-- ANTES: INSERT INTO proveedores (nombre, tipo, ...) VALUES ('Proveedor X', 'oro', ...)
-- DESPUÉS: INSERT INTO proveedores (nombre, tipo, tipo_proveedor_id, ...) 
--          VALUES ('Proveedor X', 'oro', (SELECT id FROM tipos_proveedor WHERE codigo = 'oro'), ...)
--
-- UBICACIONES: Usar area_id en lugar de solo area
-- ANTES: INSERT INTO ubicaciones (nombre, area) VALUES ('Bodega 1', 'Almacen')
-- DESPUÉS: INSERT INTO ubicaciones (nombre, area, area_id) 
--          VALUES ('Bodega 1', 'Almacen', (SELECT id FROM areas WHERE codigo = 'almacen'))
--
-- INVENTARIO_ORO: Usar tipo_oro_id en lugar de solo tipo_oro
-- ANTES: INSERT INTO inventario_oro (tipo_oro, ...) VALUES ('18k', ...)
-- DESPUÉS: INSERT INTO inventario_oro (tipo_oro, tipo_oro_id, ...) 
--          VALUES ('18k', (SELECT id FROM tipos_oro WHERE codigo = '18k'), ...)
