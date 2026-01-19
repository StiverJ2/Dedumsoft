-- ============================================================================
-- DEDUMSOFT - ESQUEMA DE BASE DE DATOS
-- ============================================================================
-- 
-- Sistema de Gestión para Joyería "Joyas Van"
-- Base de Datos: PostgreSQL 17+
-- Fecha: 2026-01-18
-- 
-- DESCRIPCIÓN:
-- Define la estructura completa de la base de datos incluyendo:
-- - Esquemas (joyeria, seguridad)
-- - Tablas lookup (estados, prioridades, tipos)
-- - Tablas de catálogo (productos, proveedores, artesanos)
-- - Tablas de inventario (oro, insumos, maquinaria)
-- - Tablas de producción (órdenes, consumos, creaciones)
-- - Tablas de seguridad (usuarios, roles, permisos)
-- 
-- DISEÑO:
-- - Completamente normalizado (3FN)
-- - Sin columnas 'codigo' redundantes (usar id)
-- - Sin CHECK constraints con valores hardcoded (usar tablas lookup)
-- - Soft-delete con campo 'activo' o 'deleted_at'
-- 
-- ESQUEMAS:
-- - joyeria: Tablas de negocio (inventario, producción, ventas)
-- - seguridad: Tablas de autenticación y autorización
-- - public: Extensiones y funciones globales
-- 
-- EJECUCIÓN:
--   psql -d db_dedumsoft -f DB/db_schema.sql
-- 
-- ============================================================================
-- DEDUMSOFT - SISTEMA DE GESTIÓN PARA JOYERÍA
-- Base de Datos: PostgreSQL 17+
-- Fecha: 2026-01-18
-- ============================================
-- Diseño completamente normalizado:
-- - Sin columnas 'codigo' redundantes (usar id)
-- - Sin CHECK constraints con valores hardcoded (usar tablas lookup)
-- ============================================

-- ============================================
-- ESQUEMAS
-- ============================================
CREATE SCHEMA IF NOT EXISTS joyeria;
CREATE SCHEMA IF NOT EXISTS seguridad;

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- TABLAS LOOKUP (valores que antes eran CHECK)
-- ============================================

-- Estados de maquinaria
CREATE TABLE IF NOT EXISTS estados_maquinaria (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(20) DEFAULT 'neutral',
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE
);

-- Estados de orden de producción
CREATE TABLE IF NOT EXISTS estados_orden (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(20) DEFAULT 'neutral',
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE
);

-- Prioridades
CREATE TABLE IF NOT EXISTS prioridades (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(20) DEFAULT 'neutral',
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE
);

-- Tipos de material (oro, insumo)
CREATE TABLE IF NOT EXISTS tipos_material (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
);

-- Niveles de calidad
CREATE TABLE IF NOT EXISTS niveles_calidad (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(10) NOT NULL UNIQUE,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE
);

-- ============================================
-- TABLAS DE CATÁLOGO
-- ============================================

