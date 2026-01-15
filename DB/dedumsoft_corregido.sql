
-- ============================================
-- SISTEMA DE GESTIÓN PARA JOYERÍA
-- Base de Datos: PostgreSQL
-- ============================================




-- ====================================================
-- LIMPIEZA DE TABLAS
-- ====================================================
DROP TABLE IF EXISTS retrabajos; 
DROP TABLE IF EXISTS creaciones_terminadas;
DROP TABLE IF EXISTS consumo_materiales;
DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS recetas_produccion;
DROP TABLE IF EXISTS ordenes_produccion;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS sesiones_usuario;
DROP TABLE IF EXISTS log_auditoria;

DROP TABLE IF EXISTS inventario_oro;
DROP TABLE IF EXISTS inventario_maquinaria;
DROP TABLE IF EXISTS inventario_insumos;

DROP TABLE IF EXISTS productos;

DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;

-- Opcional:
-- DROP SCHEMA IF EXISTS joyeria CASCADE;



-- Crear esquema (opcional)
CREATE SCHEMA IF NOT EXISTS joyeria;
SET search_path TO joyeria, public;


-- ============================================
-- TABLAS DE USUARIOS
-- ============================================

CREATE TABLE users (
    id_user NUMERIC(10) PRIMARY KEY NOT NULL,
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


CREATE TABLE roles (
    id_role NUMERIC(10) PRIMARY KEY     NOT NULL,
    nombre VARCHAR(200)                 NOT NULL
);

CREATE TABLE user_roles (
    id_user NUMERIC(10)      NOT NULL PRIMARY KEY ,
    id_role NUMERIC(10)   NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_role) REFERENCES roles(id_role) 
);

