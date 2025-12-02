
--- ====================================================
-- SISTEMA DE PRODUCCIÓN Y CONTROL DE INVENTARIO
-- JOYERÍA EN ORO - MODELO COMPLETO CON 3 ETAPAS
-- ====================================================



-- LIMPIEZA DE TABLAS
DROP TABLE IF EXISTS historial_produccion;
DROP TABLE IF EXISTS productos_terminados;
DROP TABLE IF EXISTS consumo_insumos;
DROP TABLE IF EXISTS orden_etapas;
DROP TABLE IF EXISTS etapas_produccion;
DROP TABLE IF EXISTS ordenes_produccion;
DROP TABLE IF EXISTS kardex;
DROP TABLE IF EXISTS inventario;
DROP TABLE IF EXISTS login;
DROP TABLE IF EXISTS log_eventos;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS usuario_rol;
DROP TABLE IF EXISTS modelos_joyeria;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS retrabajos;

-- ==========================
-- TABLA INVENTARIO
-- ==========================
CREATE TABLE inventario (
    codigo_item       DECIMAL(12)       PRIMARY KEY       NOT NULL,
    descripcion       TEXT                                NOT NULL,
    tipo_item         VARCHAR(20)                         NOT NULL    CHECK (tipo_item IN ('oro', 'insumo', 'producto')),
    quilataje         NUMERIC(4,2)      NOT NULL, 
    tipo_pieza        VARCHAR(50)       NOT NULL, 
    peso_neto         NUMERIC(10,2)     NOT NULL, 
    peso_bruto        NUMERIC(10,2)     NOT NULL, 
    unidad_medida     VARCHAR(10)       DEFAULT 'gr'   NOT NULL, 
    stock_actual      NUMERIC(10,2)     DEFAULT 0      NOT NULL,                
    stock_minimo      NUMERIC(10,2)     NOT NULL, 
    punto_reorden     NUMERIC(10,2)     NOT NULL, 
    valor_unitario    NUMERIC(12,2)     NOT NULL, 
    estado            BOOLEAN            DEFAULT TRUE       NOT NULL, 
    created_at        TIMESTAMP          DEFAULT NOW()      NOT NULL,
    updated_at        TIMESTAMP          DEFAULT NOW()      NOT NULL
	);

-- ==========================
-- TABLA KARDEX
-- ==========================
CREATE TABLE kardex (
    id_kardex         DECIMAL(12)      PRIMARY KEY     DEFAULT uuid_generate_v4(),
    codigo_item       DECIMAL(12)        NOT NULL,
    tipo_movimiento   VARCHAR(20)        NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida')),
    concepto          VARCHAR(100),
    cantidad          NUMERIC(10,2),
    fecha_movimiento  TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);


-- ==========================
-- ORDENES DE PRODUCCIÓN
-- ==========================
CREATE TABLE ordenes_produccion (
    id_orden           DECIMAL(12)       PRIMARY KEY,
    descripcion        TEXT,
    fecha_inicio       DATE              NOT NULL,
    fecha_fin_estimada DATE,
    estado             VARCHAR(20) CHECK (estado IN ('Pendiente', 'En Proceso', 'Finalizada')),
    created_at         TIMESTAMP DEFAULT NOW()
);

-- ==========================
-- ETAPAS DE PRODUCCIÓN (catálogo)
-- ==========================
CREATE TABLE etapas_produccion (
    id_etapa           DECIMAL(12)       PRIMARY KEY,
    nombre_etapa       VARCHAR(100)      NOT NULL,
    descripcion_etapa  TEXT 
);


-- ==========================
-- ETAPAS POR ORDEN
-- ==========================
CREATE TABLE orden_etapas (
    id_orden             DECIMAL(12)         NOT NULL,
    id_etapa             DECIMAL(12)         NOT NULL,
    fecha_inicio_etapa   DATE                NOT NULL,
    fecha_fin_etapa      DATE,
    estado_etapa         VARCHAR(20) CHECK (estado_etapa IN ('Pendiente', 'En Proceso', 'Completada')),
    PRIMARY KEY (id_orden, id_etapa),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (id_etapa) REFERENCES etapas_produccion(id_etapa)
);

