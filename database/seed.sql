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
INSERT INTO estados_maquinaria (id, nombre, descripcion, color) VALUES
(1, 'operativa', 'Maquinaria funcionando correctamente', 'success'),
(2, 'mantenimiento', 'En mantenimiento programado', 'warning'),
(3, 'averiada', 'Fuera de servicio por averia', 'danger'),
(4, 'fuera_servicio', 'Retirada permanentemente del servicio', 'muted')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Estados de orden de produccion
INSERT INTO estados_orden (id, nombre, descripcion, color) VALUES
(1, 'pendiente', 'Orden creada, pendiente de iniciar', 'muted'),
(2, 'en_proceso', 'Orden en proceso de fabricacion', 'info'),
(3, 'terminada', 'Orden completada exitosamente', 'success'),
(4, 'cancelada', 'Orden cancelada', 'danger'),
(5, 'pausada', 'Orden pausada temporalmente', 'warning')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Prioridades
INSERT INTO prioridades (id, nombre, descripcion, color) VALUES
(1, 'baja', 'Prioridad baja', 'muted'),
(2, 'media', 'Prioridad normal', 'info'),
(3, 'alta', 'Prioridad alta', 'warning'),
(4, 'urgente', 'Prioridad urgente', 'danger')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion, color = EXCLUDED.color;

-- Tipos de material
INSERT INTO tipos_material (id, nombre, descripcion) VALUES
(1, 'oro', 'Material de oro'),
(2, 'insumo', 'Insumo general')
ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre, descripcion = EXCLUDED.descripcion;

-- Niveles de calidad
INSERT INTO niveles_calidad (id, nombre, descripcion) VALUES
(1, 'A', 'Calidad premium - Sin defectos'),
(2, 'B', 'Calidad estandar - Defectos menores'),
(3, 'C', 'Calidad basica - Requiere retrabajo')
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
    '$argon2id$v=19$m=65536,t=4,p=1$blk1NmRUOWwyMmN2M3d4NQ$TH4dwRoQlIqvYram9JNwBKyDrxDapHWPaVDAY7HFG7c',
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

-- ARTESANOS DEMO: usuarios OPERADOR (password: Artesano123!)
INSERT INTO seguridad.seg_usuario (username, nombre, clave, email, rolid) VALUES
('ana', 'Ana Sanchez', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'ana@dedumsoft.com', 2),
('miguel', 'Miguel Torres', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'miguel@dedumsoft.com', 2),
('lucia', 'Lucia Ramirez', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'lucia@dedumsoft.com', 2),
('diego', 'Diego Morales', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'diego@dedumsoft.com', 2),
('camila', 'Camila Vargas', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'camila@dedumsoft.com', 2),
('sofia', 'Sofia Perez', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'sofia@dedumsoft.com', 2),
('jorge', 'Jorge Ibarra', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'jorge@dedumsoft.com', 2),
('valentina', 'Valentina Rios', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'valentina@dedumsoft.com', 2),
('hector', 'Hector Salazar', '$argon2id$v=19$m=65536,t=4,p=1$TTFaYTV4MGcwenJrOVF1RA$buFX9PtJrw6NAAFNp7cDFzwdajRkyM2iyCcpfB05NiI', 'hector@dedumsoft.com', 2)
ON CONFLICT (username) DO NOTHING;

-- ============================================
-- CATALOGOS BASE
-- ============================================

