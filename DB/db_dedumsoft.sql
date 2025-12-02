
-- ============================================
-- SISTEMA DE GESTIÓN PARA JOYERÍA
-- Base de Datos: PostgreSQL
-- ============================================

-- Crear esquema (opcional)
CREATE SCHEMA IF NOT EXISTS joyeria;
SET search_path TO joyeria, public;

-- ============================================
-- TABLAS DE SOPORTE
-- ============================================

-- Tabla: proveedores
CREATE TABLE proveedores (
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
CREATE TABLE artesanos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_ingreso DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- MÓDULO DE INVENTARIOS
-- ============================================

-- Tabla: inventario_oro
CREATE TABLE inventario_oro (
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
CREATE TABLE inventario_maquinaria (
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
    ubicacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: inventario_insumos
CREATE TABLE inventario_insumos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL DEFAULT 0 CHECK (cantidad >= 0),
    unidad_medida VARCHAR(20) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0),
    stock_minimo DECIMAL(10,3) DEFAULT 0,
    proveedor_id INTEGER REFERENCES proveedores(id) ON DELETE SET NULL,
    ubicacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: movimientos (historial de todos los inventarios)
CREATE TABLE movimientos (
    id SERIAL PRIMARY KEY,
    tipo_inventario VARCHAR(20) NOT NULL CHECK (tipo_inventario IN ('oro', 'maquinaria', 'insumos')),
    item_id INTEGER NOT NULL,
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'transferencia')),
    cantidad DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(500),
    referencia VARCHAR(100),
    usuario_id INTEGER,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- MÓDULO DE PRODUCCIÓN
-- ============================================

-- Tabla: productos (catálogo)
CREATE TABLE productos (
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

-- Tabla: recetas_produccion (BOM - Bill of Materials)
CREATE TABLE recetas_produccion (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    tipo_material VARCHAR(20) NOT NULL CHECK (tipo_material IN ('oro', 'insumo')),
    material_id INTEGER NOT NULL,
    cantidad_requerida DECIMAL(10,3) NOT NULL CHECK (cantidad_requerida > 0),
    es_opcional BOOLEAN DEFAULT FALSE,
    notas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(producto_id, tipo_material, material_id)
);

-- Tabla: ordenes_produccion
CREATE TABLE ordenes_produccion (
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

-- Tabla: consumo_materiales
CREATE TABLE consumo_materiales (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER NOT NULL REFERENCES ordenes_produccion(id) ON DELETE CASCADE,
    tipo_material VARCHAR(20) NOT NULL CHECK (tipo_material IN ('oro', 'insumo')),
    material_id INTEGER NOT NULL,
    cantidad_consumida DECIMAL(10,3) NOT NULL CHECK (cantidad_consumida > 0),
    costo_unitario DECIMAL(10,2),
    costo_total DECIMAL(10,2),
    fecha_consumo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INTEGER
);

-- ============================================
-- MÓDULO DE CREACIONES Y ESTADÍSTICAS
-- ============================================

-- Tabla: creaciones_terminadas
CREATE TABLE creaciones_terminadas (
    id SERIAL PRIMARY KEY,
    orden_produccion_id INTEGER REFERENCES ordenes_produccion(id) ON DELETE SET NULL,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE RESTRICT,
    codigo_pieza VARCHAR(50) UNIQUE NOT NULL,
    artesano_id INTEGER REFERENCES artesanos(id) ON DELETE SET NULL,
    fecha_terminado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_final_gramos DECIMAL(10,3) CHECK (peso_final_gramos > 0),
    costo_materiales DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_mano_obra DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_total DECIMAL(10,2) NOT NULL DEFAULT 0,
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

-- Tabla: estadisticas_produccion
CREATE TABLE estadisticas_produccion (
    id SERIAL PRIMARY KEY,
    periodo VARCHAR(20) NOT NULL,
    tipo_periodo VARCHAR(20) NOT NULL CHECK (tipo_periodo IN ('dia', 'semana', 'mes', 'trimestre', 'año')),
    total_piezas INTEGER DEFAULT 0,
    piezas_por_tipo JSONB,
    total_oro_usado_gramos DECIMAL(10,3) DEFAULT 0,
    costo_materiales_total DECIMAL(10,2) DEFAULT 0,
    costo_mano_obra_total DECIMAL(10,2) DEFAULT 0,
    horas_trabajadas DECIMAL(10,2) DEFAULT 0,
    artesano_mas_productivo_id INTEGER REFERENCES artesanos(id) ON DELETE SET NULL,
    producto_mas_fabricado_id INTEGER REFERENCES productos(id) ON DELETE SET NULL,
    promedio_tiempo_fabricacion DECIMAL(5,2),
    tasa_calidad_a_porcentaje DECIMAL(5,2),
    total_vendido DECIMAL(10,2) DEFAULT 0,
    utilidad_neta DECIMAL(10,2) DEFAULT 0,
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(periodo, tipo_periodo)
);

-- ============================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- ============================================

-- Inventarios
CREATE INDEX idx_inventario_oro_tipo ON inventario_oro(tipo_oro);
CREATE INDEX idx_inventario_oro_proveedor ON inventario_oro(proveedor_id);
CREATE INDEX idx_inventario_insumos_categoria ON inventario_insumos(categoria);
CREATE INDEX idx_inventario_insumos_proveedor ON inventario_insumos(proveedor_id);
CREATE INDEX idx_inventario_maquinaria_estado ON inventario_maquinaria(estado);

-- Movimientos
CREATE INDEX idx_movimientos_tipo ON movimientos(tipo_inventario, item_id);
CREATE INDEX idx_movimientos_fecha ON movimientos(fecha);

-- Producción
CREATE INDEX idx_ordenes_estado ON ordenes_produccion(estado);
CREATE INDEX idx_ordenes_artesano ON ordenes_produccion(artesano_id);
CREATE INDEX idx_ordenes_fecha ON ordenes_produccion(fecha_creacion);
CREATE INDEX idx_consumo_orden ON consumo_materiales(orden_produccion_id);

-- Creaciones
CREATE INDEX idx_creaciones_fecha ON creaciones_terminadas(fecha_terminado);
CREATE INDEX idx_creaciones_producto ON creaciones_terminadas(producto_id);
CREATE INDEX idx_creaciones_artesano ON creaciones_terminadas(artesano_id);
CREATE INDEX idx_creaciones_vendida ON creaciones_terminadas(vendida);

-- Estadísticas
CREATE INDEX idx_estadisticas_periodo ON estadisticas_produccion(periodo, tipo_periodo);

-- ============================================
-- TRIGGERS ÚTILES
-- ============================================

-- Trigger: Actualizar costo_total en creaciones_terminadas
CREATE OR REPLACE FUNCTION actualizar_costo_total()
RETURNS TRIGGER AS $$
BEGIN
    NEW.costo_total = COALESCE(NEW.costo_materiales, 0) + COALESCE(NEW.costo_mano_obra, 0);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_actualizar_costo_total
BEFORE INSERT OR UPDATE ON creaciones_terminadas
FOR EACH ROW
EXECUTE FUNCTION actualizar_costo_total();

-- Trigger: Registrar movimiento al consumir materiales
CREATE OR REPLACE FUNCTION registrar_consumo_movimiento()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO movimientos (tipo_inventario, item_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id)
    VALUES (
        NEW.tipo_material,
        NEW.material_id,
        'salida',
        NEW.cantidad_consumida,
        'Consumo en producción',
        'OP-' || NEW.orden_produccion_id,
        NEW.usuario_id
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_registrar_consumo
AFTER INSERT ON consumo_materiales
FOR EACH ROW
EXECUTE FUNCTION registrar_consumo_movimiento();

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Inventario de oro con valor total
CREATE OR REPLACE VIEW v_inventario_oro_valorizado AS
SELECT 
    io.*,
    (io.peso_gramos * io.precio_gramo) AS valor_total,
    p.nombre AS proveedor_nombre
FROM inventario_oro io
LEFT JOIN proveedores p ON io.proveedor_id = p.id;

-- Vista: Stock bajo de insumos
CREATE OR REPLACE VIEW v_insumos_stock_bajo AS
SELECT 
    ii.*,
    p.nombre AS proveedor_nombre,
    p.telefono AS proveedor_telefono
FROM inventario_insumos ii
LEFT JOIN proveedores p ON ii.proveedor_id = p.id
WHERE ii.cantidad <= ii.stock_minimo;

-- Vista: Órdenes en proceso
CREATE OR REPLACE VIEW v_ordenes_activas AS
SELECT 
    op.*,
    pr.nombre AS producto_nombre,
    pr.codigo_sku,
    a.nombre || ' ' || a.apellido AS artesano_nombre,
    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - op.fecha_inicio))/3600 AS horas_transcurridas
FROM ordenes_produccion op
INNER JOIN productos pr ON op.producto_id = pr.id
LEFT JOIN artesanos a ON op.artesano_id = a.id
WHERE op.estado IN ('pendiente', 'en_proceso');

-- Vista: Resumen de creaciones por mes
CREATE OR REPLACE VIEW v_creaciones_por_mes AS
SELECT 
    TO_CHAR(fecha_terminado, 'YYYY-MM') AS mes,
    COUNT(*) AS total_piezas,
    SUM(costo_total) AS costo_total,
    SUM(CASE WHEN vendida THEN precio_venta_real ELSE 0 END) AS total_vendido,
    SUM(CASE WHEN vendida THEN (precio_venta_real - costo_total) ELSE 0 END) AS utilidad
FROM creaciones_terminadas
GROUP BY TO_CHAR(fecha_terminado, 'YYYY-MM')
ORDER BY mes DESC;

-- ============================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- ============================================

-- Insertar algunos proveedores de ejemplo
INSERT INTO proveedores (nombre, tipo, contacto, telefono, email) VALUES
('Oro Internacional SA', 'oro', 'Juan Pérez', '+57-300-1234567', 'contacto@orointernacional.com'),
('Insumos y Piedras Ltda', 'insumos', 'María González', '+57-301-7654321', 'ventas@insumos.com'),
('Maquinaria Industrial', 'maquinaria', 'Carlos Rodríguez', '+57-302-9876543', 'info@maquinaria.com');

-- Insertar algunos artesanos de ejemplo
INSERT INTO artesanos (nombre, apellido, especialidad, telefono, fecha_ingreso) VALUES
('Pedro', 'Martínez', 'Engaste y soldadura', '+57-310-1111111', '2020-01-15'),
('Ana', 'López', 'Diseño y pulido', '+57-311-2222222', '2021-03-20'),
('Luis', 'García', 'Fundición', '+57-312-3333333', '2019-06-10');

-- ============================================
-- COMENTARIOS EN TABLAS
-- ============================================

COMMENT ON TABLE inventario_oro IS 'Inventario de oro por tipo de quilate';
COMMENT ON TABLE inventario_maquinaria IS 'Registro de maquinaria y equipos';
COMMENT ON TABLE inventario_insumos IS 'Inventario de insumos (piedras, cadenas, etc.)';
COMMENT ON TABLE ordenes_produccion IS 'Órdenes de fabricación de productos';
COMMENT ON TABLE creaciones_terminadas IS 'Registro de piezas terminadas con costos y fechas';
COMMENT ON TABLE estadisticas_produccion IS 'Estadísticas pre-calculadas por periodo';

-- ============================================
-- FIN DEL SCRIPT
-- ============================================