-- ==========================
-- CONSUMO DE INSUMOS Y ORO
-- ==========================
CREATE TABLE consumo_insumos (
    id_consumo         DECIMAL(12)       PRIMARY KEY    NOT NULL,
    id_orden           DECIMAL(12)                      NOT NULL,
    codigo_item        DECIMAL(12)                      NOT NULL,
    cantidad_usada     NUMERIC(10,2)                    NOT NULL,
    fecha_consumo      TIMESTAMP DEFAULT NOW(),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- PRODUCTOS TERMINADOS
-- ==========================
CREATE TABLE productos_terminados (
    id_final                 DECIMAL(12)         PRIMARY KEY        NOT NULL,
    id_orden                 DECIMAL(12)                            NOT NULL,
    codigo_item              DECIMAL(12)                            NOT NULL,
    cantidad_producida   NUMERIC(10,2)                              NOT NULL,
    fecha_registro       TIMESTAMP DEFAULT NOW()                    NOT NULL,
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- HISTORIAL DE PRODUCCIÓN
-- ==========================
CREATE TABLE historial_produccion (
    id_historial       DECIMAL(12)              PRIMARY KEY,
    id_orden           DECIMAL(12)                                  NOT NULL,
    codigo_item        DECIMAL(12)                                  NOT NULL,
    etapa              VARCHAR(100)                                 NOT NULL,
    responsable        VARCHAR(100)                                 NOT NULL,
    cantidad           NUMERIC(10,2)                                NOT NULL,
    peso_oro_usado     NUMERIC(10,2)                                NOT NULL,
    merma              NUMERIC(10,2)                                NOT NULL,
    observaciones      VARCHAR(50),
    fecha_registro     TIMESTAMP DEFAULT NOW()                      NOT NULL,
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);


-- ==========================
-- LOGIN DE INICIO
-- ==========================

CREATE TABLE login (
    id_user         DECIMAL(12)     PRIMARY KEY     NOT NULL,
    username        VARCHAR(50)     UNIQUE          NOT NULL,
    contrasena      VARCHAR(64)                     NOT NULL    CHECK (
        LENGTH(contrasena) >= 8 AND
        contrasena ~ '[A-Z]' AND
        contrasena ~ '[a-z]' AND
        contrasena ~ '[0-9]' AND
        contrasena ~ '[^A-Za-z0-9]'
    ),
    email           VARCHAR(100)    UNIQUE          NOT NULL,
    fecha_creacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP     NOT NULL,
    estado          BOOLEAN         NOT NULL
);
-- ==========================
--  LOGIN DE EVENTOS
-- ==========================

CREATE TABLE log_eventos (
    id_evento SERIAL PRIMARY KEY                     NOT NULL,
    usuario VARCHAR(50)                              NOT NULL,
    accion TEXT                                      NOT NULL,
    tabla_afectada VARCHAR(50)                       NOT NULL,
    registro_afectado VARCHAR(100)                   NOT NULL,
    fecha TIMESTAMP DEFAULT NOW()                    NOT NULL
);

-- ==========================
-- ASIGNA ROLES DE USUARIOS
-- ==========================

CREATE TABLE roles (
    id_rol DECIMAL(12)                  PRIMARY KEY           NOT NULL,
    nombre_rol VARCHAR(50) UNIQUE                             NOT NULL
);

-- ==========================
-- ROL DEL USUARIO
-- ==========================

CREATE TABLE usuario_rol (
    id_user      DECIMAL(12)                 NOT NULL,
    id_rol       DECIMAL(12)                 NOT NULL,
    PRIMARY KEY (id_user, id_rol),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ==========================
-- REGISTRAR MODELOS DE JOYERIA
-- ==========================

CREATE TABLE modelos_joyeria (
    id_modelo       SERIAL    PRIMARY KEY    NOT NULL,
    nombre_modelo       VARCHAR(100)         NOT NULL,
    descripcion TEXT,
    peso_estimado NUMERIC(10,2)              NOT NULL,
    precio_estimado NUMERIC(12,2)            NOT NULL
);
	

-- ==========================
-- REGISTRAR LOS CLIENTES
-- ==========================

CREATE TABLE clientes (
    id_cliente                 DECIMAL(12)      PRIMARY KEY           NOT NULL,
    nombre_cliente             VARCHAR(50)                            NOT NULL,
    contacto_cliente           DECIMAL(20)                            NOT NULL,
    email_cliente              VARCHAR(50)                            NOT NULL,
    telefono_cliente           VARCHAR(20)                            NOT NULL
);

-- ==========================
-- REGISTRAR RETRABAJOS Y DE CORRECCIONES
-- ==========================

CREATE TABLE retrabajos (
    id_retrabajo     DECIMAL(12)        PRIMARY KEY                                     NOT NULL,
    id_final         DECIMAL(12)                                                        NOT NULL,
    motivo           VARCHAR(50)                                                        NOT NULL,
    fecha            TIMESTAMP          DEFAULT NOW()                                   NOT NULL,
    estado VARCHAR(10) CHECK (estado IN ('Pendiente', 'En proceso', 'Finalizado'))      NOT NULL,
    FOREIGN KEY (id_final) REFERENCES productos_terminados(id_final)
);

-- Insertar etapas fijas
INSERT INTO etapas_produccion (nombre_etapa, descripcion_etapa) VALUES
('Alistamiento', 'Preparación de materiales y diseño de pieza'),
('Fundición', 'Fusión del oro e inicio de manufactura'),
('Preparación del producto y entrega', 'Pulido, inspección y embalaje final');


ALTER TABLE ordenes_produccion ADD COLUMN id_modelo INT;
ALTER TABLE ordenes_produccion ADD FOREIGN KEY (id_modelo) REFERENCES modelos_joyeria(id_modelo);


CREATE OR REPLACE FUNCTION actualizar_updated_at()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_inventario
BEFORE UPDATE ON inventario
FOR EACH ROW
EXECUTE FUNCTION actualizar_updated_at();


