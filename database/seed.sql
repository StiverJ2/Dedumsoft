-- ============================================================================
-- DEDUMSOFT - DATOS INICIALES (SEED)
-- ============================================================================
--
-- Carga los datos iniciales necesarios para el funcionamiento del sistema.
-- Incluye valores de tablas lookup, usuarios por defecto y datos de prueba.
--
-- CONTENIDO:
-- 1. Tablas lookup (estados, prioridades, tipos)
-- 2. Sistema de seguridad (roles, menús, permisos)
-- 3. Usuarios por defecto
-- 4. Catálogos básicos (productos, áreas, tipos de oro)
-- 5. Datos de prueba opcionales
--
-- USUARIOS CREADOS:
-- +------------+---------------+----------+------------------------+
-- | Usuario    | Contraseña    | Rol      | Acceso                 |
-- +------------+---------------+----------+------------------------+
-- | admin      | Admin123!     | ADMIN    | Acceso total           |
-- | artesano   | Artesano123!  | OPERADOR | Producción y config   |
-- +------------+---------------+----------+------------------------+
--
-- MENÚS DEL SISTEMA:
-- 1. Dashboard - Panel general
-- 2. Inventario - Gestión de inventario
-- 3. Producción - Órdenes y control
-- 4. Reportes - Reportes del sistema
-- 5. Usuarios - Administración de usuarios
-- 6. Proveedores - Gestión de proveedores
-- 7. Configuración - Preferencias y ajustes
--
-- EJECUCIÓN:
--   psql -d db_dedumsoft -f DB/seed.sql
--
-- NOTA:
-- Usa ON CONFLICT para permitir re-ejecución sin errores (idempotente).
--
-- ============================================================================
-- DEDUMSOFT - DATOS INICIALES (SEED)
-- Fecha: 2026-01-18
-- ============================================

BEGIN;
SET search_path TO joyeria, seguridad, public;

-- ============================================
-- TABLAS LOOKUP (valores que reemplazan CHECK constraints)
-- ============================================

-- Estados de maquinaria
INSERT INTO estados_maquinaria (id, nombre, descripcion, color, orden) VALUES
(1, 'operativa', 'Maquinaria funcionando correctamente', 'success', 1),
(2, 'mantenimiento', 'En mantenimiento programado', 'warning', 2),
(3, 'averiada', 'Fuera de servicio por averia', 'danger', 3),
(4, 'fuera_servicio', 'Retirada permanentemente del servicio', 'muted', 4)
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Estados de orden de produccion
INSERT INTO estados_orden (id, nombre, descripcion, color, orden) VALUES
(1, 'pendiente', 'Orden creada, pendiente de iniciar', 'muted', 1),
(2, 'en_proceso', 'Orden en proceso de fabricacion', 'info', 2),
(3, 'terminada', 'Orden completada exitosamente', 'success', 3),
(4, 'cancelada', 'Orden cancelada', 'danger', 4),
(5, 'pausada', 'Orden pausada temporalmente', 'warning', 5)
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Prioridades
INSERT INTO prioridades (id, nombre, descripcion, color, orden) VALUES
(1, 'baja', 'Prioridad baja', 'muted', 1),
(2, 'media', 'Prioridad normal', 'info', 2),
(3, 'alta', 'Prioridad alta', 'warning', 3),
(4, 'urgente', 'Prioridad urgente', 'danger', 4)
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Tipos de material
INSERT INTO tipos_material (id, nombre, descripcion) VALUES
(1, 'oro', 'Material de oro'),
(2, 'insumo', 'Insumo general')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion;

-- Niveles de calidad
INSERT INTO niveles_calidad (id, nombre, descripcion, orden) VALUES
(1, 'A', 'Calidad premium - Sin defectos', 1),
(2, 'B', 'Calidad estandar - Defectos menores', 2),
(3, 'C', 'Calidad basica - Requiere retrabajo', 3)
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion;

-- Tipos de movimiento
INSERT INTO tipos_movimiento (id, nombre, descripcion) VALUES
(1, 'entrada', 'Entrada de material al inventario'),
(2, 'salida', 'Salida de material del inventario'),
(3, 'ajuste', 'Ajuste de inventario'),
(4, 'transferencia', 'Transferencia entre ubicaciones'),
(5, 'consumo', 'Consumo en produccion')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion;

