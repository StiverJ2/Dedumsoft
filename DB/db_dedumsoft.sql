
-- ============================================
-- SISTEMA DE GESTIÃ“N PARA JOYERÃA
-- Base de Datos: PostgreSQL
-- ============================================

-- Crear esquema (opcional)
CREATE SCHEMA IF NOT EXISTS joyeria;
SET search_path TO joyeria, public;

-- ============================================
-- ESQUEMA DE SEGURIDAD (MISMA BD, ESQUEMA SEPARADO)
-- ============================================

CREATE SCHEMA IF NOT EXISTS seguridad;

-- Roles
CREATE TABLE IF NOT EXISTS seguridad.seg_rol
(
    id_rol      serial PRIMARY KEY,
    nombre      text NOT NULL,
    comentario  text NOT NULL,
    deleted_at  timestamp
);

-- Usuarios (auth local)
CREATE TABLE IF NOT EXISTS seguridad.seg_usuario
(
    id_usuario  serial PRIMARY KEY,
    username    text NOT NULL UNIQUE,
    nombre      text NOT NULL,
    clave       text NOT NULL,
    rolid       integer NOT NULL,

    created_at  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at  timestamp,

    CONSTRAINT seg_usuario_rol_fk FOREIGN KEY (rolid)
        REFERENCES seguridad.seg_rol (id_rol)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
);

-- Login / sesiones / tokens
CREATE TABLE IF NOT EXISTS seguridad.seg_login
(
    id_login           serial PRIMARY KEY,
    token              text NOT NULL,
    refresh_token      text,
    refresh_expira     timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_token       boolean NOT NULL DEFAULT TRUE,
    timestamp_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    timestamp_expira   timestamp NOT NULL,
    ip_origen          INET,
    user_agent         text,
    usuarioid          integer NOT NULL,

    CONSTRAINT seg_login_usuario_fk FOREIGN KEY (usuarioid)
        REFERENCES seguridad.seg_usuario (id_usuario)
        ON UPDATE NO ACTION
        ON DELETE CASCADE
);

-- Menus / modulos
CREATE TABLE IF NOT EXISTS seguridad.seg_menu
(
    id_menu    serial PRIMARY KEY,
    nombre     text NOT NULL,
    comentario text NOT NULL,
    ruta       text NOT NULL,
    deleted_at timestamp
);

-- Permisos por rol y menu
CREATE TABLE IF NOT EXISTS seguridad.seg_menurol
(
    id_menu_rol serial PRIMARY KEY,
    abrir       boolean NOT NULL,
    guardar     boolean NOT NULL,
    editar      boolean NOT NULL,
    eliminar    boolean NOT NULL,
    rolid       integer NOT NULL,
    menuid      integer NOT NULL,

    CONSTRAINT seg_menurol_rol_menu_unique UNIQUE (rolid, menuid),

    CONSTRAINT seg_menurol_rol_fk FOREIGN KEY (rolid)
        REFERENCES seguridad.seg_rol (id_rol)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,

    CONSTRAINT seg_menurol_menu_fk FOREIGN KEY (menuid)
        REFERENCES seguridad.seg_menu (id_menu)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
);

-- Indices seguridad
CREATE INDEX IF NOT EXISTS idx_seg_login_token 
    ON seguridad.seg_login(token);

CREATE INDEX IF NOT EXISTS idx_seg_login_refresh_token 
    ON seguridad.seg_login(refresh_token);

CREATE INDEX IF NOT EXISTS idx_seg_login_usuario 
    ON seguridad.seg_login(usuarioid);

CREATE INDEX IF NOT EXISTS idx_seg_login_activo_expira 
    ON seguridad.seg_login(estado_token, timestamp_expira) 
    WHERE estado_token = TRUE;

CREATE INDEX IF NOT EXISTS idx_seg_usuario_username 
    ON seguridad.seg_usuario(username);