-- AREAS
INSERT INTO areas (id, nombre, descripcion, activo) VALUES
(1, 'General', 'Area general de uso multiple', TRUE),
(2, 'Produccion', 'Area dedicada a la produccion y manufactura', TRUE),
(3, 'Almacen', 'Area de almacenamiento de materiales', TRUE),
(4, 'Ventas', 'Area comercial y de atencion al cliente', TRUE),
(5, 'Oficina', 'Area administrativa y de gestion', TRUE),
(6, 'Taller', 'Taller de trabajo especializado', TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;

-- TIPOS DE ORO
INSERT INTO tipos_oro (id, nombre, kilates, pureza_porcentaje, descripcion, activo) VALUES
(1, '10 Kilates', 10.00, 41.67, 'Oro de 10 quilates - 41.67% de pureza', TRUE),
(2, '14 Kilates', 14.00, 58.33, 'Oro de 14 quilates - 58.33% de pureza', TRUE),
(3, '18 Kilates', 18.00, 75.00, 'Oro de 18 quilates - 75% de pureza', TRUE),
(4, '22 Kilates', 22.00, 91.67, 'Oro de 22 quilates - 91.67% de pureza', TRUE),
(5, '24 Kilates', 24.00, 99.99, 'Oro puro de 24 quilates - 99.99% de pureza', TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    kilates = EXCLUDED.kilates,
    pureza_porcentaje = EXCLUDED.pureza_porcentaje,
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;

-- TIPOS DE PROVEEDOR
INSERT INTO tipos_proveedor (id, nombre, descripcion, activo) VALUES
(1, 'Oro', 'Proveedores de oro y metales preciosos', TRUE),
(2, 'Insumos', 'Proveedores de insumos y materiales generales', TRUE),
(3, 'Maquinaria', 'Proveedores de maquinaria, equipos y herramientas', TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;

-- TIPOS DE MAQUINARIA
INSERT INTO tipos_maquinaria (id, nombre, descripcion, activo) VALUES
(1, 'Fundicion', 'Equipos para fundicion de metales', TRUE),
(2, 'Corte', 'Herramientas y equipos de corte', TRUE),
(3, 'Pulido', 'Equipos de pulido y acabado', TRUE),
(4, 'Soldadura', 'Equipos de soldadura', TRUE),
(5, 'Medicion', 'Instrumentos de medicion y precision', TRUE),
(6, 'Grabado', 'Equipos de grabado y marcado', TRUE),
(7, 'Otro', 'Otros equipos y herramientas', TRUE)
ON CONFLICT (id) DO UPDATE SET 
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;

-- ESPECIALIDADES DE ARTESANOS
INSERT INTO cat_especialidad (id, nombre, descripcion, activo) VALUES
(1, 'Joyeria fina', 'Piezas finas con alto detalle', TRUE),
(2, 'Engaste', 'Montaje de piedras en joyeria', TRUE),
(3, 'Grabado', 'Grabado y marcado de piezas', TRUE),
(4, 'Detalle', 'Acabados de precision y detalle', TRUE),
(5, 'Fundicion', 'Fundicion de metales preciosos', TRUE),
(6, 'Moldeo', 'Modelado y moldeo de piezas', TRUE),
(7, 'Pulido', 'Pulido y acabado superficial', TRUE),
(8, 'Modelado', 'Modelado artesanal de piezas', TRUE),
(9, 'Microengaste', 'Microengaste de piedras', TRUE),
(10, 'Cadeneria', 'Fabricacion de cadenas', TRUE),
(11, 'Diseno CAD', 'Diseno asistido por computadora', TRUE),
(12, 'Soldadura', 'Soldadura y ensamble', TRUE)
ON CONFLICT (id) DO UPDATE SET
    nombre = EXCLUDED.nombre,
    descripcion = EXCLUDED.descripcion;

-- Actualizar secuencias de catalogos
SELECT setval('areas_id_seq', (SELECT MAX(id) FROM areas));
SELECT setval('tipos_oro_id_seq', (SELECT MAX(id) FROM tipos_oro));
SELECT setval('tipos_proveedor_id_seq', (SELECT MAX(id) FROM tipos_proveedor));
SELECT setval('tipos_maquinaria_id_seq', (SELECT MAX(id) FROM tipos_maquinaria));
SELECT setval('cat_especialidad_id_seq', (SELECT MAX(id) FROM cat_especialidad));

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
INSERT INTO artesanos (id, usuario_id, nombre, apellido, telefono, email, fecha_ingreso, activo) VALUES
(1, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'artesano'), 'Roberto', 'Martinez', '555-1111', 'roberto@dedumsoft.com', '2020-01-15', TRUE),
(2, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'ana'), 'Ana', 'Sanchez', '555-2222', 'ana@dedumsoft.com', '2021-03-20', TRUE),
(3, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'miguel'), 'Miguel', 'Torres', '555-3333', 'miguel@dedumsoft.com', '2019-06-10', TRUE)
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