-- Áreas de la joyería
CREATE TABLE IF NOT EXISTS areas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tipos de oro
CREATE TABLE IF NOT EXISTS tipos_oro (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    kilates DECIMAL(5,2),
    pureza_porcentaje DECIMAL(5,2),
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tipos de proveedor
CREATE TABLE IF NOT EXISTS tipos_proveedor (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tipos de maquinaria
CREATE TABLE IF NOT EXISTS tipos_maquinaria (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- ESQUEMA LEGACY DE SEGURIDAD (seg_*)
-- ============================================
CREATE TABLE IF NOT EXISTS seguridad.seg_rol (
    id_rol SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL,
    comentario TEXT NOT NULL,
    deleted_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seguridad.seg_usuario (
    id_usuario SERIAL PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    nombre TEXT NOT NULL,
    clave TEXT NOT NULL,
    rolid INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP,
    CONSTRAINT seg_usuario_rol_fk FOREIGN KEY (rolid)
        REFERENCES seguridad.seg_rol (id_rol)
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS seguridad.seg_login (
    id_login SERIAL PRIMARY KEY,
    token TEXT NOT NULL,
    refresh_token TEXT,
    refresh_expira TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_token BOOLEAN NOT NULL DEFAULT TRUE,
    timestamp_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    timestamp_expira TIMESTAMP NOT NULL,
    ip_origen INET,
    user_agent TEXT,
    usuarioid INTEGER NOT NULL,
    CONSTRAINT seg_login_usuario_fk FOREIGN KEY (usuarioid)
        REFERENCES seguridad.seg_usuario (id_usuario)
        ON UPDATE NO ACTION
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seguridad.seg_menu (
    id_menu SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL,
    comentario TEXT NOT NULL,
    ruta TEXT NOT NULL,
    deleted_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seguridad.seg_menurol (
    id_menu_rol SERIAL PRIMARY KEY,
    abrir BOOLEAN NOT NULL,
    guardar BOOLEAN NOT NULL,
    editar BOOLEAN NOT NULL,
    eliminar BOOLEAN NOT NULL,
    rolid INTEGER NOT NULL,
    menuid INTEGER NOT NULL,
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
-- TABLAS PRINCIPALES
-- ============================================

-- Ubicaciones fisicas
CREATE TABLE IF NOT EXISTS ubicaciones (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    area_id INTEGER REFERENCES areas(id),
    capacidad INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Proveedores
CREATE TABLE IF NOT EXISTS proveedores (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo_proveedor_id INTEGER REFERENCES tipos_proveedor(id),
    tipo VARCHAR(50),
    contacto VARCHAR(100),
    telefono VARCHAR(50),
    email VARCHAR(150),
    direccion TEXT,
    rfc VARCHAR(20),
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Artesanos (todos los usuarios con rol OPERADOR son artesanos)
CREATE TABLE IF NOT EXISTS artesanos (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER UNIQUE REFERENCES seguridad.seg_usuario(id_usuario) ON DELETE SET NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100),
    telefono VARCHAR(50),
    email VARCHAR(150),
    fecha_ingreso DATE DEFAULT CURRENT_DATE,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Especialidades de artesanos
CREATE TABLE IF NOT EXISTS artesano_especialidad (
    id SERIAL PRIMARY KEY,
    artesano_id INTEGER REFERENCES artesanos(id) ON DELETE CASCADE,
    especialidad VARCHAR(100) NOT NULL,
    UNIQUE(artesano_id, especialidad)
);

-- ============================================
-- MÓDULO DE INVENTARIOS
-- ============================================

-- Inventario de Oro
CREATE TABLE IF NOT EXISTS inventario_oro (
    id SERIAL PRIMARY KEY,
    tipo_oro_id INTEGER REFERENCES tipos_oro(id),
    peso_gramos DECIMAL(10,3) NOT NULL CHECK (peso_gramos > 0),
    precio_gramo DECIMAL(10,2) NOT NULL CHECK (precio_gramo > 0),
    proveedor_id INTEGER REFERENCES proveedores(id),
    fecha_ingreso DATE DEFAULT CURRENT_DATE,
    ubicacion VARCHAR(100),
    pureza DECIMAL(5,2) CHECK (pureza BETWEEN 0 AND 100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Inventario de Maquinaria (estado normalizado)
CREATE TABLE IF NOT EXISTS inventario_maquinaria (
    id SERIAL,
    sku VARCHAR(100) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo_maquinaria_id INTEGER REFERENCES tipos_maquinaria(id),
    marca VARCHAR(100),
    modelo VARCHAR(100),
    fecha_compra DATE,
    valor_compra DECIMAL(12,2) CHECK (valor_compra >= 0),
    estado_id INTEGER REFERENCES estados_maquinaria(id) DEFAULT 1,
    ultima_mantenimiento DATE,
    proxima_mantenimiento DATE,
    ubicacion_id INTEGER REFERENCES ubicaciones(id),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (id, sku),
    UNIQUE (id),
    UNIQUE (sku)
);

-- Inventario de Insumos
CREATE TABLE IF NOT EXISTS inventario_insumos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(100),
    descripcion TEXT,
    cantidad DECIMAL(10,3) DEFAULT 0 CHECK (cantidad >= 0),
    unidad_medida VARCHAR(20),
    precio_unitario DECIMAL(10,2) CHECK (precio_unitario >= 0),
    stock_minimo DECIMAL(10,3) DEFAULT 0,
    proveedor_id INTEGER REFERENCES proveedores(id),
    ubicacion_id INTEGER REFERENCES ubicaciones(id),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- ============================================
-- MÓDULO DE PRODUCCIÓN
-- ============================================

-- Productos (catálogo)
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo VARCHAR(50),
    descripcion TEXT,
    tiempo_fabricacion_horas DECIMAL(5,2),
    precio_venta DECIMAL(10,2) CHECK (precio_venta >= 0),
    imagen_url VARCHAR(500),
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Órdenes de Producción (estado y prioridad normalizados)
CREATE TABLE IF NOT EXISTS ordenes_produccion (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER DEFAULT 1 CHECK (cantidad > 0),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio TIMESTAMP,
    fecha_fin_estimada TIMESTAMP,
    fecha_fin_real TIMESTAMP,
    artesano_id INTEGER REFERENCES artesanos(id),
    estado_id INTEGER REFERENCES estados_orden(id) DEFAULT 1,
    prioridad_id INTEGER REFERENCES prioridades(id) DEFAULT 2,
    observaciones TEXT
);

-- Recetas de Producción (Bill of Materials) - tipo_material normalizado
CREATE TABLE IF NOT EXISTS recetas_produccion (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    tipo_material_id INTEGER REFERENCES tipos_material(id),
    material_id INTEGER,
    cantidad_requerida DECIMAL(10,3) CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consumo de Materiales - tipo_material normalizado
CREATE TABLE IF NOT EXISTS consumo_materiales (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER REFERENCES ordenes_produccion(id),
    tipo_material_id INTEGER REFERENCES tipos_material(id),
    material_id INTEGER,
    cantidad_consumida DECIMAL(10,3) CHECK (cantidad_consumida > 0),
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INTEGER
);

-- Creaciones Terminadas - calidad normalizada
CREATE TABLE IF NOT EXISTS creaciones_terminadas (
    id SERIAL PRIMARY KEY,
    orden_id INTEGER REFERENCES ordenes_produccion(id),
    producto_id INTEGER REFERENCES productos(id),
    artesano_id INTEGER REFERENCES artesanos(id),
    fecha_terminado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    peso_final_gramos DECIMAL(10,3) CHECK (peso_final_gramos > 0),
    costo_materiales DECIMAL(10,2) DEFAULT 0,
    costo_mano_obra DECIMAL(10,2) DEFAULT 0,
    tiempo_real_horas DECIMAL(5,2),
    calidad_id INTEGER REFERENCES niveles_calidad(id),
    observaciones TEXT,
    vendida BOOLEAN DEFAULT FALSE,
    fecha_venta TIMESTAMP,
    precio_venta_real DECIMAL(10,2),
    ubicacion_actual VARCHAR(100) DEFAULT 'inventario'
);

-- ============================================
-- MÓDULO DE MOVIMIENTOS (para triggers)
-- ============================================

-- Tipos de movimiento
CREATE TABLE IF NOT EXISTS tipos_movimiento (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla general de movimientos
CREATE TABLE IF NOT EXISTS movimientos (
    id SERIAL PRIMARY KEY,
    tipo_inventario VARCHAR(20) NOT NULL,
    item_id INTEGER NOT NULL,
    tipo_movim VARCHAR(50),
    cantidad_movim DECIMAL(10,3),
    motivo_movim TEXT,
    ref_movim VARCHAR(100),
    id_user INTEGER,
    id_role INTEGER,
    fecha_movim TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movimientos específicos de oro
CREATE TABLE IF NOT EXISTS movimientos_oro (
    id SERIAL PRIMARY KEY,
    inventario_oro_id INTEGER REFERENCES inventario_oro(id),
    tipo_movimiento VARCHAR(50),
    cantidad DECIMAL(10,3),
    motivo TEXT,
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movimientos específicos de insumos
CREATE TABLE IF NOT EXISTS movimientos_insumos (
    id SERIAL PRIMARY KEY,
    inventario_insumos_id INTEGER REFERENCES inventario_insumos(id),
    tipo_movimiento VARCHAR(50),
    cantidad DECIMAL(10,3),
    motivo TEXT,
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movimientos específicos de maquinaria
CREATE TABLE IF NOT EXISTS movimientos_maquinaria (
    id SERIAL PRIMARY KEY,
    inventario_maquinaria_id INTEGER REFERENCES inventario_maquinaria(id),
    tipo_movimiento VARCHAR(50),
    cantidad INTEGER DEFAULT 1,
    motivo TEXT,
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consumo de oro en producción
CREATE TABLE IF NOT EXISTS consumo_oro (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER REFERENCES ordenes_produccion(id),
    inventario_oro_id INTEGER REFERENCES inventario_oro(id),
    cantidad_consumida DECIMAL(10,3) CHECK (cantidad_consumida > 0),
    usuario_id INTEGER,
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consumo de insumos en producción
CREATE TABLE IF NOT EXISTS consumo_insumos (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER REFERENCES ordenes_produccion(id),
    inventario_insumos_id INTEGER REFERENCES inventario_insumos(id),
    cantidad_consumida DECIMAL(10,3) CHECK (cantidad_consumida > 0),
    usuario_id INTEGER,
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- ÍNDICES
-- ============================================
CREATE INDEX IF NOT EXISTS idx_proveedores_tipo ON proveedores(tipo_proveedor_id);
CREATE INDEX IF NOT EXISTS idx_proveedores_activo ON proveedores(activo);
CREATE INDEX IF NOT EXISTS idx_inventario_oro_tipo ON inventario_oro(tipo_oro_id);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_tipo ON inventario_maquinaria(tipo_maquinaria_id);
CREATE INDEX IF NOT EXISTS idx_inventario_maquinaria_estado ON inventario_maquinaria(estado_id);
CREATE INDEX IF NOT EXISTS idx_ubicaciones_area ON ubicaciones(area_id);
CREATE INDEX IF NOT EXISTS idx_ordenes_estado ON ordenes_produccion(estado_id);
CREATE INDEX IF NOT EXISTS idx_ordenes_prioridad ON ordenes_produccion(prioridad_id);
CREATE INDEX IF NOT EXISTS idx_ordenes_artesano ON ordenes_produccion(artesano_id);
CREATE INDEX IF NOT EXISTS idx_movimientos_tipo ON movimientos(tipo_inventario);
CREATE INDEX IF NOT EXISTS idx_movimientos_fecha ON movimientos(fecha_movim);
CREATE INDEX IF NOT EXISTS idx_movimientos_oro_inv ON movimientos_oro(inventario_oro_id);
CREATE INDEX IF NOT EXISTS idx_movimientos_insumos_inv ON movimientos_insumos(inventario_insumos_id);
CREATE INDEX IF NOT EXISTS idx_movimientos_maquinaria_inv ON movimientos_maquinaria(inventario_maquinaria_id);