CREATE INDEX IF NOT EXISTS idx_seg_usuario_activo 
    ON seguridad.seg_usuario(id_usuario) 
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_seg_rol_activo 
    ON seguridad.seg_rol (id_rol) 
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_seg_menu_activo 
    ON seguridad.seg_menu (id_menu) 
    WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_seg_menurol_rol 
    ON seguridad.seg_menurol(rolid);

-- ============================================
-- SEED DE SEGURIDAD (DEDUMSOFT)
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

-- ADMIN
INSERT INTO seguridad.seg_menurol (abrir, guardar, editar, eliminar, rolid, menuid) VALUES
(TRUE, TRUE, TRUE, TRUE, 1, 1),
(TRUE, TRUE, TRUE, TRUE, 1, 2),
(TRUE, TRUE, TRUE, TRUE, 1, 3),
(TRUE, TRUE, TRUE, TRUE, 1, 4),
(TRUE, TRUE, TRUE, TRUE, 1, 5),
(TRUE, TRUE, TRUE, TRUE, 1, 6),
(TRUE, TRUE, TRUE, TRUE, 1, 7)
ON CONFLICT (rolid, menuid) DO UPDATE
SET abrir = EXCLUDED.abrir, guardar = EXCLUDED.guardar, editar = EXCLUDED.editar, eliminar = EXCLUDED.eliminar;

-- OPERADOR
INSERT INTO seguridad.seg_menurol (abrir, guardar, editar, eliminar, rolid, menuid) VALUES
(TRUE, FALSE, FALSE, FALSE, 2, 1),
(TRUE, TRUE, TRUE, FALSE, 2, 2),
(TRUE, TRUE, TRUE, FALSE, 2, 3),
(TRUE, FALSE, FALSE, FALSE, 2, 4),
(FALSE, FALSE, FALSE, FALSE, 2, 5),
(TRUE, TRUE, TRUE, FALSE, 2, 6),
(FALSE, FALSE, FALSE, FALSE, 2, 7)
ON CONFLICT (rolid, menuid) DO UPDATE
SET abrir = EXCLUDED.abrir, guardar = EXCLUDED.guardar, editar = EXCLUDED.editar, eliminar = EXCLUDED.eliminar;

-- LECTURA
INSERT INTO seguridad.seg_menurol (abrir, guardar, editar, eliminar, rolid, menuid) VALUES
(TRUE, FALSE, FALSE, FALSE, 3, 1),
(TRUE, FALSE, FALSE, FALSE, 3, 2),
(TRUE, FALSE, FALSE, FALSE, 3, 3),
(TRUE, FALSE, FALSE, FALSE, 3, 4),
(FALSE, FALSE, FALSE, FALSE, 3, 5),
(TRUE, FALSE, FALSE, FALSE, 3, 6),
(FALSE, FALSE, FALSE, FALSE, 3, 7)
ON CONFLICT (rolid, menuid) DO UPDATE
SET abrir = EXCLUDED.abrir, guardar = EXCLUDED.guardar, editar = EXCLUDED.editar, eliminar = EXCLUDED.eliminar;

-- Usuario de prueba
INSERT INTO seguridad.seg_usuario (username, nombre, clave, rolid)
VALUES (
    'admin',
    'Administrador',
    '$argon2id$v=19$m=65536,t=4,p=1$MGQvRTBneVJhQlJFTGhmeg$V0Z6bsVV2cABUX7qo/joYRYmp0ovvxMNW0p3zFo32Aw',
    1
)
--Admin-2026
ON CONFLICT (username) DO NOTHING;

-- ============================================
-- TABLAS DE USUARIOS (JOYERIA)
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_user VARCHAR(255) NOT NULL,
    CHECK (
        LENGTH(password_user) >= 8 AND
        password_user ~ '[A-Z]' AND
        password_user ~ '[a-z]' AND
        password_user ~ '[0-9]' AND
        password_user ~ '[^A-Za-z0-9]'
    ),
    nombre_user VARCHAR(200) NOT NULL,
    email_user VARCHAR(150) NOT NULL,
    estado_user VARCHAR(10) NOT NULL CHECK (estado_user IN ('Activo', 'Inactivo'))
);

