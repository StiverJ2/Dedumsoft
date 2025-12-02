-- ====================================================
-- SISTEMA DE PRODUCCIÓN Y CONTROL DE INVENTARIO
-- JOYERÍA EN ORO - MODELO COMPLETO CON 3 ETAPAS
-- ====================================================


-- ====================================================
-- LIMPIEZA DE TABLAS
-- ====================================================
DROP TABLE IF EXISTS retrabajos;               -- depende de productos_terminados
DROP TABLE IF EXISTS sincronizacion_pendiente;  -- Sincronizaciones
DROP TABLE IF EXISTS historial_produccion;     -- depende de ordenes_produccion, modelos_joyeria, inventario, login, roles
DROP TABLE IF EXISTS productos_terminados;     -- depende de ordenes_produccion, modelos_joyeria, inventario
DROP TABLE IF EXISTS consumo_insumos;          -- depende de ordenes_produccion, inventario, login, roles
DROP TABLE IF EXISTS orden_etapas;             -- depende de ordenes_produccion, etapas_produccion, login, roles
DROP TABLE IF EXISTS etapas_produccion;        -- depende de login, roles
DROP TABLE IF EXISTS ordenes_produccion;       -- depende de modelos_joyeria, login, roles
DROP TABLE IF EXISTS notificaciones_sistema;           -- depende de inventario, ordenes_produccion, login (opcional)
DROP TABLE IF EXISTS facturas_compras;         -- depende de proveedores, inventario, login
DROP TABLE IF EXISTS modelos_joyeria;          -- usada por ordenes_produccion y otras
DROP TABLE IF EXISTS usuario_rol;              -- depende de login, roles
DROP TABLE IF EXISTS log_eventos;              -- depende de login, roles
DROP TABLE IF EXISTS kardex;                   -- depende de inventario
DROP TABLE IF EXISTS clientes;                 -- independiente
DROP TABLE IF EXISTS proveedores;              -- padre de facturas_compras
DROP TABLE IF EXISTS inventario;               -- base usada por muchas
DROP TABLE IF EXISTS roles;                    -- usada por muchas
DROP TABLE IF EXISTS login;
-- ==========================
-- TABLA INVENTARIO
-- ==========================
CREATE TABLE inventario (
    codigo_item                   DECIMAL(12)           PRIMARY KEY                 NOT NULL,               -- Identificador único del ítem
    descripcion_inventario        TEXT --falta limitarlo                                             NOT NULL,               -- Descripción detallada del ítem
    tipo_item_inventario          VARCHAR(20)                                       NOT NULL    CHECK (tipo_item_inventario IN ('oro', 'insumo', 'producto')), -- Clasificación del ítem
    quilataje_inventario          NUMERIC(4,2)                                      NOT NULL,               -- Pureza del oro (si aplica)
    tipo_pieza_inventario         VARCHAR(50)                                       NOT NULL,               -- Tipo de pieza o categoría del producto
    peso_neto_inventario          NUMERIC(10,2)                                     NOT NULL,               -- Peso sin incluir merma
    peso_bruto_inventario         NUMERIC(10,2)                                     NOT NULL,               -- Peso total con merma
    unidad_medida_inventario      VARCHAR(10)           DEFAULT 'gr'                NOT NULL,               -- Unidad estándar de medida
    stock_actual_inventario       NUMERIC(10,2)         DEFAULT 0                   NOT NULL,               -- Cantidad en stock
    stock_minimo_inventario       NUMERIC(10,2)                                     NOT NULL,               -- Nivel mínimo de inventario permitido
    stock_max_inventario          NUMERIC(10,2)                                     NOT NULL,               -- Nivel maximo de inventario permitido
    punto_reorden_inventario      NUMERIC(10,2)                                     NOT NULL,               -- Punto de reorden para abastecimiento
    valor_unitario_inventario     NUMERIC(12,2)                                     NOT NULL,               -- Costo individual del ítem
    estado_inventario             BOOLEAN               DEFAULT TRUE                NOT NULL,               -- Activo o no
    created_at_inventario         TIMESTAMP             DEFAULT NOW() NOT NULL,                             -- Fecha de creación
    updated_at_inventario         TIMESTAMP             DEFAULT NOW() NOT NULL                              -- Fecha de última actualización
);
-- ==========================
-- TABLA KARDEX
-- ==========================
CREATE SEQUENCE IF NOT EXISTS kardex_seq
    START WITH 1000
    INCREMENT BY 1
    MINVALUE 1000;


