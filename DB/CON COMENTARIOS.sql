-- ====================================================
-- SISTEMA DE PRODUCCIÓN Y CONTROL DE INVENTARIO
-- JOYERÍA EN ORO - MODELO COMPLETO CON 3 ETAPAS
-- ====================================================

-- LIMPIEZA DE TABLAS
DROP TABLE IF EXISTS historial_produccion; -- Historial de cada proceso de producción
DROP TABLE IF EXISTS productos_terminados; -- Registro final de productos terminados
DROP TABLE IF EXISTS consumo_insumos; -- Registro de insumos utilizados en producción
DROP TABLE IF EXISTS orden_etapas; -- Relación de órdenes con sus etapas
DROP TABLE IF EXISTS etapas_produccion; -- Catálogo de etapas de producción
DROP TABLE IF EXISTS ordenes_produccion; -- Registro de órdenes de producción
DROP TABLE IF EXISTS kardex; -- Movimiento de inventario (entradas/salidas)
DROP TABLE IF EXISTS inventario; -- Inventario general de materiales, productos y oro
DROP TABLE IF EXISTS login; -- Datos de acceso de los usuarios
DROP TABLE IF EXISTS log_eventos; -- Bitácora de eventos realizados por usuarios
DROP TABLE IF EXISTS roles; -- Tipos de roles de usuario
DROP TABLE IF EXISTS usuario_rol; -- Relación entre usuarios y roles
DROP TABLE IF EXISTS modelos_joyeria; -- Modelos de joyería predefinidos
DROP TABLE IF EXISTS clientes; -- Datos de los clientes
DROP TABLE IF EXISTS retrabajos; -- Correcciones o reprocesos de piezas