CREATE TABLE IF NOT EXISTS roles (
    id_role SERIAL PRIMARY KEY NOT NULL,
    nombre VARCHAR(200) NOT NULL
);

CREATE TABLE IF NOT EXISTS user_roles (
    id_user INTEGER NOT NULL PRIMARY KEY,
    id_role INTEGER NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_role) REFERENCES roles(id_role)
);

CREATE TABLE IF NOT EXISTS sesiones_usuario (
    id_sesion SERIAL PRIMARY KEY NOT NULL,
    id_user INTEGER NOT NULL,
    fecha_inicio_seccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin_seccion TIMESTAMP,
    estado_seccion VARCHAR(9) DEFAULT 'Activa',
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS log_auditoria (
    id_evento SERIAL PRIMARY KEY NOT NULL,
    id_user INTEGER,
    id_sesion INTEGER,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100) NOT NULL,
    registro_afectado INTEGER,
    descripcion VARCHAR(100),
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ip_origen VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL,
    FOREIGN KEY (id_sesion) REFERENCES sesiones_usuario(id_sesion) ON DELETE SET NULL
);

-- ============================================
-- TABLAS DE SOPORTE
-- ============================================

-- Tabla: proveedores
CREATE TABLE IF NOT EXISTS proveedores (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('oro', 'insumos', 'maquinaria')),
    contacto VARCHAR(200),
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: artesanos
CREATE TABLE IF NOT EXISTS artesanos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_ingreso DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: artesano_especialidad
CREATE TABLE IF NOT EXISTS artesano_especialidad (
    id SERIAL PRIMARY KEY,
    artesano_id INTEGER NOT NULL REFERENCES artesanos(id) ON DELETE CASCADE,
    especialidad VARCHAR(100) NOT NULL
);

-- ============================================
-- MÃ“DULO DE INVENTARIOS
-- ============================================