-- ============================================
-- DATOS DEMO EXTRA (SIN TOCAR SEGURIDAD)
-- ============================================
INSERT INTO ubicaciones (id, nombre, descripcion, area_id, capacidad, activo) VALUES
(9, 'Bodega C', 'Bodega para insumos de alta rotacion', 3, 220, TRUE),
(10, 'Laboratorio Calidad', 'Inspeccion y control de calidad', 2, 15, TRUE),
(11, 'Showroom Norte', 'Exhibicion de temporada', 4, 120, TRUE),
(12, 'Almacen Insumos 2', 'Almacen secundario de insumos', 3, 180, TRUE),
(13, 'Sala Diseno', 'Area de diseno y bocetos', 5, 8, TRUE),
(14, 'Area Empaque', 'Zona de empaque y envio', 4, 60, TRUE),
(15, 'Cuarto Herramientas', 'Herramientas y refacciones', 2, 40, TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO proveedores (id, nombre, tipo_proveedor_id, tipo, contacto, telefono, email, direccion, rfc, activo) VALUES
(4, 'Oro Andino SA', 1, 'oro', 'Luis Ortega', '555-2211', 'ventas@oroandino.com', 'Av. Mineral 12', 'OAS22112233', TRUE),
(5, 'Gemas Caribe', 2, 'insumos', 'Elena Ruiz', '555-3322', 'ventas@gemscaribe.com', 'Calle Mar 55', 'GCA33224455', TRUE),
(6, 'Quimicos del Taller', 2, 'insumos', 'Mario Vega', '555-4433', 'contacto@quimicostaller.com', 'Parque Industrial 9', 'QDT44335566', TRUE),
(7, 'Equipos Taller Pro', 3, 'maquinaria', 'Sara Luna', '555-5544', 'ventas@tallerpro.com', 'Av. Maquinas 101', 'ETP55446677', TRUE),
(8, 'Suministros Seguridad', 2, 'insumos', 'Patricia Solis', '555-6655', 'ventas@seguridadinsumos.com', 'Calle Prevencion 77', 'SSI66557788', TRUE),
(9, 'Metalurgia Central', 1, 'oro', 'Jorge Pineda', '555-7766', 'ventas@metalurgiacentral.com', 'Blvd. Fundicion 320', 'MCE77668899', TRUE),
(10, 'Herramientas Orion', 3, 'maquinaria', 'Rafael Diaz', '555-8877', 'contacto@herramientasorion.com', 'Carretera Norte 45', 'HOR88779900', TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO artesanos (id, usuario_id, nombre, apellido, telefono, email, fecha_ingreso, activo) VALUES
(4, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'lucia'), 'Lucia', 'Ramirez', '555-4444', 'lucia@dedumsoft.com', '2021-05-12', TRUE),
(5, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'diego'), 'Diego', 'Morales', '555-5555', 'diego@dedumsoft.com', '2022-02-10', TRUE),
(6, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'camila'), 'Camila', 'Vargas', '555-6666', 'camila@dedumsoft.com', '2021-08-18', TRUE),
(7, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'sofia'), 'Sofia', 'Perez', '555-7777', 'sofia@dedumsoft.com', '2020-09-01', TRUE),
(8, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'jorge'), 'Jorge', 'Ibarra', '555-8888', 'jorge@dedumsoft.com', '2018-11-03', TRUE),
(9, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'valentina'), 'Valentina', 'Rios', '555-9990', 'valentina@dedumsoft.com', '2023-01-15', TRUE),
(10, (SELECT id_usuario FROM seguridad.seg_usuario WHERE username = 'hector'), 'Hector', 'Salazar', '555-0001', 'hector@dedumsoft.com', '2019-04-22', TRUE)
ON CONFLICT (id) DO UPDATE SET usuario_id = EXCLUDED.usuario_id;

INSERT INTO artesano_especialidad (id, artesano_id, especialidad_id) VALUES
(1, 1, (SELECT id FROM cat_especialidad WHERE nombre = 'Joyeria fina')),
(2, 1, (SELECT id FROM cat_especialidad WHERE nombre = 'Engaste')),
(3, 2, (SELECT id FROM cat_especialidad WHERE nombre = 'Grabado')),
(4, 2, (SELECT id FROM cat_especialidad WHERE nombre = 'Detalle')),
(5, 3, (SELECT id FROM cat_especialidad WHERE nombre = 'Fundicion')),
(6, 3, (SELECT id FROM cat_especialidad WHERE nombre = 'Moldeo')),
(7, 4, (SELECT id FROM cat_especialidad WHERE nombre = 'Engaste')),
(8, 5, (SELECT id FROM cat_especialidad WHERE nombre = 'Pulido')),
(9, 6, (SELECT id FROM cat_especialidad WHERE nombre = 'Modelado')),
(10, 7, (SELECT id FROM cat_especialidad WHERE nombre = 'Microengaste')),
(11, 8, (SELECT id FROM cat_especialidad WHERE nombre = 'Cadeneria')),
(12, 9, (SELECT id FROM cat_especialidad WHERE nombre = 'Diseno CAD')),
(13, 10, (SELECT id FROM cat_especialidad WHERE nombre = 'Fundicion')),
(14, 10, (SELECT id FROM cat_especialidad WHERE nombre = 'Soldadura'))
ON CONFLICT (id) DO NOTHING;