CREATE TABLE sesiones_usuario (
    id_sesion NUMERIC(10) PRIMARY KEY NOT NULL,
    id_user NUMERIC(10) NOT NULL,                          -- Usuario que inicia sesión
    fecha_inicio_seccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin_seccion TIMESTAMP,
    estado_seccion VARCHAR(9) DEFAULT 'Activa',           -- Activa, Cerrada, Expirada
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE log_auditoria (
    id_evento NUMERIC(10) PRIMARY KEY NOT NULL,
    id_user NUMERIC(10) NOT NULL,
    id_sesion NUMERIC(10) NOT NULL,
    accion VARCHAR(100) NOT NULL,                -- Ejemplo: "INSERT", "UPDATE", "DELETE", "LOGIN"
    tabla_afectada VARCHAR(100) NOT NULL,
    registro_afectado NUMERIC(10) NOT NULL,                       -- ID del registro modificado (opcional)
    descripcion VARCHAR(100),                            -- Detalle: qué cambió, antes/después, etc.
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ip_origen VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL,
    FOREIGN KEY (id_sesion) REFERENCES sesiones_usuario(id_sesion) ON DELETE SET NULL
);

-- ============================================
-- TABLAS DE SOPORTE
-- ============================================

-- Tabla: proveedores
CREATE TABLE proveedores (
    id_proveedor NUMERIC (12) PRIMARY KEY NOT NULL,
    tipo_id_proveedor VARCHAR(3) NOT NULL CHECK (tipo_id_proveedor IN ('nit', 'cc')),
    nombre_proveedor VARCHAR(200) NOT NULL,
    tipo_proveedor VARCHAR(50) NOT NULL		 CHECK (tipo_proveedor IN ('oro', 'insumos', 'maquinaria')),
    telefono_proveedor NUMERIC(15) NOT NULL,
    email_proveedor VARCHAR(150) NOT NULL,
    direccion_proveedor VARCHAR(200) NOT NULL,
    ciudad_proveedor VARCHAR(100) NOT NULL,
    activo_proveedor BOOLEAN DEFAULT TRUE NOT NULL,
    fecha_registro_proveedor TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: artesanos
--CREATE TABLE artesanos (
  --  id_artesano NUMERIC (12) PRIMARY KEY NOT NULL,
  ---  nombre_artesanos VARCHAR(100) NOT NULL,
    --especialidad VARCHAR(100),
   -- telefono_artesano NUMERIC(15) NOT NULL,
   -- email_artesano VARCHAR(150) NOT NULL,
   -- direccion_artesano VARCHAR(200) NOT NULL,
    --ciudad_artesano VARCHAR(100) NOT NULL,
    --activo_artesano BOOLEAN DEFAULT TRUE NOT NULL,
    --fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
--);

-- ============================================
-- MÓDULO DE INVENTARIOS
-- ============================================

-- Tabla: inventario_oro
CREATE TABLE inventario_oro (
    id_oro INT PRIMARY KEY NOT NULL,
    tipo_oro VARCHAR(4) NOT NULL CHECK (tipo_oro IN ('10k', '14k', '18k', '22k', '24k')),
    peso_gramos DECIMAL(10,3) NOT NULL CHECK (peso_gramos > 0),
    precio_gramo DECIMAL(10,2) NOT NULL CHECK (precio_gramo > 0),
    fecha_ingreso_oro DATE NOT NULL DEFAULT CURRENT_DATE,
    ubicacion_oro VARCHAR(100)NOT NULL,
    pureza_oro DECIMAL(5,2) CHECK (pureza_oro BETWEEN 0 AND 100) NOT NULL,
    lote_oro VARCHAR(50) NOT NULL,
    fecha_registro_oro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_proveedor NUMERIC (12)  NOT NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
    );

-- Tabla: inventario_maquinaria
CREATE TABLE inventario_maquinaria (
    id_maquinaria VARCHAR (15) PRIMARY KEY NOT NULL,
    nombre_maquinaria VARCHAR(200) NOT NULL,
    tipo_maquinaria VARCHAR(100) NOT NULL,
    marca_maquinaria VARCHAR(25)NOT NULL,
    modelo_maquinaria NUMERIC(4)NOT NULL,
    fecha_compra_maquinaria DATE NOT NULL,
    valor_compra_maquinaria DECIMAL(10,2) NOT NULL CHECK (valor_compra_maquinaria >= 0),
    estado_maquinaria VARCHAR(20) NOT NULL DEFAULT 'operativa' CHECK (estado_maquinaria IN ('operativa', 'mantenimiento', 'averiada', 'fuera_servicio')),
    ultima_mantenimiento_maquinaria DATE,
    proxima_mantenimiento_maquinaria DATE,
    ubicacion_maquinaria VARCHAR(20)NOT NULL,
    fecha_registro_maquinaria TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_proveedor NUMERIC (12)  NOT NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
);

-- Tabla: inventario_insumos
CREATE TABLE inventario_insumos (
    id_insumos NUMERIC(20)  PRIMARY KEY NOT NULL,
    nombre_insumos VARCHAR(50) NOT NULL,
    categoria_insumos VARCHAR(100) NOT NULL,
    descripcion_insumo VARCHAR(100),
    cantidad_insumo DECIMAL(10,3) NOT NULL DEFAULT 0 CHECK (cantidad_insumo >= 0),
    unidad_medida_insumo  VARCHAR(20) NOT NULL,
    precio_unitario_insumo  DECIMAL(10,2) NOT NULL CHECK (precio_unitario_insumo >= 0),
    stock_minimo_insumo  DECIMAL(10,3) DEFAULT 0,
    ubicacion_insumo  VARCHAR(100),
    fecha_registro_insumo  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_proveedor NUMERIC (12)  NOT NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
);

-- Tabla: movimientos (historial de todos los inventarios)
CREATE TABLE movimientos(
    id_movim SERIAL PRIMARY KEY,
    tipo_inventario VARCHAR(20) NOT NULL CHECK (tipo_inventario IN ('oro', 'maquinaria', 'insumos')),
    item_id INTEGER NOT NULL,
    tipo_movim  VARCHAR(20) NOT NULL CHECK (tipo_movim IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad__movim DECIMAL(10,3) NOT NULL,
    motivo__movim VARCHAR(500) NOT NULL,
    ref_movim   NUMERIC(20) NOT NULL,
    id_user NUMERIC(10) NOT NULL,
    id_role NUMERIC(10) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_role) REFERENCES roles(id_role),
    fecha_movim TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- ============================================
-- MÓDULO DE PRODUCCIÓN
-- ============================================

-- Tabla: productos (catálogo)
CREATE TABLE productos (
    id_productos INTEGER    PRIMARY KEY NOT NULL,
    nombre_productos VARCHAR(200) NOT NULL,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL, ---ESTO PARA QUE ES ???
    tipo_productos VARCHAR(50) NOT NULL,
    descripcion_productos VARCHAR(200),
    tiempo_fabricacion_horas DECIMAL(5,2) NOT NULL,
    precio_venta_productos DECIMAL(10,2) NOT NULL CHECK (precio_venta_productos >= 0),
    imagen_url VARCHAR(500),
    activo BOOLEAN DEFAULT TRUE NOT NULL,
    fecha_registro_productos   TIMESTAMP 	NOT NULL
);

-- Tabla: recetas_produccion (BOM - Bill of Materials)
CREATE TABLE recetas_produccion (
    id_receta_produccion INTEGER PRIMARY KEY    NOT NULL,
    tipo_material_recetas     VARCHAR(20) NOT NULL CHECK (tipo_material_recetas  IN ('oro', 'insumo')),
    id_insumos NUMERIC(20) NOT NULL,
    cantidad_requerida DECIMAL(10,3) NOT NULL CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE, ---ESTA PARA QUE SIRVE LEONAR??
    notas VARCHAR(200),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (id_insumos)    REFERENCES inventario_insumos(id_insumos),
    FOREIGN KEY (id_productos) REFERENCES productos(id_productos)

);

-- Tabla: ordenes_produccion
CREATE TABLE ordenes_produccion (
    id_orden_prod    INTEGER    PRIMARY KEY     NOT NULL,
    codigo_orden    NUMERIC(20)     NOT NULL,
    id_productos        INTEGER      NOT NULL,      
    cantidad_orden    NUMERIC(10)       NOT NULL DEFAULT 1 CHECK (cantidad_orden  > 0),
    fecha_creacion TIMESTAMP    NOT NULL,
    fecha_inicio TIMESTAMP      NOT NULL,
    fecha_fin_estimada TIMESTAMP    NOT NULL,
    fecha_fin_real TIMESTAMP NOT NULL,
    id_user     NUMERIC (10)    NOT NULL,
    estado_orden_prod      VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado_orden_prod IN ('pendiente', 'en_proceso', 'terminada', 'cancelada', 'pausada')),
    prioridad_orden VARCHAR (8)  DEFAULT 'media' CHECK (prioridad_orden IN ('baja', 'media', 'alta', 'urgente')),
    observaciones VARCHAR(200),
    FOREIGN key (id_user)   REFERENCES users(id_user),
	FOREIGN KEY (id_productos) REFERENCES productos(id_productos) 
 ---creado_por INTEGER --- NO SE NECESITA YA ESTA EL id_user
);

-- Tabla: consumo_materiales
CREATE TABLE consumo_materiales (
    id_consumo      INTEGER     PRIMARY KEY NOT NULL,
    id_orden_prod    INTEGER       NOT NULL,
    tipo_material VARCHAR(20) NOT NULL CHECK (tipo_material IN ('oro', 'insumo')),
    id_insumos NUMERIC(20) NOT NULL,
    cantidad_consumida DECIMAL(10,3) NOT NULL CHECK (cantidad_consumida > 0),
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id_user NUMERIC(10) NOT NULL,
    FOREIGN KEY(id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_orden_prod) REFERENCES ordenes_produccion(id_orden_prod),
    FOREIGN KEY (id_insumos) REFERENCES inventario_insumos(id_insumos)
);


-- ============================================
-- MÓDULO DE CREACIONES Y ESTADÍSTICAS
-- ============================================

-- Tabla: creaciones_terminadas
CREATE TABLE creaciones_terminadas(
    id_terminados  	INTEGER		 PRIMARY KEY     NOT NULL,
    id_orden_prod    INTEGER       NOT NULL,
    id_insumos NUMERIC(20) NOT NULL,
    codigo_pieza VARCHAR(50) UNIQUE NOT NULL,
    id_user     NUMERIC(10)     NOT NULL,
    fecha_terminado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_final_gramos DECIMAL(10,3) CHECK (peso_final_gramos > 0),
    costo_materiales DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_mano_obra DECIMAL(10,2) NOT NULL DEFAULT 0,
    --costo total, violación 3NF, se calcula al vuelo, no se almacena
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
    ),
    FOREIGN KEY(id_user)             REFERENCES users(id_user),
    FOREIGN KEY (id_orden_prod)         REFERENCES ordenes_produccion(id_orden_prod)ON DELETE SET NULL,
    FOREIGN KEY (id_insumos)    REFERENCES inventario_insumos(id_insumos)ON DELETE RESTRICT
);

CREATE TABLE retrabajos (
    id_retrabajo     DECIMAL(12)        PRIMARY KEY                                     NOT NULL,   -- ID del retrabajo
    id_terminados  	 INTEGER													          NOT NULL,  -- ID del producto final asociado
    motivo_retrabajo            VARCHAR(50)                                             NOT NULL,   -- Razón del retrabajo
    fecha_retrabajo            TIMESTAMP          DEFAULT NOW()                         NOT NULL,   -- Fecha de registro
    estado VARCHAR(10) CHECK (estado IN ('Pendiente', 'En proceso', 'Finalizado'))      NOT NULL,   -- Estado del retrabajo
    FOREIGN KEY (id_terminados)    REFERENCES creaciones_terminadas(id_terminados)
    
);


----------------------------------------------------------------------------------------------------------

    --total_piezas, violación 3NF, se calcula al vuelo con COUNT(id) de creaciones_terminadas
	---CORREGIDO
CREATE VIEW vista_total_piezas AS
SELECT 
    id_orden_prod,
    COUNT(*) AS total_piezas
FROM creaciones_terminadas
GROUP BY id_orden_prod;




 -- piezas_por_tipo JSONB, violación 1NF, si hay un campo JSONB lo mas probable es que se pueda representar como una tabla resultante

 CREATE VIEW vista_piezas_por_tipo AS
SELECT 
    id_orden_prod,
    calidad AS tipo_pieza,
    COUNT(*) AS cantidad
FROM creaciones_terminadas
GROUP BY id_orden_prod, calidad;