-- Tabla: ubicaciones (catÃ¡logo de ubicaciones fÃ­sicas)
CREATE TABLE IF NOT EXISTS ubicaciones (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    area VARCHAR(50),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE ubicaciones IS 'CatÃ¡logo de ubicaciones fÃ­sicas para inventario y maquinaria';
COMMENT ON COLUMN ubicaciones.area IS 'Ãrea general (AlmacÃ©n, Taller, Oficina, etc.)';

-- Tabla: inventario_oro
CREATE TABLE IF NOT EXISTS inventario_oro (
    id SERIAL PRIMARY KEY,
    tipo_oro VARCHAR(10) NOT NULL CHECK (tipo_oro IN ('10k', '14k', '18k', '22k', '24k')),
    peso_gramos DECIMAL(10,3) NOT NULL CHECK (peso_gramos > 0),
    precio_gramo DECIMAL(10,2) NOT NULL CHECK (precio_gramo > 0),
    proveedor_id INTEGER REFERENCES proveedores(id) ON DELETE SET NULL,
    fecha_ingreso DATE NOT NULL DEFAULT CURRENT_DATE,
    ubicacion VARCHAR(100),
    pureza DECIMAL(5,2) CHECK (pureza BETWEEN 0 AND 100),
    lote VARCHAR(50),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: inventario_maquinaria
CREATE TABLE IF NOT EXISTS inventario_maquinaria (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100) UNIQUE,
    fecha_compra DATE NOT NULL,
    valor_compra DECIMAL(10,2) NOT NULL CHECK (valor_compra >= 0),
    estado VARCHAR(20) NOT NULL DEFAULT 'operativa' CHECK (estado IN ('operativa', 'mantenimiento', 'averiada', 'fuera_servicio')),
    ultima_mantenimiento DATE,
    proxima_mantenimiento DATE,
    ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: inventario_insumos
CREATE TABLE IF NOT EXISTS inventario_insumos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL DEFAULT 0 CHECK (cantidad >= 0),
    unidad_medida VARCHAR(20) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0),
    stock_minimo DECIMAL(10,3) DEFAULT 0,
    proveedor_id INTEGER REFERENCES proveedores(id) ON DELETE SET NULL,
    ubicacion_id INTEGER REFERENCES ubicaciones(id) ON DELETE SET NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: movimientos_oro
CREATE TABLE IF NOT EXISTS movimientos_oro (
    id SERIAL PRIMARY KEY,
    inventario_oro_id INTEGER NOT NULL REFERENCES inventario_oro(id) ON DELETE RESTRICT,
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(500),
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: movimientos_insumos
CREATE TABLE IF NOT EXISTS movimientos_insumos (
    id SERIAL PRIMARY KEY,
    inventario_insumos_id INTEGER NOT NULL REFERENCES inventario_insumos(id) ON DELETE RESTRICT,
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(500),
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: movimientos_maquinaria
CREATE TABLE IF NOT EXISTS movimientos_maquinaria (
    id SERIAL PRIMARY KEY,
    inventario_maquinaria_id INTEGER NOT NULL REFERENCES inventario_maquinaria(id) ON DELETE RESTRICT,
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(500),
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: movimientos (historial general)
CREATE TABLE IF NOT EXISTS movimientos (
    id_movim SERIAL PRIMARY KEY,
    tipo_inventario VARCHAR(20) NOT NULL CHECK (tipo_inventario IN ('oro', 'maquinaria', 'insumos')),
    item_id INTEGER NOT NULL,
    tipo_movim VARCHAR(20) NOT NULL CHECK (tipo_movim IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad_movim DECIMAL(10,3) NOT NULL,
    motivo_movim VARCHAR(500),
    ref_movim VARCHAR(100),
    id_user INTEGER,
    id_role INTEGER,
    fecha_movim TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_user) REFERENCES seguridad.seg_usuario(id_usuario),
    FOREIGN KEY (id_role) REFERENCES seguridad.seg_rol(id_rol)
);

-- ============================================
-- MÃ“DULO DE PRODUCCIÃ“N
-- ============================================

-- Tabla: productos (catÃ¡logo)
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    tiempo_fabricacion_horas DECIMAL(5,2),
    precio_venta DECIMAL(10,2) CHECK (precio_venta >= 0),
    imagen_url VARCHAR(500),
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: recetas_oro (BOM - Bill of Materials)
CREATE TABLE IF NOT EXISTS recetas_oro (
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    inventario_oro_id INTEGER NOT NULL REFERENCES inventario_oro(id) ON DELETE RESTRICT,
    cantidad_requerida DECIMAL(10,3) NOT NULL CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (producto_id, inventario_oro_id)
);

-- Tabla: recetas_insumos (BOM - Bill of Materials)
CREATE TABLE IF NOT EXISTS recetas_insumos (
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    inventario_insumos_id INTEGER NOT NULL REFERENCES inventario_insumos(id) ON DELETE RESTRICT,
    cantidad_requerida DECIMAL(10,3) NOT NULL CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (producto_id, inventario_insumos_id)
);

-- Tabla: recetas_produccion (BOM - Bill of Materials)
CREATE TABLE IF NOT EXISTS recetas_produccion (
    id_receta_produccion SERIAL PRIMARY KEY NOT NULL,
    tipo_material_recetas VARCHAR(20) NOT NULL CHECK (tipo_material_recetas IN ('oro', 'insumo')),
    id_insumos INTEGER,
    id_oro INTEGER,
    id_productos INTEGER NOT NULL,
    cantidad_requerida DECIMAL(10,3) NOT NULL CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE,
    notas VARCHAR(200),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_insumos) REFERENCES inventario_insumos(id),
    FOREIGN KEY (id_oro) REFERENCES inventario_oro(id),
    FOREIGN KEY (id_productos) REFERENCES productos(id),
    CONSTRAINT chk_recetas_produccion_material CHECK (
        (tipo_material_recetas = 'oro' AND id_oro IS NOT NULL AND id_insumos IS NULL)
        OR
        (tipo_material_recetas = 'insumo' AND id_insumos IS NOT NULL AND id_oro IS NULL)
    )
);

-- Tabla: ordenes_produccion
CREATE TABLE IF NOT EXISTS ordenes_produccion (
    id SERIAL PRIMARY KEY,
    codigo_orden VARCHAR(50) UNIQUE NOT NULL,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE RESTRICT,
    cantidad INTEGER NOT NULL DEFAULT 1 CHECK (cantidad > 0),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio TIMESTAMP,
    fecha_fin_estimada TIMESTAMP,
    fecha_fin_real TIMESTAMP,
    artesano_id INTEGER REFERENCES artesanos(id) ON DELETE SET NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'en_proceso', 'terminada', 'cancelada', 'pausada')),
    prioridad VARCHAR(20) DEFAULT 'media' CHECK (prioridad IN ('baja', 'media', 'alta', 'urgente')),
    observaciones TEXT,
    creado_por INTEGER
);

-- Tabla: consumo_oro
CREATE TABLE IF NOT EXISTS consumo_oro (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER NOT NULL REFERENCES ordenes_produccion(id) ON DELETE CASCADE,
    inventario_oro_id INTEGER NOT NULL REFERENCES inventario_oro(id) ON DELETE RESTRICT,
    cantidad_consumida DECIMAL(10,3) NOT NULL CHECK (cantidad_consumida > 0),
    costo_unitario DECIMAL(10,2),
    --costo total, violacion 3NF, se calcula al vuelo, no se almacena
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INTEGER
);

-- Tabla: consumo_insumos
CREATE TABLE IF NOT EXISTS consumo_insumos (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER NOT NULL REFERENCES ordenes_produccion(id) ON DELETE CASCADE,
    inventario_insumos_id INTEGER NOT NULL REFERENCES inventario_insumos(id) ON DELETE RESTRICT,
    cantidad_consumida DECIMAL(10,3) NOT NULL CHECK (cantidad_consumida > 0),
    costo_unitario DECIMAL(10,2),
    --costo total, violacion 3NF, se calcula al vuelo, no se almacena
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INTEGER
);

-- Tabla: consumo_materiales
CREATE TABLE IF NOT EXISTS consumo_materiales (
    id_consumo SERIAL PRIMARY KEY NOT NULL,
    id_orden_prod INTEGER NOT NULL,
    tipo_material VARCHAR(20) NOT NULL CHECK (tipo_material IN ('oro', 'insumo')),
    id_insumos INTEGER,
    id_oro INTEGER,
    cantidad_consumida DECIMAL(10,3) NOT NULL CHECK (cantidad_consumida > 0),
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id_user INTEGER NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_orden_prod) REFERENCES ordenes_produccion(id),
    FOREIGN KEY (id_insumos) REFERENCES inventario_insumos(id),
    FOREIGN KEY (id_oro) REFERENCES inventario_oro(id),
    CONSTRAINT chk_consumo_materiales_material CHECK (
        (tipo_material = 'oro' AND id_oro IS NOT NULL AND id_insumos IS NULL)
        OR
        (tipo_material = 'insumo' AND id_insumos IS NOT NULL AND id_oro IS NULL)
    )
);