-- Actualizar secuencias de tablas lookup
SELECT setval('estados_maquinaria_id_seq', (SELECT MAX(id) FROM estados_maquinaria));
SELECT setval('estados_orden_id_seq', (SELECT MAX(id) FROM estados_orden));
SELECT setval('prioridades_id_seq', (SELECT MAX(id) FROM prioridades));
SELECT setval('tipos_material_id_seq', (SELECT MAX(id) FROM tipos_material));
SELECT setval('niveles_calidad_id_seq', (SELECT MAX(id) FROM niveles_calidad));

-- ============================================
-- LEGACY SEGURIDAD (seg_*)
-- ============================================
INSERT INTO seguridad.seg_rol (id_rol, nombre, comentario) VALUES
(1, 'ADMIN', 'Acceso total al sistema'),
(2, 'OPERADOR', 'Operacion general sin administracion total'),
(3, 'LECTURA', 'Solo lectura')
ON CONFLICT (id_rol) DO UPDATE
SET nombre = EXCLUDED.nombre, comentario = EXCLUDED.comentario;

INSERT INTO seguridad.seg_menu (id_menu, nombre, comentario, ruta) VALUES
(1, 'Dashboard', 'Panel general', '/dashboard'),
(2, 'Inventario', 'Gestion de inventario', '/inventario'),
(3, 'Produccion', 'Ordenes y control de produccion', '/produccion'),
(4, 'Reportes', 'Reportes del sistema', '/reportes'),
(5, 'Usuarios', 'Administracion de usuarios y roles', '/usuarios'),
(6, 'Proveedores', 'Gestion de proveedores', '/proveedores'),
(7, 'Configuracion', 'Preferencias y ajustes', '/configuracion')
ON CONFLICT (id_menu) DO UPDATE
SET nombre = EXCLUDED.nombre, comentario = EXCLUDED.comentario, ruta = EXCLUDED.ruta;

INSERT INTO seguridad.seg_menurol (abrir, guardar, editar, eliminar, rolid, menuid) VALUES
(TRUE, TRUE, TRUE, TRUE, 1, 1),
(TRUE, TRUE, TRUE, TRUE, 1, 2),
(TRUE, TRUE, TRUE, TRUE, 1, 3),
(TRUE, TRUE, TRUE, TRUE, 1, 4),
(TRUE, TRUE, TRUE, TRUE, 1, 5),
(TRUE, TRUE, TRUE, TRUE, 1, 6),
(TRUE, TRUE, TRUE, TRUE, 1, 7),
(TRUE, FALSE, FALSE, FALSE, 2, 3),
(TRUE, FALSE, FALSE, FALSE, 2, 7),
(TRUE, FALSE, FALSE, FALSE, 3, 1),
(TRUE, FALSE, FALSE, FALSE, 3, 2),
(TRUE, FALSE, FALSE, FALSE, 3, 3),
(TRUE, FALSE, FALSE, FALSE, 3, 4),
(FALSE, FALSE, FALSE, FALSE, 3, 5),
(TRUE, FALSE, FALSE, FALSE, 3, 6),
(FALSE, FALSE, FALSE, FALSE, 3, 7)
ON CONFLICT (rolid, menuid) DO UPDATE
SET abrir = EXCLUDED.abrir, guardar = EXCLUDED.guardar, editar = EXCLUDED.editar, eliminar = EXCLUDED.eliminar;

-- ============================================
-- USUARIOS POR DEFECTO (legacy)
-- ============================================

-- ADMIN: admin / Admin123!
INSERT INTO seguridad.seg_usuario (username, nombre, clave, rolid)
VALUES (
    'admin',
    'Administrador',
    '$argon2id$v=19$m=65536,t=4,p=1$MGQvRTBneVJhQlJFTGhmeg$V0Z6bsVV2cABUX7qo/joYRYmp0ovvxMNW0p3zFo32Aw',
    1
)
ON CONFLICT (username) DO NOTHING;