CREATE TABLE kardex (
    --id_kardex                     DECIMAL(12)           PRIMARY KEY                DEFAULT NEXTVAL('kardex_seq'),
    id_kardex                     DECIMAL(12)          PRIMARY KEY                 NOT NULL,                    -- Identificador del movimientO
    codigo_item                   DECIMAL(12)                                      NOT NULL,                    -- Ítem relacionado al movimiento
    tipo_movimiento_kardex        VARCHAR(20)                                      NOT NULL CHECK (tipo_movimiento_kardex IN ('entrada', 'salida')),    -- Entrada o salida
    concepto_kardex               VARCHAR(100)                                     NOT NULL,                    -- Descripción del movimiento
    cantidad_kardex               NUMERIC(10,2)                                    NOT NULL ,                     -- Cantidad involucrada
    fecha_movimiento_kardex       TIMESTAMP DEFAULT NOW(), -- Fecha del movimiento
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- LOGIN DE INICIO
-- ==========================

CREATE TABLE login (
    id_user              DECIMAL(12)     PRIMARY KEY     NOT NULL,              -- ID del usuario
    username_user        VARCHAR(50)     UNIQUE          NOT NULL,              -- Nombre de usuario
    contrasena_user      VARCHAR(64)                     NOT NULL    CHECK (    -- Contraseña segura con requisitos mínimos
        LENGTH(contrasena_user) >= 8 AND
        contrasena_user ~ '[A-Z]' AND
        contrasena_user ~ '[a-z]' AND
        contrasena_user ~ '[0-9]' AND
        contrasena_user ~ '[^A-Za-z0-9]'
    ),
    email_user           VARCHAR(100)    UNIQUE          NOT NULL,                     -- Correo del usuario
    fecha_creacion       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP     NOT NULL,       -- Fecha de creación
    estado_user          BOOLEAN                        NOT NULL                      -- Activo o inactivo
);

-- ==========================
-- ASIGNA ROLES DE USUARIOS
-- ==========================

CREATE TABLE roles (
    id_rol DECIMAL(12)                  PRIMARY KEY           NOT NULL,     -- ID del rol
    nombre_rol VARCHAR(50)                                    NOT NULL      -- Nombre único del rol
);

-- ==========================
-- ROL DEL USUARIO
-- ==========================

CREATE TABLE usuario_rol (
    id_user      DECIMAL(12)                 NOT NULL,                       -- ID del usuario
    id_rol       DECIMAL(12)                 NOT NULL,                       -- ID del rol asignado
    PRIMARY KEY (id_user, id_rol),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ==========================
-- NOTIFICAIONES Y ALERTAS
-- ==========================

CREATE TABLE notificaciones_sistema (
    id_notificacion        DECIMAL(12)          PRIMARY KEY,                                 -- ID único
    tipo_notificacion      VARCHAR(30)                                      NOT NULL CHECK (
        tipo_notificacion IN ('Stock Bajo', 'Retrabajo', 'Orden Vencida', 'Evento Sistema', 'Nuevo Ingreso', 'Otro')
    ),
    mensaje_notificacion                VARCHAR(100)                                     NOT NULL,    -- Mensaje para el usuario
    nivel_prioridad        VARCHAR(5)                                       NOT NULL CHECK (
        nivel_prioridad IN ('Alta', 'Media', 'Baja')
    ),
    leida                  BOOLEAN          DEFAULT FALSE,                               -- Estado de lectura
    fecha_creacion         TIMESTAMP        DEFAULT CURRENT_TIMESTAMP       NOT NULL,    -- Fecha de creación
    id_user                DECIMAL(12)                                      NOT NULL,    -- Usuario relacionado
    id_rol                 DECIMAL(12)                                      NOT NULL,    -- Rol relacionado
    tabla_referida         VARCHAR(50),                                                  -- NOMBRE DE REFERENCIA DE TABLAS AFECTADAS
    id_referencia          DECIMAL(12),                                                  -- ID DE REFERENCIA DE TABLAS AFECTADAS
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ==========================
-- CONTROL DE SINCRONIZACIÓN OFFLINE
-- ==========================
CREATE TABLE sincronizacion_pendiente (
    id_sync         DECIMAL(12)          PRIMARY KEY     NOT NULL,
    tabla_afectada  VARCHAR(50)                          NOT NULL,
    tipo_operacion  VARCHAR(10)          CHECK (tipo_operacion IN ('INSERT', 'UPDATE', 'DELETE')) NOT NULL,
    id_registro     VARCHAR(50)                          NOT NULL,
    fecha_operacion                      TIMESTAMP      DEFAULT NOW(),
    sincronizado    BOOLEAN DEFAULT FALSE
);

-- ==========================
-- REGISTRAR MODELOS DE JOYERIA
-- ==========================

CREATE TABLE modelos_joyeria (
    id_modelo               DECIMAL(12)         PRIMARY KEY       NOT NULL,       -- ID del modelo
    nombre_modelo           VARCHAR(50)                           NOT NULL,       -- Nombre del diseño
    descripcion_modelo      VARCHAR(100)                         NOT NULL,       -- Detalles del diseño
    peso_estimado_modelo    NUMERIC(10,2)                         NOT NULL,       -- Peso aproximado
    precio_estimado_modelo  NUMERIC(12,2)                         NOT NULL        -- Precio sugerido
);
	
-- ==========================
-- PROVEEDORES MATERIA PRIMA
-- ==========================

CREATE TABLE proveedores (
    id_proveedor         DECIMAL(12)             PRIMARY KEY         NOT NULL,
    nombre_proveedor     VARCHAR(100)                                NOT NULL,
    contacto_proveedor   VARCHAR(100),
    email_proveedor      VARCHAR(100),
    telefono_proveedor   VARCHAR(20),
    direccion_proveedor  TEXT
);

-- ==========================
-- FACTURA PARA LOS PROVEEDORES DE MATERIA PRIMA
-- ==========================


CREATE TABLE facturas_compras (
    id_factura           DECIMAL(12)     PRIMARY KEY     NOT NULL,             -- ID único de la factura
    id_proveedor         DECIMAL(12)                     NOT NULL,             -- Proveedor relacionado
    codigo_item          DECIMAL(12)                     NOT NULL,             -- Ítem comprado (oro, insumo, etc.)
    cantidad_comprada    NUMERIC(10,2)                   NOT NULL,             -- Cantidad adquirida
    precio_unitario      NUMERIC(12,2)                   NOT NULL,             -- Precio por unidad
    total_factura        NUMERIC(14,2)                   GENERATED ALWAYS AS (cantidad_comprada * precio_unitario) STORED,
    fecha_compra         TIMESTAMP       DEFAULT NOW()   NOT NULL,             -- Fecha de la compra
    id_user              DECIMAL(12)                     NOT NULL,             -- Usuario que registró la factura
    id_rol               DECIMAL(12)                     NOT NULL,             -- Rol del usuario
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);


-- ==========================
-- ORDENES DE PRODUCCIÓN
-- ==========================
CREATE TABLE ordenes_produccion (
    id_orden                               DECIMAL(12)          PRIMARY KEY      NOT NULL,            -- ID de la orden
    descripcion_ordenes_produccion         TEXT                                  NOT NULL,            -- Descripción de la orden
    fecha_inicio_ordenes_produccion        DATE                                  NOT NULL,            -- Inicio programado
    fecha_fin_estimada_ordenes_produccion  DATE,                                      	             -- Fecha estimada de terminación
    estado_ordenes_produccion              VARCHAR(20)          CHECK (estado_ordenes_produccion      IN ('Pendiente', 'En Proceso', 'Finalizada'))     NOT NULL, -- Estado actual
    created_at_ordenes_produccion          TIMESTAMP 			 DEFAULT NOW(),                       -- Fecha de creación
    id_user                                DECIMAL(12)                           NOT NULL,           -- ID del usuario
    id_rol                                 DECIMAL(12)                           NOT NULL,           -- ID del rol
    nombre_rol                             VARCHAR(50)                           NOT NULL,           -- Nombre único del rol
    id_modelo                              DECIMAL(12)                           NOT NULL,           -- ID del modelo
    FOREIGN KEY (id_modelo) REFERENCES modelos_joyeria(id_modelo),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)

);

-- ==========================
-- ETAPAS DE PRODUCCIÓN (catálogo)
-- ==========================
CREATE TABLE etapas_produccion (
    id_etapa           DECIMAL(12)       PRIMARY KEY,       -- ID de la etapa
    nombre_etapa       VARCHAR(100)      NOT NULL,          -- Nombre de la etapa
    descripcion_etapa  TEXT,                                -- Detalle adicional de la etapa
    id_user                                DECIMAL(12)                           NOT NULL,              -- ID del usuario
    id_rol                                 DECIMAL(12)                           NOT NULL,              -- ID del rol
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);


-- ==========================
-- ETAPAS POR ORDEN
-- ==========================
CREATE TABLE orden_etapas (
    id_orden             DECIMAL(12)         NOT NULL,      -- Orden relacionada
    id_etapa             DECIMAL(12)         NOT NULL,      -- Etapa asignada
    fecha_inicio_etapa   DATE                NOT NULL,      -- Inicio real de la etapa
    fecha_fin_etapa      DATE,                              -- Fin real de la etapa
    estado_etapa         VARCHAR(20) CHECK (estado_etapa IN ('Pendiente', 'En Proceso', 'Completada')), -- Estado actual
    id_user                                DECIMAL(12)                           NOT NULL,              -- ID del usuario
    id_rol                                 DECIMAL(12)                           NOT NULL,              -- ID del rol
    nombre_rol                             VARCHAR(50)                           NOT NULL,              -- Nombre único del rol
    id_modelo               DECIMAL(12)                           NOT NULL,       -- ID del modelo
    PRIMARY KEY (id_orden, id_etapa),
    FOREIGN KEY (id_modelo) REFERENCES modelos_joyeria(id_modelo),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (id_etapa) REFERENCES etapas_produccion(id_etapa)
);

-- ==========================
-- CONSUMO DE INSUMOS Y ORO
-- ==========================
CREATE TABLE consumo_insumos (
    id_consumo         DECIMAL(12)       PRIMARY KEY    NOT NULL,              -- Identificador del consumo
    id_orden           DECIMAL(12)                      NOT NULL,              -- Orden relacionada
    codigo_item        DECIMAL(12)                      NOT NULL,              -- Ítem consumido
    cantidad_usada     NUMERIC(10,2)                    NOT NULL,              -- Cantidad usada en la producción
    fecha_consumo      TIMESTAMP DEFAULT NOW()          NOT NULL,              -- Fecha de consumo en la producción
    id_user            DECIMAL(12)                      NOT NULL,              -- ID del usuario
    id_rol             DECIMAL(12)                      NOT NULL,              -- ID del rol

    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- PRODUCTOS TERMINADOS
-- ==========================
CREATE TABLE productos_terminados (
    id_final                 DECIMAL(12)         PRIMARY KEY        NOT NULL,              -- ID del producto final
    id_orden                 DECIMAL(12)                            NOT NULL,              -- Orden de la cual proviene
    codigo_item              DECIMAL(12)                            NOT NULL,              -- Ítem registrado como producto terminado
    cantidad_producida       NUMERIC(10,2)                          NOT NULL,              -- Cantidad producida
    fecha_registro_terminado TIMESTAMP DEFAULT NOW()                NOT NULL,              -- Fecha del registro
    id_user                  DECIMAL(12)                            NOT NULL,              -- ID del usuario
    id_rol                   DECIMAL(12)                            NOT NULL,              -- ID del rol
    id_modelo                DECIMAL(12)                            NOT NULL,       -- ID del modelo
    FOREIGN KEY (id_modelo) REFERENCES modelos_joyeria(id_modelo),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- HISTORIAL DE PRODUCCIÓN
-- ==========================
CREATE TABLE historial_produccion (
    id_historial       DECIMAL(12)              PRIMARY KEY,                        -- ID del registro
    id_orden           DECIMAL(12)                                  NOT NULL,       -- Orden asociada
    codigo_item        DECIMAL(12)                                  NOT NULL,       -- Ítem relacionado
    etapa              VARCHAR(100)                                 NOT NULL,       -- Nombre de la etapa
    cantidad_producto  NUMERIC(10,2)                                NOT NULL,       -- Cantidad trabajada
    peso_oro_usado     NUMERIC(10,2)                                NOT NULL,       -- Peso de oro empleado
    merma              NUMERIC(10,2)                                NOT NULL,       -- Merma generada
    observaciones      VARCHAR(50),                                                 -- Observaciones adicionales           
    fecha_registro     TIMESTAMP DEFAULT NOW()                      NOT NULL,       -- Fecha del evento
    id_user            DECIMAL(12)                                  NOT NULL,       -- ID del usuario
    id_rol             DECIMAL(12)                                  NOT NULL,       -- ID del rol
    id_modelo          DECIMAL(12)                                  NOT NULL,       -- ID del modelo
    FOREIGN KEY (id_modelo) REFERENCES modelos_joyeria(id_modelo),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);


-- ==========================
--  LOGIN DE EVENTOS
-- ==========================

CREATE TABLE log_eventos (
    id_evento         DECIMAL(12)        PRIMARY KEY                     NOT NULL,        -- ID del evento
    id_user           DECIMAL(12)                                        NOT NULL,        -- ID del usuario
    id_rol            DECIMAL(12)                                        NOT NULL,        -- ID del rol asignado
    descripcion_rol   VARCHAR(100)                                       NOT NULL,        -- Acción realizada
    tabla_afectada    VARCHAR(50)                                        NOT NULL,        -- Tabla afectada
    registro_afectado VARCHAR(100)                                       NOT NULL,        -- Registro específico
    fecha_evento      TIMESTAMP DEFAULT NOW()                            NOT NULL,        -- Fecha del evento
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);



-- ==========================
-- REGISTRAR LOS CLIENTES
-- ==========================

CREATE TABLE clientes (
    id_cliente                 DECIMAL(12)      PRIMARY KEY           NOT NULL,         -- ID del cliente
    nombre_cliente             VARCHAR(50)                            NOT NULL,         -- Nombre del cliente
    contacto_cliente           DECIMAL(20)                            NOT NULL,         -- Número de contacto
    email_cliente              VARCHAR(50)                            NOT NULL,         -- Correo electrónico
    telefono_cliente           VARCHAR(20)                            NOT NULL          -- Teléfono del cliente
);

-- ==========================
-- REGISTRAR RETRABAJOS Y DE CORRECCIONES
-- ==========================

CREATE TABLE retrabajos (
    id_retrabajo     DECIMAL(12)        PRIMARY KEY                                     NOT NULL,   -- ID del retrabajo
    id_final         DECIMAL(12)                                                        NOT NULL,   -- ID del producto final asociado
    motivo_retrabajo            VARCHAR(50)                                             NOT NULL,   -- Razón del retrabajo
    fecha_retrabajo            TIMESTAMP          DEFAULT NOW()                         NOT NULL,   -- Fecha de registro
    estado VARCHAR(10) CHECK (estado IN ('Pendiente', 'En proceso', 'Finalizado'))      NOT NULL,   -- Estado del retrabajo
    FOREIGN KEY (id_final) REFERENCES productos_terminados(id_final)
);


-- ==========================
-- INDEX INVENTARIO
-- ==========================

CREATE INDEX idx_inventario_tipo ON inventario(tipo_item_inventario);
CREATE INDEX idx_inventario_codigo ON inventario(codigo_item);
CREATE INDEX idx_inventario_stock ON inventario(stock_actual_inventario);

-- ==========================
-- INDEX ORDENES_PRODUCCION
-- ==========================

CREATE INDEX idx_orden_estado ON ordenes_produccion(estado_ordenes_produccion);
CREATE INDEX idx_orden_modelo ON ordenes_produccion(id_modelo);
CREATE INDEX idx_orden_usuario ON ordenes_produccion(id_user);


-- ==========================
-- INDEX ETAPAS DE PRODUCCION
-- ==========================

CREATE INDEX idx_etapa_nombre ON etapas_produccion(nombre_etapa);

-- ==========================
-- INDEX ORDEN DE ETAPAS
-- ==========================

CREATE INDEX idx_orden_etapa_estado ON orden_etapas(estado_etapa);
CREATE INDEX idx_orden_etapa_orden ON orden_etapas(id_orden);

-- ==========================
-- INDEX PRODUCTOS TERMINADOS
-- ==========================

CREATE INDEX idx_productos_orden ON productos_terminados(id_orden);
CREATE INDEX idx_productos_modelo ON productos_terminados(id_modelo);

-- ==========================
-- INDEX HISTORIAL DE PRODUCTOS
-- ==========================

CREATE INDEX idx_historial_orden ON historial_produccion(id_orden);
CREATE INDEX idx_historial_codigo_item ON historial_produccion(codigo_item);

-- ==========================
-- INDEX DE CONSUMO DE INSUMOS
-- ==========================

CREATE INDEX idx_consumo_orden ON consumo_insumos(id_orden);
CREATE INDEX idx_consumo_codigo_item ON consumo_insumos(codigo_item);

-- ==========================
-- INDEX DE login, roles y usuario_rol
-- ==========================

CREATE INDEX idx_login_username ON login(username_user);
CREATE INDEX idx_usuario_rol_user ON usuario_rol(id_user);
CREATE INDEX idx_usuario_rol_rol ON usuario_rol(id_rol);

-- ==========================
-- INDEX DE LOG EVENTOS
-- ==========================

CREATE INDEX idx_log_tabla_fecha ON log_eventos(tabla_afectada, fecha_evento);

-- ==========================
-- INDEX DE NOTIFICACIONES
-- ==========================

CREATE INDEX idx_notificaciones_estado ON notificaciones_sistema(leido, prioridad);

-- ==========================
-- INDEX SINCRONZACION DE PENDIENTES
-- ==========================

CREATE INDEX idx_sync_sincronizado ON sincronizacion_pendiente(sincronizado);



--INSERT INTO kardex (codigo_item, tipo_movimiento_kardex, concepto_kardex, cantidad_kardex)
--VALUES (1001, 'entrada', 'Prueba inicial', 5.5);



-- ==========================
-- INSERT INVENTARIO
-- ==========================


INSERT INTO inventario (
    codigo_item,
    descripcion_inventario,
    tipo_item_inventario,
    quilataje_inventario,
    tipo_pieza_inventario,
    peso_neto_inventario,
    peso_bruto_inventario,
    unidad_medida_inventario,
    stock_actual_inventario,
    stock_minimo_inventario,
    stock_max_inventario,
    punto_reorden_inventario,
    valor_unitario_inventario,
    estado_inventario
) VALUES
-- Producto terminado: Anillo Clásico
(1003, 'Anillo Clásico Mujer', 'producto', 18.00, 'Anillo', 5.00, 5.30, 'gr', 0, 2, 20, 3, 850000.00, TRUE),

-- Producto terminado: Cadena Elegante
(1004, 'Cadena Elegante Hombre', 'producto', 18.00, 'Cadena', 10.00, 10.40, 'gr', 0, 2, 15, 3, 970000.00, TRUE),

-- Producto terminado: Pulsera Moderna
(1005, 'Pulsera Moderna Juvenil', 'producto', 18.00, 'Pulsera', 8.00, 8.30, 'gr', 0, 2, 15, 3, 1250000.00, TRUE);


INSERT INTO inventario (
    codigo_item, descripcion_inventario, tipo_item_inventario, quilataje_inventario,
    tipo_pieza_inventario, peso_neto_inventario, peso_bruto_inventario,
    unidad_medida_inventario, stock_actual_inventario, stock_minimo_inventario,
    stock_max_inventario, punto_reorden_inventario, valor_unitario_inventario,
    estado_inventario
) VALUES
-- ORO
(1001, 'Oro puro 24k en lingote', 'oro', 24.00, 'Lingote', 1000.00, 1005.00, 'gr', 1500.00, 500.00, 2000.00, 600.00, 285000.00, TRUE),
(1002, 'Oro 18k reciclado', 'oro', 18.00, 'Material reciclado', 500.00, 510.00, 'gr', 400.00, 100.00, 1000.00, 200.00, 180000.00, TRUE),

-- INSUMOS
(2001, 'Ácido nítrico para limpieza', 'insumo', 0.00, 'Químico', 5.00, 5.10, 'lt', 10.00, 2.00, 20.00, 5.00, 120000.00, TRUE),
(2002, 'Guantes de protección', 'insumo', 0.00, 'Equipamiento', 0.10, 0.11, 'par', 30.00, 10.00, 100.00, 15.00, 2500.00, TRUE),
(2003, 'Cepillo de pulido', 'insumo', 0.00, 'Herramienta', 0.20, 0.22, 'ud', 8.00, 5.00, 50.00, 6.00, 9000.00, TRUE),

-- PRODUCTOS TERMINADOS
(3001, 'Anillo oro blanco 18k', 'producto', 18.00, 'Anillo', 15.00, 15.20, 'gr', 12.00, 5.00, 30.00, 8.00, 950000.00, TRUE),
(3002, 'Cadena oro amarillo 14k', 'producto', 14.00, 'Cadena', 25.00, 25.40, 'gr', 7.00, 3.00, 20.00, 5.00, 1400000.00, TRUE),
(3003, 'Aretes oro rosa 18k', 'producto', 18.00, 'Aretes', 10.00, 10.10, 'gr', 20.00, 10.00, 50.00, 12.00, 620000.00, TRUE),
(3004, 'Pulsera trenzada oro 18k', 'producto', 18.00, 'Pulsera', 30.00, 30.50, 'gr', 5.00, 3.00, 15.00, 4.00, 1800000.00, TRUE);


-- ==========================
-- INSERT KARDEX
-- ==========================

INSERT INTO kardex (
    id_kardex, codigo_item, tipo_movimiento_kardex, concepto_kardex, cantidad_kardex, fecha_movimiento_kardex
) VALUES
-- ENTRADAS
(1, 1001, 'entrada', 'Compra de oro puro 24k', 500.00, '2025-07-01 08:00:00'),
(2, 2001, 'entrada', 'Reposición de ácido nítrico', 10.00, '2025-07-03 09:00:00'),
(3, 3001, 'entrada', 'Registro inicial de producto terminado: Anillo oro blanco', 5.00, '2025-07-05 14:30:00'),

-- SALIDAS
(4, 1001, 'salida', 'Uso de oro 24k para producción de anillos', 200.00, '2025-07-06 10:00:00'),
(5, 2002, 'salida', 'Entrega de guantes al operario', 10.00, '2025-07-06 11:00:00'),
(6, 3002, 'salida', 'Venta de cadenas oro amarillo 14k a cliente', 2.00, '2025-07-07 16:00:00'),
(7, 3003, 'salida', 'Salida por devolución de aretes oro rosa', 1.00, '2025-07-08 13:15:00'),
(8, 1002, 'salida', 'Uso de oro reciclado para nueva fundición', 150.00, '2025-07-09 10:45:00'),

-- ENTRADA POST-PRODUCCIÓN
(9, 3004, 'entrada', 'Producto terminado ingresado: Pulsera trenzada oro', 3.00, '2025-07-10 17:00:00'),

-- MOVIMIENTO INTERNO
(10, 2003, 'salida', 'Cepillo entregado al área de pulido', 3.00, '2025-07-10 09:00:00');

-- ==========================
-- INSERT DE LOGIN
-- ==========================

INSERT INTO login (
    id_user, username_user, contrasena_user, email_user, estado_user
) VALUES
-- ADMINISTRADOR DEL SISTEMA
(1, 'admin_master', 'Admin$2025!', 'admin@sistema.com', TRUE),

-- GERENTE
(2, 'gerente_joya', 'Gerente#2025', 'gerencia@joyeria.com', TRUE),

-- ENCARGADO DEL INVENTARIO
(3, 'inventario_jefe', 'Invent@2025', 'inventario@joyeria.com', TRUE),

-- OPERARIO
(4, 'operario1', 'Operario*123', 'operario1@joyeria.com', TRUE),

-- CLIENTE
(5, 'cliente_oro', 'Cliente!123', 'cliente@gmail.com', TRUE),

-- INVITADO
(6, 'visitante_demo', 'Invitado#2025', 'invitado@demo.com', TRUE);

-- ==========================
-- INSERT ROLES
-- ==========================

INSERT INTO roles (id_rol, nombre_rol) VALUES
(1, 'ADMINISTRADOR DEL SISTEMA'),
(2, 'GERENTE'),
(3, 'ENCARGADO DEL INVENTARIO'),
(4, 'OPERARIO'),
(5, 'CLIENTE'),
(6, 'INVITADO');

-- ==========================
-- INSERT ROL DE USUARIOS
-- ==========================

INSERT INTO usuario_rol (id_user, id_rol) VALUES
(1, 1),  -- admin_master       → ADMINISTRADOR DEL SISTEMA
(2, 2),  -- gerente_joya       → GERENTE
(3, 3),  -- inventario_jefe    → ENCARGADO DEL INVENTARIO
(4, 4),  -- operario1          → OPERARIO
(5, 5),  -- cliente_oro        → CLIENTE
(6, 6);  -- visitante_demo     → INVITADO

-- ==========================
-- INSERT MODELOS DE JOYERIA
-- ==========================

INSERT INTO modelos_joyeria (id_modelo, nombre_modelo, descripcion_modelo, peso_estimado_modelo, precio_estimado_modelo) VALUES
(1, 'Anillo Clásico Oro 18k',      'Anillo sencillo de oro 18 quilates con acabado brillante',              5.50, 850000.00),
(2, 'Collar Perla Elegancia',      'Collar con cadena de oro y colgante de perla natural',                  12.80, 1650000.00),
(3, 'Aretes Estrella Zirconia',    'Aretes pequeños con zirconias en forma de estrella',                    2.10, 320000.00),
(4, 'Pulsera Doble Eslabón',       'Pulsera de eslabones dobles en oro blanco',                             8.00, 1250000.00),
(5, 'Anillo Compromiso Brillante', 'Anillo de compromiso con piedra central y detalles en oro rosa',        4.30, 1850000.00),
(6, 'Cadena Estilo Clásico',       'Cadena sencilla para uso diario de oro amarillo',                       10.00, 970000.00),
(7, 'Dije Cruz Minimalista',       'Dije pequeño en forma de cruz con terminación mate',                    1.75, 270000.00),
(8, 'Tobillera Luna',              'Tobillera de oro con colgantes en forma de luna y estrella',            3.80, 450000.00),
(9, 'Broche Flor Vintage',         'Broche con diseño floral de estilo antiguo con incrustaciones',         6.20, 690000.00),
(10,'Anillo Personalizado Inicial','Anillo ajustable con inicial grabada a elección del cliente',           3.50, 510000.00);

INSERT INTO modelos_joyeria (
    id_modelo, nombre_modelo, descripcion_modelo, peso_estimado_modelo, precio_estimado_modelo
) VALUES
(101, 'Anillo Clásico', 'Anillo tradicional de compromiso en oro de 18k', 5.2, 320000.00),
(102, 'Cadena Fina', 'Cadena delgada de oro blanco', 7.8, 540000.00),
(103, 'Pulsera Moderna', 'Pulsera con diseño contemporáneo en oro rosa', 10.0, 750000.00);

-- ==========================
-- INSERT DE PROVEEDORES
-- ==========================

INSERT INTO proveedores (id_proveedor, nombre_proveedor, contacto_proveedor, email_proveedor, telefono_proveedor, direccion_proveedor) VALUES
(101, 'OroNorte S.A.S.',             'Carlos Duarte',    'ventas@oronorte.com',      '3104567890', 'Calle 45 #12-30, Medellín, Antioquia'),
(102, 'Insumos y Químicos Ltda',     'Sandra Pinzón',    's.pinzon@iqcolombia.co',   '3147893210', 'Carrera 8 #22-15, Bogotá D.C.'),
(103, 'Empaques Finos Colombia',     'Luis Meza',        'contacto@empaquesf.com',   '3123456789', 'Av. Las Palmas #33-22, Cali'),
(104, 'Joyería Global Export',       'Ana Restrepo',     'export@joyeriaglobal.com', '3158765432', 'Cra 11 #45-67, Bucaramanga'),
(105, 'Metales Preciosos Andes',     'Diego Trujillo',   'dtrujillo@mpandes.co',     '3019876543', 'Calle 10 #50-21, Medellín'),
(106, 'Distribuciones Dorado',       'María Ruiz',       'mruiz@dorado.com',         '3176543210', 'Carrera 7 #70-20, Bogotá D.C.'),
(107, 'Tecnopiezas S.A.',            'Raúl Gómez',       'rgomez@tecnopiezas.com',   '3001122334', 'Zona Industrial #5, Barranquilla'),
(108, 'Ornamentales Elite',          'Patricia Pardo',   'ppardo@elitejoyas.com',    '3182233445', 'Av. Circunvalar #14-70, Cartagena');

-- ==========================
-- INSERT COMPRAS A PROVEEDORES
-- ==========================

INSERT INTO facturas_compras (id_factura, id_proveedor, codigo_item, cantidad_comprada, precio_unitario, fecha_compra, id_user, id_rol) VALUES
(5001, 101, 1001, 250.00, 240000.00, '2025-07-01 09:00:00', 2, 3),  -- Oro
(5002, 102, 2001, 5.00, 18000.00,  '2025-07-02 10:30:00', 2, 3),    -- Ácido Nítrico
(5003, 103, 2002, 10.00, 4500.00,  '2025-07-02 11:00:00', 2, 3),    -- Empaques
(5004, 104, 1002, 300.00, 250000.00, '2025-07-03 08:15:00', 1, 1),  -- Oro refinado
(5005, 105, 2003, 2.50, 30000.00,  '2025-07-03 14:45:00', 2, 3),    -- Fundente
(5008, 108, 3001, 2.00, 700000.00, '2025-07-05 12:00:00', 1, 1);    -- Anillo terminado

-- ==========================
-- INSERT DE ORDENES DE PRODUCCION
-- ==========================


INSERT INTO ordenes_produccion (
    id_orden, descripcion_ordenes_produccion, fecha_inicio_ordenes_produccion,
    fecha_fin_estimada_ordenes_produccion, estado_ordenes_produccion,
    id_user, id_rol, nombre_rol, id_modelo
) VALUES
-- Orden creada por gerente para fabricar un anillo clásico
(6001, 'Producción de 10 anillos clásicos para inventario', '2025-08-01', '2025-08-05', 'En Proceso',
 2, 2, 'Gerente', 1001),

-- Orden creada por administrador para fabricar un brazalete
(6002, 'Producción de brazaletes para promoción de temporada', '2025-08-03', '2025-08-08', 'Pendiente',
 1, 1, 'Administrador del sistema', 1002),


-- Orden del encargado de inventario para reponer stock bajo de aretes
(6003, 'Reposición de aretes modernos por bajo stock', '2025-08-05', '2025-08-10', 'Pendiente',
 3, 3, 'Encargado del inventario', 1003);

INSERT INTO ordenes_produccion (
    id_orden, descripcion_ordenes_produccion, fecha_inicio_ordenes_produccion,
    fecha_fin_estimada_ordenes_produccion, estado_ordenes_produccion,
    id_user, id_rol, nombre_rol, id_modelo
) VALUES
(1001, 'Producción de anillo compromiso modelo clásico', '2025-07-20', '2025-07-25', 'En Proceso', 3, 3, 'Encargado del Inventario', 101),
(1002, 'Producción de cadena elegante modelo fino',      '2025-07-22', '2025-07-28', 'Pendiente', 3, 3, 'Encargado del Inventario', 102),
(1003, 'Producción de pulsera moderna',                  '2025-07-24', '2025-07-30', 'En Proceso', 3, 3, 'Encargado del Inventario', 103);

-- ==========================
-- INSERT ETAPAS DE PRODUCCION
-- ==========================


INSERT INTO etapas_produccion (
    id_etapa, nombre_etapa, descripcion_etapa, id_user, id_rol
) VALUES
-- Etapa 1: Alistamiento de materiales
(10, 'Alistamiento', 'Selección y preparación de materiales, herramientas y moldes.', 3, 3),

-- Etapa 2: Fundición del oro
(11, 'Fundición', 'Fusión del oro y modelado inicial de la pieza.', 4, 4),

-- Etapa 3: Pulido y acabados
(12, 'Pulido y Acabado', 'Pulido final, verificación de calidad y acabado de la joya.', 4, 4),

-- Etapa 4: Control de calidad
(13, 'Control de calidad', 'Revisión técnica y estética antes del embalaje.', 2, 2),

-- Etapa 5: Empaque y despacho
(14, 'Empaque y Despacho', 'Embalaje final del producto y entrega al cliente o inventario.', 2, 2);

INSERT INTO ordenes_produccion (
    id_orden, descripcion_ordenes_produccion, fecha_inicio_ordenes_produccion,
    fecha_fin_estimada_ordenes_produccion, estado_ordenes_produccion,
    id_user, id_rol, nombre_rol, id_modelo
) VALUES
(10001, 'Producción de Anillo Clásico', '2025-07-20', '2025-07-25', 'Finalizada', 4, 4, 'Operario', 1),
(10002, 'Producción de Cadena Elegante', '2025-07-24', '2025-07-28', 'Finalizada', 4, 4, 'Operario', 2),
(10003, 'Producción de Pulsera Moderna', '2025-07-25', '2025-07-30', 'Finalizada', 4, 4, 'Operario', 3);


-- ==========================
-- INSERT DE ORDENES DE ETAPA
-- ==========================

INSERT INTO orden_etapas (
    id_orden, id_etapa, fecha_inicio_etapa, fecha_fin_etapa, estado_etapa,
    id_user, id_rol, nombre_rol, id_modelo
) VALUES
(1001, 1, '2025-07-20', '2025-07-20', 'Completada', 3, 3, 'Encargado del Inventario', 101),
(1001, 2, '2025-07-21', '2025-07-21', 'Completada', 4, 4, 'Operario', 101),
(1001, 3, '2025-07-22', NULL,         'En Proceso', 4, 4, 'Operario', 101),

(1002, 1, '2025-07-24', NULL,         'Pendiente', 3, 3, 'Encargado del Inventario', 102),

(1003, 1, '2025-07-25', '2025-07-25', 'Completada', 3, 3, 'Encargado del Inventario', 103),
(1003, 2, '2025-07-26', NULL,         'En Proceso', 4, 4, 'Operario', 103);

-- ==========================
-- INSERT DE CONSUMO DE INSUMOS
-- ==========================

INSERT INTO consumo_insumos (
    id_consumo, id_orden, codigo_item, cantidad_usada, fecha_consumo, id_user, id_rol
) VALUES
(50001, 1001, 1001, 12.5, '2025-07-20 09:00:00', 4, 4),  -- Consumo de oro para Anillo Clásico
(50002, 1001, 2001, 0.75, '2025-07-20 09:30:00', 4, 4),  -- Ácido nítrico para la misma orden

(50003, 1002, 1001, 25.0, '2025-07-24 10:00:00', 4, 4),  -- Oro para cadena elegante
(50004, 1002, 2002, 1.0,  '2025-07-24 10:15:00', 4, 4),  -- Fundente para cadena elegante

(50005, 1003, 1002, 18.0, '2025-07-25 11:00:00', 4, 4),  -- Oro rosa para pulsera moderna
(50006, 1003, 2003, 0.5,  '2025-07-25 11:20:00', 4, 4);  -- Ácido sulfúrico

-- INSERT DE PRODUCTO TERMINADO
-- ==========================

INSERT INTO productos_terminados (
    id_final, id_orden, codigo_item, cantidad_producida,
    fecha_registro_terminado, id_user, id_rol, id_modelo
) VALUES
(70001, 10001, 1003, 5.00, '2025-07-22 16:00:00', 4, 4, 1),
(70002, 10002, 1004, 3.00, '2025-07-25 18:00:00', 4, 4, 2),
(70003, 10003, 1005, 2.00, '2025-07-26 14:30:00', 4, 4, 3);

INSERT INTO productos_terminados (
    id_final, id_orden, codigo_item, cantidad_producida,
    fecha_registro_terminado, id_user, id_rol, id_modelo
) VALUES
(60001, 10001, 1003, 5.00, '2025-07-22 16:00:00', 4, 4, 1),
(60002, 10002, 1004, 3.00, '2025-07-25 18:00:00', 4, 4, 2);


-- ==========================
-- INSERT HISTORIAL DE PRODUCCION
-- ==========================

INSERT INTO historial_produccion (
    id_historial, id_orden, codigo_item, etapa, cantidad_producto, peso_oro_usado, merma, observaciones, fecha_registro, id_user, id_rol, id_modelo
) VALUES
-- Producción de Anillo Clásico Mujer
(90001, 10001, 1003, 'Fundición', 5.00, 50.00, 0.50, 'Sin novedades', '2025-07-23 10:00:00', 4, 4, 1),
-- Producción de Cadena Elegante
(90002, 10002, 1004, 'Pulido', 3.00, 75.00, 0.80, 'Pulido correcto', '2025-07-25 16:30:00', 4, 4, 2),
-- Producción de Pulsera Moderna
(90003, 10003, 1005, 'Alistamiento', 2.00, 40.00, 0.30, 'Preparación completa', '2025-07-26 09:45:00', 4, 4, 3);

-- ==========================
-- INSERT LOG DE EVENTOS
-- ==========================

INSERT INTO log_eventos (
    id_evento, id_user, id_rol, descripcion_rol, tabla_afectada, registro_afectado, fecha_evento
) VALUES
-- Creación de orden de producción
(80001, 1, 1, 'Creación de orden de producción', 'ordenes_produccion', 'ID Orden: 10001', '2025-07-23 09:00:00'),

-- Registro de entrada al inventario
(80002, 3, 3, 'Entrada de insumo al inventario', 'inventario', 'Código ítem: 2001', '2025-07-23 10:00:00'),

-- Registro de consumo de insumo
(80003, 4, 4, 'Consumo de oro para fundición', 'consumo_insumos', 'ID Consumo: 70001', '2025-07-23 11:15:00'),

-- Finalización de etapa de producción
(80004, 4, 4, 'Etapa de producción completada: Fundición', 'orden_etapas', 'Orden: 10001 - Etapa: 501', '2025-07-23 14:20:00'),

-- Registro de producto terminado
(80005, 4, 4, 'Producto terminado registrado', 'productos_terminados', 'ID Final: 60001', '2025-07-23 16:00:00'),

-- Historial actualizado
(80006, 4, 4, 'Historial de producción registrado', 'historial_produccion', 'ID Historial: 90001', '2025-07-23 16:05:00');

-- ==========================
-- INSERT CLIENTES
-- ==========================

---PENDIENTE 

-- ==========================
-- INSERT DE RETRABAJOS
-- ==========================

INSERT INTO retrabajos (
    id_retrabajo, id_final, motivo_retrabajo, fecha_retrabajo, estado
) VALUES
(95001, 60001, 'Defecto en el acabado', '2025-07-25 10:15:00', 'Pendiente'),
(95002, 60002, 'Falla en soldadura', '2025-07-25 11:45:00', 'En proceso');



--FUNCION Y TRIGGER--
--Cada vez que se actualice una fila en inventario, se verifica si el stock_actual_inventario está por debajo del stock_minimo_inventario.
--
--Si es así, se inserta una notificación tipo "Alerta" en notificaciones_sistema, asignada al primer usuario --con rol de "Administrador".-

-- ==========================
-- FUNCIONES DE NOTIFICACIONES
-- ==========================

CREATE OR REPLACE FUNCTION fn_notificar_stock_bajo()
RETURNS TRIGGER AS $$
DECLARE
    v_id_user DECIMAL(12);
    v_id_rol  DECIMAL(12);
BEGIN

    SELECT u.id_user, r.id_rol
    INTO v_id_user, v_id_rol
    FROM usuario_rol ur
    JOIN roles r ON ur.id_rol = r.id_rol
    JOIN login u ON u.id_user = ur.id_user
    WHERE r.nombre_rol = 'Administrador'
    LIMIT 1;

  
    IF NEW.stock_actual_inventario < NEW.stock_minimo_inventario THEN
        INSERT INTO notificaciones_sistema (
            id_user,
            id_rol,
            tipo_notificacion,
            mensaje,
            tabla_referida,
            id_referencia
        )
        VALUES (
            v_id_user,
            v_id_rol,
            'Alerta',
            'El stock del ítem ' || NEW.descripcion_inventario || ' ha bajado del mínimo permitido.',
            'inventario',
            NEW.codigo_item
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- TRIGGER DE NOTIFICACION
-- ==========================


CREATE TRIGGER trg_alerta_stock_minimo
AFTER UPDATE ON inventario
FOR EACH ROW
EXECUTE FUNCTION fn_notificar_stock_bajo();


-- ==========================
-- FUNCIONES DE ACTUALIZACION
-- ==========================


CREATE OR REPLACE FUNCTION actualizar_updated_at()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- TRIGGER DE ACTUALIZACION
-- ==========================


CREATE TRIGGER trigger_update_inventario
BEFORE UPDATE ON inventario
FOR EACH ROW
EXECUTE FUNCTION actualizar_updated_at();

-- ==========================
-- FUNCION DE KARDEX
-- ==========================

CREATE OR REPLACE FUNCTION registrar_entrada_kardex()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO kardex (
        id_kardex,
        codigo_item,
        tipo_movimiento_kardex,
        concepto_kardex,
        cantidad_kardex,
        fecha_movimiento_kardex
    )
    VALUES (
        NEXTVAL('kardex_seq'),                 -- si usas una secuencia para el ID
        NEW.codigo_item,
        'entrada',
        'Compra registrada en factura ID ' || NEW.id_factura,
        NEW.cantidad_comprada,
        NEW.fecha_compra
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- ==========================
-- TRIGGER DE KARDEX
-- ==========================

CREATE TRIGGER trigger_registro_kardex
AFTER INSERT ON facturas_compras
FOR EACH ROW
EXECUTE FUNCTION registrar_entrada_kardex();

--- SECUENCIA DE KARDEX PARA GENERAR ID AUTOMATIZADO
--CREATE SEQUENCE IF NOT EXISTS kardex_seq START 1000 INCREMENT 1;

--CREATE SEQUENCE IF NOT EXISTS kardex_id_seq START 100001;

-- ==========================
-- FUNCIONES DE KARDEX ACTUALIZACION
-- ==========================


CREATE OR REPLACE FUNCTION registrar_actualizacion_factura_kardex()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO kardex (
        id_kardex,
        codigo_item,
        tipo_movimiento_kardex,
        concepto_kardex,
        cantidad_kardex,
        fecha_movimiento_kardex
    ) VALUES (
        NEXTVAL('kardex_id_seq'),
        NEW.codigo_item,
        'entrada',
        CONCAT('Actualización factura ID ', NEW.id_factura),
        NEW.cantidad_comprada,
        NOW()
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- TRIGGER DE ACTUALIZACION
-- ==========================

CREATE TRIGGER trigger_actualizacion_factura_kardex
AFTER UPDATE ON facturas_compras
FOR EACH ROW
WHEN (OLD.cantidad_comprada IS DISTINCT FROM NEW.cantidad_comprada)
EXECUTE FUNCTION registrar_actualizacion_factura_kardex();

-- ==========================
-- FUNCIONES DE KARDEX ELIMINACIÓN 
-- ==========================

CREATE OR REPLACE FUNCTION registrar_eliminacion_factura_kardex()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO kardex (
        id_kardex,
        codigo_item,
        tipo_movimiento_kardex,
        concepto_kardex,
        cantidad_kardex,
        fecha_movimiento_kardex
    ) VALUES (
        NEXTVAL('kardex_id_seq'),
        OLD.codigo_item,
        'salida',
        CONCAT('Eliminación factura ID ', OLD.id_factura),
        OLD.cantidad_comprada,
        NOW()
    );

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- TRIGGER DE KARDEX ELIMINACIÓN 
-- ==========================

CREATE TRIGGER trigger_eliminacion_factura_kardex
AFTER DELETE ON facturas_compras
FOR EACH ROW
EXECUTE FUNCTION registrar_eliminacion_factura_kardex();

---Función para actualizar el stock cuando hay entradas en el Kardex
-- Y También quieres que se reste el stock cuando se haga una salida, puedes modificar la función así:--


-- ==========================
-- FUNCION DE ACTUALIZAR EL STOCK
-- ==========================
CREATE OR REPLACE FUNCTION actualizar_stock_inventario_movimiento()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.tipo_movimiento_kardex = 'entrada' THEN
        UPDATE inventario
        SET stock_actual_inventario = stock_actual_inventario + NEW.cantidad_kardex,
            updated_at_inventario = NOW()
        WHERE codigo_item = NEW.codigo_item;

    ELSIF NEW.tipo_movimiento_kardex = 'salida' THEN
        UPDATE inventario
        SET stock_actual_inventario = stock_actual_inventario - NEW.cantidad_kardex,
            updated_at_inventario = NOW()
        WHERE codigo_item = NEW.codigo_item;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- ==========================
-- TRIGGER DE ACTUALIZAR EL STOCK
-- ==========================

CREATE TRIGGER trigger_actualizar_stock_movimiento
AFTER INSERT ON kardex
FOR EACH ROW
EXECUTE FUNCTION actualizar_stock_inventario_movimiento();


-- ==========================
--- Función del Trigger con Kardex + Notificación
-- ==========================

CREATE OR REPLACE FUNCTION registrar_kardex_y_notificacion_finalizacion()
RETURNS TRIGGER AS $$
DECLARE
    nuevo_id_kardex DECIMAL(12);
BEGIN
    -- Generar ID único para kardex (puedes cambiar esta lógica si usas secuencias)
    nuevo_id_kardex := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

    -- Insertar movimiento en Kardex
    INSERT INTO kardex (
        id_kardex,
        codigo_item,
        tipo_movimiento_kardex,
        concepto_kardex,
        cantidad_kardex,
        fecha_movimiento_kardex
    ) VALUES (
        nuevo_id_kardex,
        NEW.codigo_item,
        'entrada',
        CONCAT('Entrada por producto terminado. Modelo: ', NEW.id_modelo),
        NEW.cantidad_producida,
        NOW()
    );

    -- Insertar notificación
    INSERT INTO notificaciones_sistema (
        id_notificacion,
        tipo_notificacion,
        mensaje_notificacion,
        nivel_prioridad,
        leida,
        fecha_creacion,
        id_user,
        id_rol,
        tabla_referida,
        id_referencia
    ) VALUES (
        (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT,  -- ID único como decimal
        'Nuevo Ingreso',
        CONCAT('Producto terminado agregado. Código: ', NEW.codigo_item),
        'Media',
        FALSE,
        NOW(),
        NEW.id_user,
        NEW.id_rol,
        'productos_terminados',
        NEW.id_final
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- Trigger con Kardex + Notificación
-- ==========================

CREATE TRIGGER trigger_kardex_y_notificacion_final
AFTER INSERT ON productos_terminados
FOR EACH ROW
EXECUTE FUNCTION registrar_kardex_y_notificacion_finalizacion();


-- ==========================
-- Función para insertar en log_eventos desde historial_produccion

CREATE OR REPLACE FUNCTION insertar_log_historial_produccion()
RETURNS TRIGGER AS $$
DECLARE
    id_evento_nuevo DECIMAL(12);
BEGIN
    -- Generar ID único para log (puedes adaptar si tienes secuencias)
    id_evento_nuevo := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

    -- Insertar en log_eventos
    INSERT INTO log_eventos (
        id_evento,
        id_user,
        id_rol,
        descripcion_rol,
        tabla_afectada,
        registro_afectado,
        fecha_evento
    ) VALUES (
        id_evento_nuevo,
        NEW.id_user,
        NEW.id_rol,
        CONCAT('Registro insertado en historial de producción. Etapa: ', NEW.etapa),
        'historial_produccion',
        NEW.id_historial::TEXT,
        NOW()
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================

-- ==========================
-- TRIGGER para insertar en log_eventos desde historial_produccion
-- ==========================

CREATE TRIGGER trigger_log_historial_produccion
AFTER INSERT ON historial_produccion
FOR EACH ROW
EXECUTE FUNCTION insertar_log_historial_produccion();


-- ==========================
-- funcion  para registrar en log_eventos
-- ==========================

CREATE OR REPLACE FUNCTION registrar_log_evento()
RETURNS TRIGGER AS $$
DECLARE
    id_evento_nuevo DECIMAL(12);
    accion_texto TEXT;
    id_registro_afectado TEXT;
BEGIN
    -- Generar un ID basado en timestamp (puedes reemplazar por secuencia si prefieres)
    id_evento_nuevo := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

    -- Determinar acción y registro afectado
    IF TG_OP = 'INSERT' THEN
        accion_texto := 'INSERT realizado en tabla ' || TG_TABLE_NAME;
        id_registro_afectado := COALESCE(NEW.codigo_item::TEXT, NEW.id_orden::TEXT, '[Sin ID]');
    ELSIF TG_OP = 'UPDATE' THEN
        accion_texto := 'UPDATE realizado en tabla ' || TG_TABLE_NAME;
        id_registro_afectado := COALESCE(NEW.codigo_item::TEXT, NEW.id_orden::TEXT, '[Sin ID]');
    ELSIF TG_OP = 'DELETE' THEN
        accion_texto := 'DELETE realizado en tabla ' || TG_TABLE_NAME;
        id_registro_afectado := COALESCE(OLD.codigo_item::TEXT, OLD.id_orden::TEXT, '[Sin ID]');
    END IF;

    -- Insertar en log_eventos
    INSERT INTO log_eventos (
        id_evento,
        id_user,
        id_rol,
        descripcion_rol,
        tabla_afectada,
        registro_afectado,
        fecha_evento
    ) VALUES (
        id_evento_nuevo,
        COALESCE(NEW.id_user, OLD.id_user),
        COALESCE(NEW.id_rol, OLD.id_rol),
        accion_texto,
        TG_TABLE_NAME,
        id_registro_afectado,
        NOW()
    );

    -- Devolver el registro afectado para continuar operación
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    ELSE
        RETURN NEW;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- TRIGGER para la tabla clave - inventario
-- ==========================

-- Trigger para INSERT
CREATE TRIGGER log_insert_inventario
AFTER INSERT ON inventario
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- Trigger para UPDATE
CREATE TRIGGER log_update_inventario
AFTER UPDATE ON inventario
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- Trigger para DELETE
CREATE TRIGGER log_delete_inventario
AFTER DELETE ON inventario
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- ==========================
-- TRIGGER para ordenes_producion
-- ==========================

-- Trigger para INSERT
CREATE TRIGGER log_insert_ordenes
AFTER INSERT ON ordenes_produccion
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- Trigger para UPDATE
CREATE TRIGGER log_update_ordenes
AFTER UPDATE ON ordenes_produccion
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- Trigger para DELETE
CREATE TRIGGER log_delete_ordenes
AFTER DELETE ON ordenes_produccion
FOR EACH ROW EXECUTE FUNCTION registrar_log_evento();

-- ==========================
-- TRIGGER para insertar en log_eventos desde historial_produccion
-- ==========================
-- ==========================

CREATE OR REPLACE FUNCTION log_retrabajo_evento()
RETURNS TRIGGER AS $$
DECLARE
    accion_texto TEXT;
    id_registro_afectado TEXT;
    id_evento_nuevo DECIMAL(12);
BEGIN
    -- Generar un ID basado en timestamp
    id_evento_nuevo := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

    IF TG_OP = 'INSERT' THEN
        accion_texto := 'Nuevo retrabajo registrado';
        id_registro_afectado := NEW.id_retrabajo::TEXT;
    ELSIF TG_OP = 'UPDATE' THEN
        accion_texto := 'Retrabajo actualizado';
        id_registro_afectado := NEW.id_retrabajo::TEXT;
    END IF;

    INSERT INTO log_eventos (
        id_evento,
        id_user,
        id_rol,
        descripcion_rol,
        tabla_afectada,
        registro_afectado,
        fecha_evento
    ) VALUES (
        id_evento_nuevo,
        (SELECT id_user FROM productos_terminados WHERE id_final = NEW.id_final LIMIT 1),
        (SELECT id_rol FROM productos_terminados WHERE id_final = NEW.id_final LIMIT 1),
        accion_texto,
        'retrabajos',
        id_registro_afectado,
        NOW()
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- triggers para INSERT y UPDATE en la tabla retrabajos
-- ==========================

-- Trigger para INSERT
CREATE TRIGGER trigger_log_insert_retrabajo
AFTER INSERT ON retrabajos
FOR EACH ROW EXECUTE FUNCTION log_retrabajo_evento();

-- Trigger para UPDATE
CREATE TRIGGER trigger_log_update_retrabajo
AFTER UPDATE ON retrabajos
FOR EACH ROW EXECUTE FUNCTION log_retrabajo_evento();


-- ==========================
--  Funcion para registrar en log_eventos las inserciones en productos_terminados
-- ==========================


CREATE OR REPLACE FUNCTION log_insert_producto_terminado()
RETURNS TRIGGER AS $$
DECLARE
    id_evento_nuevo DECIMAL(12);
BEGIN
    id_evento_nuevo := (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT;

    INSERT INTO log_eventos (
        id_evento,
        id_user,
        id_rol,
        descripcion_rol,
        tabla_afectada,
        registro_afectado,
        fecha_evento
    )
    VALUES (
        id_evento_nuevo,
        NEW.id_user,
        NEW.id_rol,
        'Producto terminado registrado',
        'productos_terminados',
        NEW.id_final::TEXT,
        NOW()
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ==========================
-- Trigger para registrar en log_eventos las inserciones en productos_terminados
-- ==========================

CREATE TRIGGER trigger_log_insert_producto_terminado
AFTER INSERT ON productos_terminados
FOR EACH ROW
EXECUTE FUNCTION log_insert_producto_terminado();

-- ==========================
-- Funcion para detectar órdenes vencidas automáticamente
-- ==========================

CREATE OR REPLACE FUNCTION verificar_orden_vencida()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.estado_ordenes_produccion != 'Finalizada' AND
       NEW.fecha_fin_estimada_ordenes_produccion IS NOT NULL AND
       NEW.fecha_fin_estimada_ordenes_produccion < CURRENT_DATE THEN

        INSERT INTO notificaciones_sistema (
            id_notificacion,
            tipo_notificacion,
            mensaje_notificacion,
            nivel_prioridad,
            leida,
            fecha_creacion,
            id_user,
            id_rol,
            tabla_referida,
            id_referencia
        ) VALUES (
            (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT,
            'Orden Vencida',
            CONCAT('La orden ', NEW.id_orden, ' ha superado su fecha estimada.'),
            'Alta',
            FALSE,
            NOW(),
            NEW.id_user,
            NEW.id_rol,
            'ordenes_produccion',
            NEW.id_orden
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- ==========================
-- Trigger para detectar órdenes vencidas automáticamente
-- ==========================

CREATE TRIGGER trigger_orden_vencida_check
AFTER INSERT OR UPDATE ON ordenes_produccion
FOR EACH ROW
EXECUTE FUNCTION verificar_orden_vencida();

-- ==========================
-- Funcion para actualizar updated_at en más tablas
-- ==========================

CREATE OR REPLACE FUNCTION fn_auto_updated_at()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at := NOW();
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER update_timestamp_facturas
BEFORE UPDATE ON facturas_compras
FOR EACH ROW
EXECUTE FUNCTION fn_auto_updated_at();