-- ============================================
-- MÃ“DULO DE CREACIONES Y ESTADÃSTICAS
-- ============================================

-- Tabla: creaciones_terminadas
CREATE TABLE IF NOT EXISTS creaciones_terminadas (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER REFERENCES ordenes_produccion(id) ON DELETE SET NULL,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE RESTRICT,
    codigo_pieza VARCHAR(50) UNIQUE NOT NULL,
    artesano_id INTEGER REFERENCES artesanos(id) ON DELETE SET NULL,
    fecha_terminado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_final_gramos DECIMAL(10,3) CHECK (peso_final_gramos > 0),
    costo_materiales DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_mano_obra DECIMAL(10,2) NOT NULL DEFAULT 0,
    --costo total, violaciÃ³n 3NF, se calcula al vuelo, no se almacena
    tiempo_real_horas DECIMAL(5,2),
    calidad VARCHAR(1) CHECK (calidad IN ('A', 'B', 'C')),
    observaciones TEXT,
    vendida BOOLEAN DEFAULT FALSE,
    fecha_venta TIMESTAMP,
    precio_venta_real DECIMAL(10,2),
    cliente_id INTEGER,
    ubicacion_actual VARCHAR(100) DEFAULT 'inventario',
    CONSTRAINT chk_venta CHECK (
        (vendida = FALSE AND fecha_venta IS NULL AND precio_venta_real IS NULL) OR
        (vendida = TRUE AND fecha_venta IS NOT NULL AND precio_venta_real IS NOT NULL)
    )
);

