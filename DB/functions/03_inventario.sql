-- ============================================
-- FUNCIONES DE INVENTARIO
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_oro(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_tipo text DEFAULT NULL
)
RETURNS TABLE (
    id int,
    tipo_oro text,
    peso_gramos numeric,
    precio_gramo numeric,
    proveedor_id int,
    fecha_ingreso date,
    ubicacion text,
    pureza numeric,
    lote text,
    fecha_registro timestamp,
    valor_total numeric,
    proveedor_nombre text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        io.id,
        io.tipo_oro,
        io.peso_gramos,
        io.precio_gramo,
        io.proveedor_id,
        io.fecha_ingreso,
        io.ubicacion,
        io.pureza,
        io.lote,
        io.fecha_registro,
        (io.peso_gramos * io.precio_gramo) AS valor_total,
        p.nombre AS proveedor_nombre
    FROM inventario_oro io
    LEFT JOIN proveedores p ON io.proveedor_id = p.id
    WHERE (par_tipo IS NULL OR io.tipo_oro = par_tipo)
    ORDER BY io.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_insumos(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_categoria text DEFAULT NULL,
    par_stock_bajo boolean DEFAULT FALSE
)
RETURNS TABLE (
    id int,
    nombre text,
    categoria text,
    descripcion text,
    cantidad numeric,
    unidad_medida text,
    precio_unitario numeric,
    stock_minimo numeric,
    proveedor_id int,
    ubicacion text,
    fecha_registro timestamp,
    proveedor_nombre text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        ii.id,
        ii.nombre,
        ii.categoria,
        ii.descripcion,
        ii.cantidad,
        ii.unidad_medida,
        ii.precio_unitario,
        ii.stock_minimo,
        ii.proveedor_id,
        ii.ubicacion,
        ii.fecha_registro,
        p.nombre AS proveedor_nombre
    FROM inventario_insumos ii
    LEFT JOIN proveedores p ON ii.proveedor_id = p.id
    WHERE (par_categoria IS NULL OR ii.categoria = par_categoria)
      AND (par_stock_bajo = FALSE OR ii.cantidad <= ii.stock_minimo)
    ORDER BY ii.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_maquinaria(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL
)
RETURNS TABLE (
    id int,
    nombre text,
    tipo text,
    marca text,
    modelo text,
    numero_serie text,
    fecha_compra date,
    valor_compra numeric,
    estado text,
    ultima_mantenimiento date,
    proxima_mantenimiento date,
    ubicacion text,
    fecha_registro timestamp
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        im.id,
        im.nombre,
        im.tipo,
        im.marca,
        im.modelo,
        im.numero_serie,
        im.fecha_compra,
        im.valor_compra,
        im.estado,
        im.ultima_mantenimiento,
        im.proxima_mantenimiento,
        im.ubicacion,
        im.fecha_registro
    FROM inventario_maquinaria im
    WHERE (par_estado IS NULL OR im.estado = par_estado)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;
