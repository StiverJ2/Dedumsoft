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
    codigo_item       DECIMAL(12) PRIMARY KEY NOT NULL, -- Identificador único del ítem
    descripcion       TEXT NOT NULL, -- Descripción detallada del ítem
    tipo_item         VARCHAR(20) NOT NULL CHECK (tipo_item IN ('oro', 'insumo', 'producto')), -- Clasificación del ítem
    quilataje         NUMERIC(4,2) NOT NULL, -- Pureza del oro (si aplica)
    tipo_pieza        VARCHAR(50) NOT NULL, -- Tipo de pieza o categoría del producto
    peso_neto         NUMERIC(10,2) NOT NULL, -- Peso sin incluir merma
    peso_bruto        NUMERIC(10,2) NOT NULL, -- Peso total con merma
    unidad_medida     VARCHAR(10) DEFAULT 'gr' NOT NULL, -- Unidad estándar de medida
    stock_actual      NUMERIC(10,2) DEFAULT 0 NOT NULL, -- Cantidad en stock
    stock_minimo      NUMERIC(10,2) NOT NULL, -- Nivel mínimo de inventario permitido
    punto_reorden     NUMERIC(10,2) NOT NULL, -- Punto de reorden para abastecimiento
    valor_unitario    NUMERIC(12,2) NOT NULL, -- Costo individual del ítem
    estado            BOOLEAN DEFAULT TRUE NOT NULL, -- Activo o no
    created_at        TIMESTAMP DEFAULT NOW() NOT NULL, -- Fecha de creación
    updated_at        TIMESTAMP DEFAULT NOW() NOT NULL -- Fecha de última actualización
);

-- ==========================
-- TABLA KARDEX
-- ==========================
CREATE TABLE kardex (
    id_kardex         DECIMAL(12) PRIMARY KEY , -- Identificador del movimientO
    codigo_item       DECIMAL(12) NOT NULL, -- Ítem relacionado al movimiento
    tipo_movimiento   VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida')), -- Entrada o salida
    concepto          VARCHAR(100), -- Descripción del movimiento
    cantidad          NUMERIC(10,2), -- Cantidad involucrada
    fecha_movimiento  TIMESTAMP DEFAULT NOW(), -- Fecha del movimiento
    FOREIGN KEY (codigo_item) REFERENCES inventario(codigo_item)
);

-- ==========================
-- TABLA ORDENES DE PRODUCCIÓN
-- ==========================
CREATE TABLE ordenes_produccion (
    id_orden           DECIMAL(12) PRIMARY KEY, -- ID de la orden
    descripcion        TEXT, -- Descripción de la orden
    fecha_inicio       DATE NOT NULL, -- Inicio programado
    fecha_fin_estimada DATE, -- Fecha estimada de terminación
    estado             VARCHAR(20) CHECK (estado IN ('Pendiente', 'En Proceso', 'Finalizada')), -- Estado actual
    created_at         TIMESTAMP DEFAULT NOW() -- Fecha de creación
);