CREATE TABLE IF NOT EXISTS retrabajos (
    id_retrabajo SERIAL PRIMARY KEY NOT NULL,
    id_terminados INTEGER NOT NULL,
    motivo_retrabajo VARCHAR(50) NOT NULL,
    fecha_retrabajo TIMESTAMP DEFAULT NOW() NOT NULL,
    estado VARCHAR(10) CHECK (estado IN ('Pendiente', 'En proceso', 'Finalizado')) NOT NULL,
    FOREIGN KEY (id_terminados) REFERENCES creaciones_terminadas(id)
);


-- ============================================
-- ÃNDICES PARA OPTIMIZACIÃ“N
-- ============================================

-- Inventarios
CREATE INDEX IF NOT EXISTS idx_inventario_oro_tipo ON inventario_oro(tipo_oro);
CREATE INDEX IF NOT EXISTS idx_inventario_oro_proveedor ON inventario_oro(proveedor_id);
CREATE INDEX IF NOT EXISTS idx_inventario_insumos_categoria ON inventario_insumos(categoria);
CREATE INDEX IF NOT EXISTS idx_inventario_insumos_proveedor ON inventario_insumos(proveedor_id);
CREATE INDEX IF NOT EXISTS idx_inventario_insumos_ubicacion ON inventario_insumos(ubicacion_id);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_estado ON inventario_maquinaria(estado);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_ubicacion ON inventario_maquinaria(ubicacion_id);

-- Ubicaciones
CREATE INDEX IF NOT EXISTS idx_ubicaciones_area ON ubicaciones(area);
CREATE INDEX IF NOT EXISTS idx_ubicaciones_activo ON ubicaciones(activo);

-- Movimientos
CREATE INDEX IF NOT EXISTS idx_mov_oro_item ON movimientos_oro(inventario_oro_id);
CREATE INDEX IF NOT EXISTS idx_mov_oro_fecha ON movimientos_oro(fecha);
CREATE INDEX IF NOT EXISTS idx_mov_insumos_item ON movimientos_insumos(inventario_insumos_id);
CREATE INDEX IF NOT EXISTS idx_mov_insumos_fecha ON movimientos_insumos(fecha);
CREATE INDEX IF NOT EXISTS idx_mov_maquinaria_item ON movimientos_maquinaria(inventario_maquinaria_id);
CREATE INDEX IF NOT EXISTS idx_mov_maquinaria_fecha ON movimientos_maquinaria(fecha);

-- ProducciÃ³n
CREATE INDEX IF NOT EXISTS idx_ordenes_estado ON ordenes_produccion(estado);
CREATE INDEX IF NOT EXISTS idx_ordenes_artesano ON ordenes_produccion(artesano_id);
CREATE INDEX IF NOT EXISTS idx_ordenes_fecha ON ordenes_produccion(fecha_creacion);
CREATE INDEX IF NOT EXISTS idx_consumo_oro_orden ON consumo_oro(orden_produccion_id);
CREATE INDEX IF NOT EXISTS idx_consumo_insumos_orden ON consumo_insumos(orden_produccion_id);

-- Creaciones
CREATE INDEX IF NOT EXISTS idx_creaciones_fecha ON creaciones_terminadas(fecha_terminado);
CREATE INDEX IF NOT EXISTS idx_creaciones_producto ON creaciones_terminadas(producto_id);
CREATE INDEX IF NOT EXISTS idx_creaciones_artesano ON creaciones_terminadas(artesano_id);
CREATE INDEX IF NOT EXISTS idx_creaciones_vendida ON creaciones_terminadas(vendida);