-- ARTESANO: artesano / Artesano123!
-- Password hash generado con: php -r "echo password_hash('Artesano123!', PASSWORD_ARGON2ID);"
INSERT INTO seguridad.seg_usuario (username, nombre, clave, rolid)
VALUES (
    'artesano',
    'Roberto Martinez',
    '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI',
    2
)
ON CONFLICT (username) DO NOTHING;

-- ============================================
-- CATALOGOS BASE
-- ============================================

-- AREAS
INSERT INTO areas (id, nombre, descripcion, orden, activo) VALUES
(1, 'General', 'Area general de uso multiple', 1, TRUE),
(2, 'Produccion', 'Area dedicada a la produccion y manufactura', 2, TRUE),
(3, 'Almacen', 'Area de almacenamiento de materiales', 3, TRUE),
(4, 'Ventas', 'Area comercial y de atencion al cliente', 4, TRUE),
(5, 'Oficina', 'Area administrativa y de gestion', 5, TRUE),
(6, 'Taller', 'Taller de trabajo especializado', 6, TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- TIPOS DE ORO
INSERT INTO tipos_oro (id, nombre, kilates, pureza_porcentaje, descripcion, orden, activo) VALUES
(1, '10 Kilates', 10.00, 41.67, 'Oro de 10 quilates - 41.67% de pureza', 1, TRUE),
(2, '14 Kilates', 14.00, 58.33, 'Oro de 14 quilates - 58.33% de pureza', 2, TRUE),
(3, '18 Kilates', 18.00, 75.00, 'Oro de 18 quilates - 75% de pureza', 3, TRUE),
(4, '22 Kilates', 22.00, 91.67, 'Oro de 22 quilates - 91.67% de pureza', 4, TRUE),
(5, '24 Kilates', 24.00, 99.99, 'Oro puro de 24 quilates - 99.99% de pureza', 5, TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    kilates = EXCLUDED.kilates,
    pureza_porcentaje = EXCLUDED.pureza_porcentaje,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- TIPOS DE PROVEEDOR
INSERT INTO tipos_proveedor (id, nombre, descripcion, orden, activo) VALUES
(1, 'Oro', 'Proveedores de oro y metales preciosos', 1, TRUE),
(2, 'Insumos', 'Proveedores de insumos y materiales generales', 2, TRUE),
(3, 'Maquinaria', 'Proveedores de maquinaria, equipos y herramientas', 3, TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- TIPOS DE MAQUINARIA
INSERT INTO tipos_maquinaria (id, nombre, descripcion, orden, activo) VALUES
(1, 'Fundicion', 'Equipos para fundicion de metales', 1, TRUE),
(2, 'Corte', 'Herramientas y equipos de corte', 2, TRUE),
(3, 'Pulido', 'Equipos de pulido y acabado', 3, TRUE),
(4, 'Soldadura', 'Equipos de soldadura', 4, TRUE),
(5, 'Medicion', 'Instrumentos de medicion y precision', 5, TRUE),
(6, 'Grabado', 'Equipos de grabado y marcado', 6, TRUE),
(7, 'Otro', 'Otros equipos y herramientas', 7, TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    orden = EXCLUDED.orden;

-- Actualizar secuencias de catalogos
SELECT setval('areas_id_seq', (SELECT MAX(id) FROM areas));
SELECT setval('tipos_oro_id_seq', (SELECT MAX(id) FROM tipos_oro));
SELECT setval('tipos_proveedor_id_seq', (SELECT MAX(id) FROM tipos_proveedor));
SELECT setval('tipos_maquinaria_id_seq', (SELECT MAX(id) FROM tipos_maquinaria));

-- ============================================
-- UBICACIONES
-- ============================================
INSERT INTO ubicaciones (id, nombre, descripcion, area_id, capacidad, activo) VALUES
(1, 'Recepcion Principal', 'Area de recepcion de materiales', 1, 100, TRUE),
(2, 'Bodega A', 'Bodega principal de materiales', 3, 500, TRUE),
(3, 'Bodega B', 'Bodega secundaria', 3, 300, TRUE),
(4, 'Taller Principal', 'Espacio principal de trabajo', 6, 50, TRUE),
(5, 'Area de Fundicion', 'Zona de hornos y fundicion', 2, 20, TRUE),
(6, 'Mesa de Pulido', 'Estacion de pulido', 2, 10, TRUE),
(7, 'Vitrina de Exhibicion', 'Area de muestra de productos', 4, 200, TRUE),
(8, 'Caja Fuerte', 'Almacenamiento seguro de oro', 3, 50, TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    area_id = EXCLUDED.area_id;

SELECT setval('ubicaciones_id_seq', (SELECT MAX(id) FROM ubicaciones));

-- ============================================
-- PROVEEDORES DE EJEMPLO
-- ============================================
INSERT INTO proveedores (id, nombre, tipo_proveedor_id, tipo, contacto, telefono, email, direccion, rfc, activo) VALUES
(1, 'Metales Preciosos del Norte', 1, 'oro', 'Juan Perez', '555-1234', 'ventas@metalesnorte.com', 'Calle Oro 123, Centro', 'MPN123456789', TRUE),
(2, 'Insumos Joyeros SA', 2, 'insumos', 'Maria Garcia', '555-5678', 'contacto@insumosjoyeros.com', 'Av. Industrial 456', 'IJS987654321', TRUE),
(3, 'Maquinaria Industrial MX', 3, 'maquinaria', 'Carlos Lopez', '555-9012', 'ventas@maquinariamx.com', 'Blvd. Manufactura 789', 'MIM456789123', TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('proveedores_id_seq', (SELECT MAX(id) FROM proveedores));

-- ============================================
-- ARTESANOS DE EJEMPLO
-- Vinculados con usuarios OPERADOR via usuario_id
-- ============================================
INSERT INTO artesanos (id, usuario_id, nombre, apellido, especialidad, telefono, email, fecha_ingreso, activo) VALUES
(1, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'artesano'), 'Roberto', 'Martinez', 'Joyeria fina', '555-1111', 'roberto@dedumsoft.com', '2020-01-15', TRUE),
(2, NULL, 'Ana', 'Sanchez', 'Grabado y detalle', '555-2222', 'ana@dedumsoft.com', '2021-03-20', TRUE),
(3, NULL, 'Miguel', 'Torres', 'Fundicion', '555-3333', 'miguel@dedumsoft.com', '2019-06-10', TRUE)
ON CONFLICT (id) DO UPDATE SET usuario_id = EXCLUDED.usuario_id;

SELECT setval('artesanos_id_seq', (SELECT MAX(id) FROM artesanos));

-- ============================================
-- PRODUCTOS DE EJEMPLO
-- ============================================
INSERT INTO productos (id, nombre, tipo, descripcion, tiempo_fabricacion_horas, precio_venta, activo) VALUES
(1, 'Anillo de Compromiso Clasico', 'Anillo', 'Anillo solitario con engaste de piedra', 8.00, 15000.00, TRUE),
(2, 'Cadena Eslabones 50cm', 'Cadena', 'Cadena de eslabones medianos', 4.00, 8000.00, TRUE),
(3, 'Aretes Gota', 'Aretes', 'Par de aretes en forma de gota', 3.00, 5000.00, TRUE),
(4, 'Pulsera Eslabones', 'Pulsera', 'Pulsera de eslabones finos', 5.00, 12000.00, TRUE),
(5, 'Dije Corazon', 'Dije', 'Dije en forma de corazon', 2.00, 3500.00, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('productos_id_seq', (SELECT MAX(id) FROM productos));

-- ============================================
-- ORDENES DE PRODUCCION DE EJEMPLO
-- ============================================
INSERT INTO ordenes_produccion (
    id,
    producto_id,
    cantidad,
    fecha_creacion,
    fecha_inicio,
    fecha_fin_estimada,
    fecha_fin_real,
    artesano_id,
    estado_id,
    prioridad_id,
    observaciones
) VALUES
(1, 1, 1, '2026-01-03 09:00:00', '2026-01-03 10:00:00', '2026-01-05 18:00:00', NULL, 1, 2, 3, 'Pedido especial con piedra central.'),
(2, 2, 2, '2026-01-05 11:30:00', '2026-01-05 12:00:00', '2026-01-10 18:00:00', '2026-01-09 17:00:00', 2, 3, 2, 'Cadena para stock.'),
(3, 4, 1, '2026-01-08 08:45:00', '2026-01-08 09:15:00', '2026-01-12 18:00:00', '2026-01-11 16:30:00', 3, 3, 1, 'Entrega completada sin observaciones.')
ON CONFLICT (id) DO NOTHING;

SELECT setval('ordenes_produccion_id_seq', (SELECT MAX(id) FROM ordenes_produccion));

-- ============================================
-- CREACIONES TERMINADAS DE EJEMPLO
-- ============================================
INSERT INTO creaciones_terminadas (
    id,
    orden_id,
    producto_id,
    artesano_id,
    fecha_terminado,
    peso_final_gramos,
    costo_materiales,
    costo_mano_obra,
    tiempo_real_horas,
    calidad_id,
    observaciones,
    vendida,
    fecha_venta,
    precio_venta_real,
    ubicacion_actual
) VALUES
(1, 1, 1, 1, '2026-01-06 16:00:00', 12.500, 4500.00, 1200.00, 9.5, 1, 'Entrega parcial', TRUE, '2026-01-07 11:30:00', 15000.00, 'vendida'),
(2, 2, 2, 2, '2026-01-09 17:15:00', 18.200, 3000.00, 900.00, 6.0, 2, 'Lote completado', TRUE, '2026-01-10 10:00:00', 8000.00, 'vendida'),
(3, 3, 4, 3, '2026-01-12 15:30:00', 8.200, 3200.00, 900.00, 5.5, 2, 'Entrega completada', FALSE, NULL, NULL, 'inventario')
ON CONFLICT (id) DO NOTHING;

SELECT setval('creaciones_terminadas_id_seq', (SELECT MAX(id) FROM creaciones_terminadas));

-- ============================================
-- INVENTARIO INICIAL DE ORO
-- ============================================
INSERT INTO inventario_oro (id, tipo_oro_id, peso_gramos, precio_gramo, proveedor_id, ubicacion, pureza, activo) VALUES
(1, 3, 500.00, 1200.00, 1, 'Caja Fuerte', 75.00, TRUE),
(2, 4, 200.00, 1400.00, 1, 'Caja Fuerte', 91.67, TRUE),
(3, 2, 300.00, 950.00, 1, 'Caja Fuerte', 58.33, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('inventario_oro_id_seq', (SELECT MAX(id) FROM inventario_oro));

-- ============================================
-- MAQUINARIA INICIAL
-- ============================================
INSERT INTO inventario_maquinaria (id, sku, nombre, tipo_maquinaria_id, marca, modelo, fecha_compra, valor_compra, estado_id, ubicacion_id, activo) VALUES
(1, 'SN-MAQ-0001', 'Horno de Fundicion', 1, 'Kerr', 'Electromelt', '2020-03-15', 45000.00, 1, 5, TRUE),
(2, 'SN-MAQ-0002', 'Pulidora Industrial', 3, 'Foredom', 'SR-500', '2021-06-20', 15000.00, 1, 6, TRUE),
(3, 'SN-MAQ-0003', 'Sierra de Joyero', 2, 'Knew Concepts', 'MK4', '2019-11-10', 3500.00, 1, 4, TRUE),
(4, 'SN-MAQ-0004', 'Soldador Laser', 4, 'Orion', 'LZR-150', '2022-02-28', 85000.00, 1, 4, TRUE),
(5, 'SN-MAQ-0005', 'Balanza de Precision', 5, 'Ohaus', 'PA224', '2020-07-05', 12000.00, 1, 4, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('inventario_maquinaria_id_seq', (SELECT MAX(id) FROM inventario_maquinaria));

-- ============================================
-- INSUMOS INICIALES
-- ============================================
INSERT INTO inventario_insumos (id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, activo) VALUES
(1, 'Cera para Moldeo', 'Ceras', 'Cera azul para moldes de joyeria', 50.00, 'kg', 150.00, 10.00, 2, 2, TRUE),
(2, 'Pasta de Pulir Roja', 'Pulido', 'Pasta abrasiva para pulido inicial', 20.00, 'unidad', 85.00, 5.00, 2, 6, TRUE),
(3, 'Pasta de Pulir Verde', 'Pulido', 'Pasta fina para acabado final', 15.00, 'unidad', 95.00, 5.00, 2, 6, TRUE),
(4, 'Soldadura de Oro 18K', 'Soldadura', 'Soldadura para oro de 18 kilates', 100.00, 'gramos', 45.00, 20.00, 2, 4, TRUE),
(5, 'Discos de Corte', 'Herramientas', 'Discos de corte para sierra', 200.00, 'unidad', 12.00, 50.00, 2, 4, TRUE),
(6, 'Guantes de Proteccion', 'Seguridad', 'Guantes resistentes al calor', 10.00, 'pares', 250.00, 2.00, 2, 4, TRUE),
(7, 'Lijas finas', 'Herramientas', 'Lijas para acabado fino', 2.00, 'unidad', 8.00, 10.00, 2, 4, TRUE),
(8, 'Zirconia 3mm', 'Piedras', 'Piedra zirconia para engaste', 1.00, 'unidad', 50.00, 5.00, 2, 2, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('inventario_insumos_id_seq', (SELECT MAX(id) FROM inventario_insumos));

-- ============================================
-- CONSUMO DE MATERIALES EN PRODUCCION
-- ============================================
INSERT INTO consumo_oro (id, orden_produccion_id, inventario_oro_id, cantidad_consumida, usuario_id, fecha_consumo) VALUES
(1, 1, 1, 15.500, 1, '2026-01-03 11:00:00'),
(2, 2, 2, 8.000, 1, '2026-01-06 14:00:00'),
(3, 3, 3, 6.500, 1, '2026-01-09 10:00:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO consumo_insumos (id, orden_produccion_id, inventario_insumos_id, cantidad_consumida, usuario_id, fecha_consumo) VALUES
(1, 1, 1, 2.500, 1, '2026-01-03 12:00:00'),
(2, 2, 2, 1.000, 1, '2026-01-06 14:30:00'),
(3, 3, 5, 4.000, 1, '2026-01-09 10:30:00')
ON CONFLICT (id) DO NOTHING;

SELECT setval('consumo_oro_id_seq', (SELECT MAX(id) FROM consumo_oro));
SELECT setval('consumo_insumos_id_seq', (SELECT MAX(id) FROM consumo_insumos));

-- ============================================
-- COMPRAS (ENTRADAS) DE EJEMPLO
-- ============================================
INSERT INTO movimientos_oro (id, inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(100, 1, 'entrada', 50.000, 'Compra proveedor', 'COMP-2026-01', 1, '2026-01-02 09:00:00'),
(101, 2, 'entrada', 25.000, 'Compra proveedor', 'COMP-2026-02', 1, '2026-01-14 15:30:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO movimientos_insumos (id, inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(200, 1, 'entrada', 15.000, 'Compra insumos', 'COMP-INS-2026-01', 1, '2026-01-04 10:15:00'),
(201, 4, 'entrada', 30.000, 'Compra insumos', 'COMP-INS-2026-02', 1, '2026-01-16 09:45:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO movimientos_maquinaria (id, inventario_maquinaria_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(300, 1, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-01', 1, '2026-01-05 08:30:00')
ON CONFLICT (id) DO NOTHING;

SELECT setval('movimientos_oro_id_seq', (SELECT MAX(id) FROM movimientos_oro));
SELECT setval('movimientos_insumos_id_seq', (SELECT MAX(id) FROM movimientos_insumos));
SELECT setval('movimientos_maquinaria_id_seq', (SELECT MAX(id) FROM movimientos_maquinaria));

COMMIT;

-- Mensaje final
DO $$
BEGIN
    RAISE NOTICE 'Seed completado exitosamente.';
    RAISE NOTICE 'Usuarios creados:';
    RAISE NOTICE '  - admin / Admin123! (ADMIN)';
    RAISE NOTICE '  - artesano / Artesano123! (OPERADOR)';
END;
$$;