-- ==========================
-- TABLA ETAPAS DE PRODUCCIÓN
-- ==========================
CREATE TABLE etapas_produccion (
    id_etapa           DECIMAL(12) PRIMARY KEY, -- ID de la etapa
    nombre_etapa       VARCHAR(100) NOT NULL, -- Nombre descriptivo
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
    fecha_consumo      TIMESTAMP DEFAULT NOW(), -- Fecha del consumo
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



-- ==========================
-- DATOS DE PRUEBA PARA INVENTARIO
-- ==========================
INSERT INTO inventario VALUES
(1001, 'Oro 18k en lingotes', 'oro', 18.00, 'Lingote', 500.00, 510.00, 'gr', 1000.00, 200.00, 250.00, 250000.00, TRUE, NOW(), NOW()),
(2001, 'Ácido nítrico', 'insumo', 0.00, 'Insumo Químico', 1.00, 1.05, 'lt', 50.00, 10.00, 15.00, 15000.00, TRUE, NOW(), NOW()),
(3001, 'Anillo Clásico Mujer', 'producto', 18.00, 'Anillo', 10.00, 10.50, 'gr', 20.00, 5.00, 10.00, 650000.00, TRUE, NOW(), NOW());

-- ==========================
-- DATOS DE PRUEBA PARA ROLES
-- ==========================
INSERT INTO roles VALUES
(1, 'Administrador'),
(2, 'Operario'),
(3, 'Supervisor');

-- ==========================
-- DATOS DE PRUEBA PARA LOGIN
-- ==========================
INSERT INTO login VALUES
(1, 'admin', 'Admin123!', 'admin@joyeria.com', NOW(), TRUE),
(2, 'operario1', 'Operario#1', 'operario1@joyeria.com', NOW(), TRUE);

-- ==========================
-- DATOS DE PRUEBA PARA USUARIO_ROL
-- ==========================
INSERT INTO usuario_rol VALUES
(1, 1),
(2, 2);

-- ==========================
-- DATOS DE PRUEBA PARA MODELOS DE JOYERÍA
-- ==========================
INSERT INTO modelos_joyeria (nombre_modelo, descripcion, peso_estimado, precio_estimado) VALUES
('Anillo Clásico Mujer', 'Anillo de oro amarillo clásico', 10.00, 650000.00),
('Cadena Elegante', 'Cadena fina para eventos especiales', 25.00, 1450000.00);

-- ==========================
-- DATOS DE PRUEBA PARA CLIENTES
-- ==========================
INSERT INTO clientes VALUES
(101, 'María Gómez', 1234567890, 'maria@example.com', '3104567890'),
(102, 'Carlos Pérez', 9876543210, 'carlos@example.com', '3157891234');

-- ==========================
-- DATOS DE PRUEBA PARA ORDENES DE PRODUCCIÓN
-- ==========================
INSERT INTO ordenes_produccion VALUES
(5001, 'Producción de anillos clásicos', '2025-07-01', '2025-07-10', 'En Proceso', NOW());

-- ==========================
-- DATOS DE PRUEBA PARA ETAPAS DE PRODUCCIÓN
-- ==========================
INSERT INTO etapas_produccion VALUES
(1, 'Alistamiento', 'Preparación de materiales y diseño de pieza'),
(2, 'Fundición', 'Fusión del oro e inicio de manufactura'),
(3, 'Preparación del producto y entrega', 'Pulido, inspección y embalaje final');

-- ==========================
-- DATOS DE PRUEBA PARA ORDEN_ETAPAS
-- ==========================
INSERT INTO orden_etapas VALUES
(5001, 1, '2025-07-01', '2025-07-02', 'Completada'),
(5001, 2, '2025-07-03', NULL, 'En Proceso');

-- ==========================
-- DATOS DE PRUEBA PARA CONSUMO_INSUMOS
-- ==========================
INSERT INTO consumo_insumos VALUES
(9001, 5001, 2001, 2.00, NOW());

-- ==========================
-- DATOS DE PRUEBA PARA PRODUCTOS TERMINADOS
-- ==========================
INSERT INTO productos_terminados VALUES
(8001, 5001, 3001, 5.00, NOW());

-- ==========================
-- DATOS DE PRUEBA PARA HISTORIAL DE PRODUCCIÓN
-- ==========================
INSERT INTO historial_produccion VALUES
(7001, 5001, 3001, 'Fundición', 'Luis Torres', 5.00, 50.00, 0.50, 'Sin novedades', NOW());

-- ==========================
-- DATOS DE PRUEBA PARA RETRABAJOS
-- ==========================
INSERT INTO retrabajos VALUES
(6001, 8001, 'Falla en pulido', NOW(), 'Pendiente');

-- ==========================
-- DATOS DE PRUEBA PARA LOG_EVENTOS
-- ==========================
INSERT INTO log_eventos (usuario, accion, tabla_afectada, registro_afectado) VALUES
('admin', 'INSERT', 'inventario', '1001'),
('admin', 'INSERT', 'ordenes_produccion', '5001');