-- ==========================
-- TABLA INVENTARIO
-- ==========================
CREATE TABLE inventario (
    codigo_item                   DECIMAL(12)           PRIMARY KEY                 NOT NULL,               -- Identificador único del ítem
    descripcion_inventario        TEXT                                              NOT NULL,               -- Descripción detallada del ítem
    tipo_item_inventario          VARCHAR(20)                                       NOT NULL    CHECK (tipo_item IN ('oro', 'insumo', 'producto')), -- Clasificación del ítem
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
CREATE TABLE kardex (
    id_kardex                     DECIMAL(12)          PRIMARY KEY                 NOT NULL,                    -- Identificador del movimientO
    codigo_item                   DECIMAL(12)                                      NOT NULL,                    -- Ítem relacionado al movimiento
    tipo_movimiento_kardex        VARCHAR(20)                                      NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida')),    -- Entrada o salida
    concepto_kardex               VARCHAR(100)                                     NOT NULL,                    -- Descripción del movimiento
    cantidad_kardex               NUMERIC(10,2)                                   NOT NULL                      -- Cantidad involucrada
    fecha_movimiento_kardex       TIMESTAMP DEFAULT NOW(), -- Fecha del movimiento
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- TABLA ORDENES DE PRODUCCIÓN
-- ==========================
CREATE TABLE ordenes_produccion (
    id_orden                               DECIMAL(12)          PRIMARY KEY, -- ID de la orden
    descripcion_ordenes_produccion         TEXT, -- Descripción de la orden
    fecha_inicio_ordenes_produccion        DATE NOT NULL, -- Inicio programado
    fecha_fin_estimada_ordenes_produccion  DATE, -- Fecha estimada de terminación
    estado_ordenes_produccion              VARCHAR(20) CHECK (estado IN ('Pendiente', 'En Proceso', 'Finalizada')), -- Estado actual
    created_at_ordenes_produccion          TIMESTAMP DEFAULT NOW() -- Fecha de creación
);

-- ==========================
-- TABLA ETAPAS DE PRODUCCIÓN
-- ==========================
CREATE TABLE etapas_produccion (
    id_etapa           DECIMAL(12) PRIMARY KEY, -- ID de la etapa
    nombre_etapa       VARCHAR(100) NOT NULL, -- ID de la etapa
    descripcion_etapa  TEXT -- Detalle adicional de la etapa
);

-- ==========================
-- TABLA ORDEN ETAPAS
-- ==========================
CREATE TABLE orden_etapas (
    id_orden             DECIMAL(12) NOT NULL, -- Orden relacionada
    id_etapa             DECIMAL(12) NOT NULL, -- Etapa asignada
    fecha_inicio_etapa   DATE NOT NULL, -- Inicio real de la etapa
    fecha_fin_etapa      DATE, -- Fin real de la etapa
    estado_etapa         VARCHAR(20) CHECK (estado_etapa IN ('Pendiente', 'En Proceso', 'Completada')), -- Estado actual
    PRIMARY KEY (id_orden, id_etapa),
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (id_etapa) REFERENCES etapas_produccion(id_etapa)
);

-- ==========================
-- TABLA CONSUMO INSUMOS
-- ==========================
CREATE TABLE consumo_insumos (
    id_consumo         DECIMAL(12) PRIMARY KEY NOT NULL, -- Identificador del consumo
    id_orden           DECIMAL(12) NOT NULL, -- Orden relacionada
    codigo_item        DECIMAL(12) NOT NULL, -- Ítem consumido
    cantidad_usada     NUMERIC(10,2) NOT NULL, -- Cantidad usada en la producción
    fecha_consumo      TIMESTAMP DEFAULT NOW(), -- Cantidad usada en la producción
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- TABLA PRODUCTOS TERMINADOS
-- ==========================
CREATE TABLE productos_terminados (
    id_final DECIMAL(12) PRIMARY KEY NOT NULL, -- ID del producto final
    id_orden DECIMAL(12) NOT NULL, -- Orden de la cual proviene
    codigo_item DECIMAL(12) NOT NULL, -- Ítem registrado como producto terminado
    cantidad_producida NUMERIC(10,2) NOT NULL, -- Cantidad producida
    fecha_registro TIMESTAMP DEFAULT NOW() NOT NULL, -- Fecha del registro
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- TABLA HISTORIAL PRODUCCIÓN
-- ==========================
CREATE TABLE historial_produccion (
    id_historial DECIMAL(12) PRIMARY KEY, -- ID del registro
    id_orden DECIMAL(12) NOT NULL, -- Orden asociada
    codigo_item DECIMAL(12) NOT NULL, -- Ítem relacionado
    etapa VARCHAR(100) NOT NULL, -- Nombre de la etapa
    responsable VARCHAR(100) NOT NULL, -- Persona encargada
    cantidad NUMERIC(10,2) NOT NULL, -- Cantidad trabajada
    peso_oro_usado NUMERIC(10,2) NOT NULL, -- Peso de oro empleado
    merma NUMERIC(10,2) NOT NULL, -- Merma generada
    observaciones VARCHAR(50), -- Observaciones adicionales
    fecha_registro TIMESTAMP DEFAULT NOW() NOT NULL, -- Fecha del evento
    FOREIGN KEY (id_orden) REFERENCES ordenes_produccion(id_orden),
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);
-- ==========================
-- TABLA LOGIN
-- ==========================
CREATE TABLE login (
    id_user         DECIMAL(12) PRIMARY KEY NOT NULL, -- ID del usuario
    username        VARCHAR(50) UNIQUE NOT NULL, -- Nombre de usuario
    contrasena      VARCHAR(64) NOT NULL CHECK ( -- Contraseña segura con requisitos mínimos
        LENGTH(contrasena) >= 8 AND
        contrasena ~ '[A-Z]' AND
        contrasena ~ '[a-z]' AND
        contrasena ~ '[0-9]' AND
        contrasena ~ '[^A-Za-z0-9]'
    ),
    email           VARCHAR(100) UNIQUE NOT NULL, -- Correo del usuario
    fecha_creacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, -- Fecha de creación
    estado          BOOLEAN NOT NULL -- Activo o inactivo
);

-- ==========================
-- TABLA LOG EVENTOS
-- ==========================
CREATE TABLE log_eventos (
    id_evento SERIAL PRIMARY KEY NOT NULL, -- ID del evento
    usuario VARCHAR(50) NOT NULL, -- Usuario que realiza la acción
    accion TEXT NOT NULL, -- Acción realizada
    tabla_afectada VARCHAR(50) NOT NULL, -- Tabla afectada
    registro_afectado VARCHAR(100) NOT NULL, -- Registro específico
    fecha TIMESTAMP DEFAULT NOW() NOT NULL -- Fecha del evento
);

-- ==========================
-- TABLA ROLES
-- ==========================
CREATE TABLE roles (
    id_rol DECIMAL(12) PRIMARY KEY NOT NULL, -- ID del rol
    nombre_rol VARCHAR(50) UNIQUE NOT NULL -- Nombre único del rol
);

-- ==========================
-- TABLA USUARIO ROL
-- ==========================
CREATE TABLE usuario_rol (
    id_user DECIMAL(12) NOT NULL, -- ID del usuario
    id_rol DECIMAL(12) NOT NULL, -- ID del rol asignado
    PRIMARY KEY (id_user, id_rol),
    FOREIGN KEY (id_user) REFERENCES login(id_user),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- ==========================
-- TABLA MODELOS JOYERÍA
-- ==========================
CREATE TABLE modelos_joyeria (
    id_modelo SERIAL PRIMARY KEY NOT NULL, -- ID del modelo
    nombre_modelo VARCHAR(100) NOT NULL, -- Nombre del diseño
    descripcion TEXT, -- Detalles del diseño
    peso_estimado NUMERIC(10,2) NOT NULL, -- Peso aproximado
    precio_estimado NUMERIC(12,2) NOT NULL -- Precio sugerido
);

-- ==========================
-- TABLA CLIENTES
-- ==========================
CREATE TABLE clientes (
    id_cliente DECIMAL(12) PRIMARY KEY NOT NULL, -- ID del cliente
    nombre_cliente VARCHAR(50) NOT NULL, -- Nombre del cliente
    contacto_cliente DECIMAL(20) NOT NULL, -- Número de contacto
    email_cliente VARCHAR(50) NOT NULL, -- Correo electrónico
    telefono_cliente VARCHAR(20) NOT NULL -- Teléfono del cliente
);

-- ==========================
-- TABLA RETRABAJOS
-- ==========================
CREATE TABLE retrabajos (
    id_retrabajo DECIMAL(12) PRIMARY KEY NOT NULL, -- ID del retrabajo
    id_final DECIMAL(12) NOT NULL, -- ID del producto final asociado
    motivo VARCHAR(50) NOT NULL, -- Razón del retrabajo
    fecha TIMESTAMP DEFAULT NOW() NOT NULL, -- Fecha de registro
    estado VARCHAR(10) CHECK (estado IN ('Pendiente', 'En proceso', 'Finalizado')) NOT NULL, -- Estado del retrabajo
    FOREIGN KEY (id_final) REFERENCES productos_terminados(id_final)
);