-- Artesanos
CREATE INDEX IF NOT EXISTS idx_artesano_especialidad_artesano ON artesano_especialidad(artesano_id);


-- ============================================
-- TRIGGERS Y FUNCIONES PL/PGSQL
-- (Ejecutar scripts en DB/functions/ despues de crear tablas)

-- DATOS DE EJEMPLO (OPCIONAL)
-- ============================================

-- Insertar ubicaciones por defecto
INSERT INTO ubicaciones (nombre, area)
SELECT v.nombre, v.area
FROM (VALUES
    ('Almacen Principal', 'Almacen'),
    ('Taller de Produccion', 'Taller'),
    ('Taller de Acabados', 'Taller'),
    ('Bodega de Insumos', 'Bodega'),
    ('Oficina Administrativa', 'Oficina')
) AS v(nombre, area)
WHERE NOT EXISTS (
    SELECT 1 FROM ubicaciones u
    WHERE u.nombre = v.nombre AND u.area = v.area
);

-- Insertar algunos proveedores de ejemplo
INSERT INTO proveedores (nombre, tipo, contacto, telefono, email)
SELECT v.nombre, v.tipo, v.contacto, v.telefono, v.email
FROM (VALUES
    ('Oro Internacional SA', 'oro', 'Juan Perez', '+57-300-1234567', 'contacto@orointernacional.com'),
    ('Insumos y Piedras Ltda', 'insumos', 'Maria Gonzalez', '+57-301-7654321', 'ventas@insumos.com'),
    ('Maquinaria Industrial', 'maquinaria', 'Carlos Rodriguez', '+57-302-9876543', 'info@maquinaria.com')
) AS v(nombre, tipo, contacto, telefono, email)
WHERE NOT EXISTS (
    SELECT 1 FROM proveedores p
    WHERE p.nombre = v.nombre AND p.tipo = v.tipo
);

-- Insertar algunos artesanos de ejemplo
INSERT INTO artesanos (nombre, apellido, telefono, fecha_ingreso)
SELECT v.nombre, v.apellido, v.telefono, v.fecha_ingreso
FROM (VALUES
    ('Pedro', 'Martinez', '+57-310-1111111', '2020-01-15'),
    ('Ana', 'Lopez', '+57-311-2222222', '2021-03-20'),
    ('Luis', 'Garcia', '+57-312-3333333', '2019-06-10')
) AS v(nombre, apellido, telefono, fecha_ingreso)
WHERE NOT EXISTS (
    SELECT 1 FROM artesanos a
    WHERE a.nombre = v.nombre AND a.apellido = v.apellido
);

INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Engaste y soldadura'
FROM artesanos a
WHERE a.nombre = 'Pedro' AND a.apellido = 'Martinez'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Engaste y soldadura'
  );
INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Diseno y pulido'
FROM artesanos a
WHERE a.nombre = 'Ana' AND a.apellido = 'Lopez'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Diseno y pulido'
  );
INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Fundicion'
FROM artesanos a
WHERE a.nombre = 'Luis' AND a.apellido = 'Garcia'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Fundicion'
  );


-- ============================================
-- COMENTARIOS EN TABLAS
-- ============================================

COMMENT ON TABLE inventario_oro IS 'Inventario de oro por tipo de quilate';
COMMENT ON TABLE inventario_maquinaria IS 'Registro de maquinaria y equipos';
COMMENT ON TABLE inventario_insumos IS 'Inventario de insumos (piedras, cadenas, etc.)';
COMMENT ON TABLE ubicaciones IS 'CatÃ¡logo de ubicaciones fÃ­sicas para inventario y maquinaria';
COMMENT ON TABLE ordenes_produccion IS 'Ã“rdenes de fabricaciÃ³n de productos';
COMMENT ON TABLE creaciones_terminadas IS 'Registro de piezas terminadas con costos y fechas';
-- COMMENT ON TABLE estadisticas_produccion IS 'EstadÃ­sticas pre-calculadas por periodo';

-- ============================================
-- FIN DEL SCRIPT
-- ============================================