INSERT INTO productos (id, nombre, tipo, descripcion, tiempo_fabricacion_horas, precio_venta, activo) VALUES
(6, 'Anillo Halo Diamantes', 'Anillo', 'Anillo con halo de piedras', 10.00, 22000.00, TRUE),
(7, 'Collar Perlas 45cm', 'Collar', 'Collar de perlas clasico', 6.00, 9000.00, TRUE),
(8, 'Pulsera Tennis', 'Pulsera', 'Pulsera tipo tennis con zirconias', 7.00, 18000.00, TRUE),
(9, 'Aretes Aro Clasicos', 'Aretes', 'Aros clasicos en oro', 2.50, 4200.00, TRUE),
(10, 'Dije Estrella', 'Dije', 'Dije con forma de estrella', 2.00, 2800.00, TRUE),
(11, 'Anillo Sello', 'Anillo', 'Anillo tipo sello con grabado', 5.00, 6000.00, TRUE),
(12, 'Brazalete Rigido', 'Pulsera', 'Brazalete rigido pulido', 6.00, 14000.00, TRUE),
(13, 'Cadena Fina 60cm', 'Cadena', 'Cadena fina para dijes', 4.50, 7000.00, TRUE),
(14, 'Aretes Perla', 'Aretes', 'Aretes con perla natural', 3.50, 5200.00, TRUE),
(15, 'Dije Inicial', 'Dije', 'Dije con inicial personalizada', 1.50, 2000.00, TRUE),
(16, 'Anillo Trilogia', 'Anillo', 'Anillo con tres piedras', 9.00, 19500.00, TRUE),
(17, 'Collar Corazon', 'Collar', 'Collar con dije de corazon', 5.00, 8800.00, TRUE),
(18, 'Pulsera Charm', 'Pulsera', 'Pulsera para charms', 5.50, 11000.00, TRUE),
(19, 'Aretes Stud', 'Aretes', 'Aretes tipo stud', 2.00, 3500.00, TRUE),
(20, 'Dije Cruz', 'Dije', 'Dije de cruz clasica', 2.20, 3100.00, TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO recetas_produccion (id, producto_id, tipo_material_id, material_id, cantidad_requerida, es_opcional, notas) VALUES
(1, 1, 1, 1, 12.500, FALSE, 'Oro 18K'),
(2, 1, 2, 8, 1.000, TRUE, 'Zirconia 3mm'),
(3, 2, 1, 2, 18.000, FALSE, 'Oro 22K'),
(4, 2, 2, 1, 0.800, TRUE, 'Cera para molde'),
(5, 3, 1, 3, 6.000, FALSE, 'Oro 14K'),
(6, 4, 1, 1, 9.000, FALSE, 'Oro 18K'),
(7, 5, 1, 3, 4.000, FALSE, 'Oro 14K'),
(8, 6, 1, 2, 11.000, FALSE, 'Oro 22K'),
(9, 6, 2, 8, 2.000, TRUE, 'Zirconia 3mm'),
(10, 7, 1, 2, 14.000, FALSE, 'Oro 22K'),
(11, 8, 1, 1, 10.000, FALSE, 'Oro 18K'),
(12, 9, 1, 3, 3.500, FALSE, 'Oro 14K'),
(13, 10, 1, 1, 2.800, FALSE, 'Oro 18K'),
(14, 11, 1, 1, 7.500, FALSE, 'Oro 18K'),
(15, 12, 1, 2, 12.000, FALSE, 'Oro 22K'),
(16, 13, 1, 3, 8.000, FALSE, 'Oro 14K'),
(17, 14, 1, 2, 5.000, FALSE, 'Oro 22K'),
(18, 15, 1, 3, 2.000, FALSE, 'Oro 14K'),
(19, 16, 1, 1, 10.500, FALSE, 'Oro 18K'),
(20, 17, 1, 2, 7.000, FALSE, 'Oro 22K')
ON CONFLICT (id) DO NOTHING;

INSERT INTO inventario_oro (id, tipo_oro_id, peso_gramos, precio_gramo, proveedor_id, ubicacion, pureza, activo) VALUES
(4, 1, 400.000, 750.00, 4, 'Caja Fuerte', 41.67, TRUE),
(5, 5, 120.000, 1850.00, 9, 'Caja Fuerte', 99.99, TRUE),
(6, 2, 350.000, 980.00, 1, 'Bodega A', 58.33, TRUE),
(7, 3, 280.000, 1250.00, 4, 'Bodega A', 75.00, TRUE),
(8, 4, 160.000, 1450.00, 9, 'Caja Fuerte', 91.67, TRUE),
(9, 1, 260.000, 700.00, 1, 'Bodega B', 41.67, TRUE),
(10, 5, 90.000, 1900.00, 4, 'Caja Fuerte', 99.99, TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO inventario_maquinaria (id, sku, nombre, tipo_maquinaria_id, marca, modelo, fecha_compra, valor_compra, estado_id, ubicacion_id, activo) VALUES
(6, 'SN-MAQ-0006', 'Pulidora Compacta', 3, 'Foredom', 'SR-400', '2023-01-10', 9000.00, 1, 6, TRUE),
(7, 'SN-MAQ-0007', 'Laminadora Manual', 7, 'Durston', 'DM130', '2022-08-12', 22000.00, 1, 4, TRUE),
(8, 'SN-MAQ-0008', 'Micro Taladro', 2, 'Proxxon', 'FBS240', '2021-04-06', 6500.00, 1, 15, TRUE),
(9, 'SN-MAQ-0009', 'Equipo Ultrasonido', 7, 'Elma', 'Elmasonic', '2020-09-18', 18000.00, 2, 10, TRUE),
(10, 'SN-MAQ-0010', 'Pulidora Doble', 3, 'Baldor', 'DS-200', '2019-05-22', 11000.00, 1, 6, TRUE),
(11, 'SN-MAQ-0011', 'Grabadora Laser', 6, 'Trotec', 'Speedy100', '2021-11-30', 98000.00, 1, 4, TRUE),
(12, 'SN-MAQ-0012', 'Medidor de Pureza', 5, 'GXL', 'AuCheck', '2022-03-14', 14500.00, 1, 10, TRUE)
ON CONFLICT (id) DO NOTHING;

INSERT INTO inventario_insumos (id, nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, activo) VALUES
(9, 'Piedra Zirconia 2mm', 'Piedras', 'Zirconia blanca 2mm', 500.00, 'unidad', 8.00, 100.00, 5, 2, TRUE),
(10, 'Piedra Zirconia 4mm', 'Piedras', 'Zirconia blanca 4mm', 300.00, 'unidad', 12.00, 60.00, 5, 2, TRUE),
(11, 'Perlas Sinteticas', 'Piedras', 'Perlas para collar', 200.00, 'unidad', 15.00, 50.00, 5, 2, TRUE),
(12, 'Acido Borico', 'Quimicos', 'Flux para soldadura', 5.00, 'kg', 180.00, 1.00, 6, 5, TRUE),
(13, 'Pasta Pulir Azul', 'Pulido', 'Pasta de brillo final', 12.00, 'unidad', 90.00, 4.00, 2, 6, TRUE),
(14, 'Discos Fieltro', 'Herramientas', 'Discos para pulir', 80.00, 'unidad', 20.00, 20.00, 2, 15, TRUE),
(15, 'Brocas Finas', 'Herramientas', 'Brocas para micro taladro', 150.00, 'unidad', 6.00, 30.00, 10, 15, TRUE),
(16, 'Caja de Empaque', 'Empaque', 'Caja para anillos', 300.00, 'unidad', 8.00, 80.00, 8, 14, TRUE),
(17, 'Bolsa Antirrayas', 'Empaque', 'Bolsa para joyeria', 400.00, 'unidad', 3.50, 100.00, 8, 14, TRUE),
(18, 'Guantes Nitrilo', 'Seguridad', 'Guantes desechables', 50.00, 'caja', 120.00, 10.00, 8, 4, TRUE),
(19, 'Mascara Protectora', 'Seguridad', 'Mascara para soldadura', 20.00, 'unidad', 150.00, 5.00, 8, 4, TRUE),
(20, 'Lijas Grano 400', 'Herramientas', 'Lija fina grano 400', 120.00, 'unidad', 5.00, 30.00, 2, 4, TRUE),
(21, 'Lijas Grano 800', 'Herramientas', 'Lija ultra fina grano 800', 100.00, 'unidad', 6.00, 25.00, 2, 4, TRUE),
(22, 'Cera Verde', 'Ceras', 'Cera verde para modelos', 30.00, 'kg', 160.00, 8.00, 2, 2, TRUE),
(23, 'Soldadura 14K', 'Soldadura', 'Soldadura para oro 14K', 120.00, 'gramos', 40.00, 25.00, 6, 4, TRUE),
(24, 'Polvo Abrasivo', 'Pulido', 'Polvo para arenado', 25.00, 'kg', 200.00, 5.00, 6, 6, TRUE),
(25, 'Alcohol Isopropilico', 'Quimicos', 'Limpieza de piezas', 20.00, 'litros', 90.00, 4.00, 6, 10, TRUE)
ON CONFLICT (id) DO NOTHING;

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
(4, 6, 1, '2026-01-02 09:10:00', '2026-01-02 10:00:00', '2026-01-08 18:00:00', '2026-01-07 17:30:00', 4, 3, 3, 'Anillo halo para stock premium.'),
(5, 7, 1, '2026-01-04 11:00:00', '2026-01-04 12:00:00', '2026-01-09 18:00:00', '2026-01-09 16:20:00', 5, 3, 2, 'Collar clasico de perlas.'),
(6, 8, 2, '2026-01-05 08:30:00', '2026-01-05 09:00:00', '2026-01-12 18:00:00', NULL, 6, 2, 3, 'Pulsera tennis para vitrina.'),
(7, 9, 3, '2026-01-06 09:00:00', NULL, '2026-01-08 18:00:00', NULL, 7, 1, 2, 'Aros clasicos.'),
(8, 10, 2, '2026-01-07 10:15:00', '2026-01-07 11:00:00', '2026-01-10 18:00:00', '2026-01-10 14:40:00', 4, 3, 2, 'Dijes estrella lote.'),
(9, 11, 1, '2026-01-08 08:20:00', '2026-01-08 09:10:00', '2026-01-12 18:00:00', '2026-01-12 16:10:00', 5, 3, 2, 'Anillo sello grabado.'),
(10, 12, 1, '2026-01-10 09:30:00', '2026-01-10 10:00:00', '2026-01-15 18:00:00', NULL, 6, 2, 3, 'Brazalete rigido.'),
(11, 13, 2, '2026-01-11 11:40:00', '2026-01-11 12:10:00', '2026-01-14 18:00:00', '2026-01-14 17:00:00', 7, 3, 2, 'Cadena fina 60cm.'),
(12, 14, 2, '2026-01-12 08:00:00', '2026-01-12 08:40:00', '2026-01-13 18:00:00', '2026-01-13 16:30:00', 8, 3, 1, 'Aretes con perla.'),
(13, 15, 4, '2026-01-13 09:50:00', '2026-01-13 10:30:00', '2026-01-16 18:00:00', '2026-01-15 15:20:00', 9, 3, 1, 'Dijes inicial personalizados.'),
(14, 16, 1, '2026-01-14 10:10:00', '2026-01-15 09:00:00', '2026-01-22 18:00:00', '2026-01-21 17:10:00', 10, 3, 4, 'Anillo trilogia pedido urgente.'),
(15, 17, 1, '2026-01-16 08:45:00', '2026-01-16 09:30:00', '2026-01-20 18:00:00', NULL, 8, 5, 2, 'Collar corazon pausado.'),
(16, 18, 2, '2026-01-17 11:20:00', '2026-01-18 09:00:00', '2026-01-25 18:00:00', NULL, 9, 2, 2, 'Pulsera charm en proceso.'),
(17, 19, 5, '2026-01-19 08:30:00', '2026-01-19 09:00:00', '2026-01-20 18:00:00', '2026-01-20 14:00:00', 10, 3, 1, 'Aretes stud para exhibicion.'),
(18, 20, 2, '2026-01-20 10:00:00', '2026-01-20 10:30:00', '2026-01-22 18:00:00', NULL, 4, 1, 2, 'Dije cruz pendiente.'),
(19, 1, 1, '2026-01-22 09:10:00', '2026-01-22 10:00:00', '2026-01-26 18:00:00', '2026-01-25 16:40:00', 5, 3, 3, 'Anillo clasico adicional.'),
(20, 2, 2, '2026-01-23 11:30:00', '2026-01-23 12:10:00', '2026-01-26 18:00:00', NULL, 6, 2, 2, 'Cadena para stock.'),
(21, 3, 3, '2026-01-24 08:50:00', '2026-01-24 09:20:00', '2026-01-26 18:00:00', '2026-01-26 15:50:00', 7, 3, 1, 'Aretes gota lote.'),
(22, 4, 1, '2026-01-25 09:40:00', '2026-01-25 10:00:00', '2026-01-29 18:00:00', '2026-01-29 17:10:00', 8, 3, 2, 'Pulsera eslabones.'),
(23, 5, 1, '2026-01-26 10:30:00', '2026-01-27 09:00:00', '2026-01-30 18:00:00', NULL, 9, 4, 2, 'Dije corazon cancelado.'),
(24, 6, 1, '2026-02-01 08:40:00', '2026-02-01 09:00:00', '2026-02-07 18:00:00', '2026-02-06 16:30:00', 10, 3, 3, 'Anillo halo febrero.'),
(25, 7, 1, '2026-02-02 11:00:00', '2026-02-02 11:30:00', '2026-02-06 18:00:00', NULL, 4, 2, 2, 'Collar perlas en proceso.')
ON CONFLICT (id) DO NOTHING;

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
(4, 4, 6, 4, '2026-01-07 17:00:00', 13.200, 5000.00, 1400.00, 10.2, 1, 'Entrega premium', TRUE, '2026-01-08 12:30:00', 22500.00, 'vendida'),
(5, 5, 7, 5, '2026-01-09 16:10:00', 15.000, 3200.00, 900.00, 6.4, 2, 'Collar clasico', TRUE, '2026-01-10 10:10:00', 9200.00, 'vendida'),
(6, 8, 10, 4, '2026-01-10 14:20:00', 4.200, 1200.00, 500.00, 2.3, 2, 'Dije estrella', TRUE, '2026-01-11 09:30:00', 2800.00, 'vendida'),
(7, 9, 11, 5, '2026-01-12 16:00:00', 7.800, 2600.00, 800.00, 5.2, 2, 'Grabado listo', TRUE, '2026-01-13 11:20:00', 6000.00, 'vendida'),
(8, 11, 13, 7, '2026-01-14 16:50:00', 9.500, 2800.00, 700.00, 4.8, 2, 'Cadena fina', TRUE, '2026-01-15 13:10:00', 7200.00, 'vendida'),
(9, 12, 14, 8, '2026-01-13 16:00:00', 6.100, 2100.00, 600.00, 3.7, 1, 'Perlas seleccionadas', FALSE, NULL, NULL, 'inventario'),
(10, 13, 15, 9, '2026-01-15 15:00:00', 5.200, 1700.00, 650.00, 3.5, 2, 'Iniciales personalizadas', TRUE, '2026-01-16 12:00:00', 2100.00, 'vendida'),
(11, 14, 16, 10, '2026-01-21 16:40:00', 12.800, 5400.00, 1600.00, 9.0, 1, 'Trilogia urgente', TRUE, '2026-01-22 09:30:00', 19500.00, 'vendida'),
(12, 17, 19, 10, '2026-01-20 14:00:00', 3.600, 900.00, 420.00, 2.0, 2, 'Aretes stud', TRUE, '2026-01-20 18:00:00', 3500.00, 'vendida'),
(13, 19, 1, 5, '2026-01-25 16:20:00', 12.900, 4700.00, 1200.00, 8.8, 2, 'Anillo clasico extra', TRUE, '2026-01-26 10:15:00', 15000.00, 'vendida'),
(14, 21, 3, 7, '2026-01-26 15:30:00', 6.800, 2000.00, 650.00, 3.1, 2, 'Lote aretes gota', TRUE, '2026-01-27 11:00:00', 5200.00, 'vendida'),
(15, 22, 4, 8, '2026-01-29 17:00:00', 8.900, 2900.00, 780.00, 5.0, 2, 'Pulsera eslabones', FALSE, NULL, NULL, 'inventario'),
(16, 24, 6, 10, '2026-02-06 16:10:00', 13.400, 5200.00, 1500.00, 9.5, 1, 'Anillo halo febrero', TRUE, '2026-02-07 10:40:00', 23000.00, 'vendida')
ON CONFLICT (id) DO NOTHING;

INSERT INTO consumo_oro (id, orden_produccion_id, inventario_oro_id, cantidad_consumida, usuario_id, fecha_consumo) VALUES
(4, 4, 7, 13.000, 1, '2026-01-02 11:00:00'),
(5, 5, 6, 14.500, 1, '2026-01-04 13:20:00'),
(6, 6, 6, 18.000, 1, '2026-01-05 10:30:00'),
(7, 8, 7, 5.200, 1, '2026-01-07 11:30:00'),
(8, 9, 4, 7.600, 1, '2026-01-08 12:10:00'),
(9, 10, 8, 10.000, 1, '2026-01-10 12:45:00'),
(10, 11, 7, 9.200, 1, '2026-01-11 14:00:00'),
(11, 12, 6, 6.000, 1, '2026-01-12 09:50:00'),
(12, 13, 9, 5.000, 1, '2026-01-13 11:10:00'),
(13, 14, 5, 12.000, 1, '2026-01-15 10:40:00'),
(14, 17, 4, 3.800, 1, '2026-01-19 10:20:00'),
(15, 19, 1, 12.500, 1, '2026-01-22 11:00:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO consumo_insumos (id, orden_produccion_id, inventario_insumos_id, cantidad_consumida, usuario_id, fecha_consumo) VALUES
(4, 4, 9, 6.000, 1, '2026-01-02 12:10:00'),
(5, 5, 11, 4.000, 1, '2026-01-04 14:00:00'),
(6, 6, 13, 3.000, 1, '2026-01-05 11:20:00'),
(7, 8, 16, 10.000, 1, '2026-01-07 12:15:00'),
(8, 9, 14, 2.000, 1, '2026-01-08 13:10:00'),
(9, 10, 20, 5.000, 1, '2026-01-10 13:00:00'),
(10, 11, 22, 1.500, 1, '2026-01-11 15:00:00'),
(11, 12, 10, 4.000, 1, '2026-01-12 10:20:00'),
(12, 13, 17, 12.000, 1, '2026-01-13 12:10:00'),
(13, 14, 18, 1.000, 1, '2026-01-15 11:10:00'),
(14, 17, 21, 2.500, 1, '2026-01-19 11:00:00'),
(15, 19, 1, 1.200, 1, '2026-01-22 12:00:00'),
(16, 21, 23, 2.000, 1, '2026-01-24 12:15:00'),
(17, 22, 24, 1.500, 1, '2026-01-25 12:20:00'),
(18, 24, 9, 5.000, 1, '2026-02-01 10:10:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO movimientos_oro (id, inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(102, 4, 'entrada', 60.000, 'Compra proveedor', 'COMP-2026-03', 1, '2026-01-20 09:00:00'),
(103, 6, 'entrada', 40.000, 'Compra proveedor', 'COMP-2026-04', 1, '2026-01-28 15:30:00'),
(104, 7, 'entrada', 55.000, 'Compra proveedor', 'COMP-2026-05', 1, '2026-02-05 11:15:00'),
(105, 8, 'entrada', 25.000, 'Compra proveedor', 'COMP-2026-06', 1, '2026-02-12 10:40:00'),
(106, 9, 'entrada', 70.000, 'Compra proveedor', 'COMP-2026-07', 1, '2026-02-18 16:00:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO movimientos_insumos (id, inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(202, 9, 'entrada', 200.000, 'Compra insumos', 'COMP-INS-2026-03', 1, '2026-01-20 10:10:00'),
(203, 10, 'entrada', 150.000, 'Compra insumos', 'COMP-INS-2026-04', 1, '2026-01-22 12:10:00'),
(204, 12, 'entrada', 5.000, 'Compra insumos', 'COMP-INS-2026-05', 1, '2026-01-25 09:45:00'),
(205, 13, 'entrada', 8.000, 'Compra insumos', 'COMP-INS-2026-06', 1, '2026-01-28 14:30:00'),
(206, 16, 'entrada', 120.000, 'Compra insumos', 'COMP-INS-2026-07', 1, '2026-02-03 11:05:00'),
(207, 17, 'entrada', 180.000, 'Compra insumos', 'COMP-INS-2026-08', 1, '2026-02-06 16:00:00'),
(208, 20, 'entrada', 90.000, 'Compra insumos', 'COMP-INS-2026-09', 1, '2026-02-10 10:20:00'),
(209, 21, 'entrada', 80.000, 'Compra insumos', 'COMP-INS-2026-10', 1, '2026-02-12 12:50:00'),
(210, 23, 'entrada', 60.000, 'Compra insumos', 'COMP-INS-2026-11', 1, '2026-02-15 09:10:00'),
(211, 24, 'entrada', 10.000, 'Compra insumos', 'COMP-INS-2026-12', 1, '2026-02-20 15:35:00'),
(212, 25, 'entrada', 15.000, 'Compra insumos', 'COMP-INS-2026-13', 1, '2026-02-25 13:25:00')
ON CONFLICT (id) DO NOTHING;

INSERT INTO movimientos_maquinaria (id, inventario_maquinaria_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha) VALUES
(301, 6, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-02', 1, '2026-01-18 08:30:00'),
(302, 7, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-03', 1, '2026-01-25 09:10:00'),
(303, 8, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-04', 1, '2026-02-04 10:40:00'),
(304, 11, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-05', 1, '2026-02-14 11:25:00'),
(305, 12, 'entrada', 1, 'Compra maquinaria', 'COMP-MAQ-2026-06', 1, '2026-02-22 12:05:00')
ON CONFLICT (id) DO NOTHING;

-- Re-sincronizar secuencias despues de los datos extra
SELECT setval('ubicaciones_id_seq', (SELECT MAX(id) FROM ubicaciones));
SELECT setval('proveedores_id_seq', (SELECT MAX(id) FROM proveedores));
SELECT setval('artesanos_id_seq', (SELECT MAX(id) FROM artesanos));
SELECT setval('artesano_especialidad_id_seq', (SELECT MAX(id) FROM artesano_especialidad));
SELECT setval('productos_id_seq', (SELECT MAX(id) FROM productos));
SELECT setval('recetas_produccion_id_seq', (SELECT MAX(id) FROM recetas_produccion));
SELECT setval('ordenes_produccion_id_seq', (SELECT MAX(id) FROM ordenes_produccion));
SELECT setval('creaciones_terminadas_id_seq', (SELECT MAX(id) FROM creaciones_terminadas));
SELECT setval('inventario_oro_id_seq', (SELECT MAX(id) FROM inventario_oro));
SELECT setval('inventario_maquinaria_id_seq', (SELECT MAX(id) FROM inventario_maquinaria));
SELECT setval('inventario_insumos_id_seq', (SELECT MAX(id) FROM inventario_insumos));
SELECT setval('consumo_oro_id_seq', (SELECT MAX(id) FROM consumo_oro));
SELECT setval('consumo_insumos_id_seq', (SELECT MAX(id) FROM consumo_insumos));
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
