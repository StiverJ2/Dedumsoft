-- ============================================
-- FUNCIONES DE INVENTARIO
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_oro(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_tipo text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
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
    proveedor_nombre text,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        io.id,
        io.tipo_oro::text,
        io.peso_gramos,
        io.precio_gramo,
        io.proveedor_id,
        io.fecha_ingreso,
        io.ubicacion::text,
        io.pureza,
        io.lote::text,
        io.fecha_registro,
        (io.peso_gramos * io.precio_gramo) AS valor_total,
        p.nombre::text AS proveedor_nombre,
        io.activo
    FROM inventario_oro io
    LEFT JOIN proveedores p ON io.proveedor_id = p.id
    WHERE (par_tipo IS NULL OR io.tipo_oro = par_tipo)
      AND (par_activo IS NULL OR io.activo = par_activo)
    ORDER BY io.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de inventario no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_insumos(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_categoria text DEFAULT NULL,
    par_stock_bajo boolean DEFAULT FALSE,
    par_activo boolean DEFAULT TRUE
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
    ubicacion_id int,
    ubicacion_nombre text,
    fecha_registro timestamp,
    proveedor_nombre text,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        ii.id,
        ii.nombre::text,
        ii.categoria::text,
        ii.descripcion::text,
        ii.cantidad,
        ii.unidad_medida::text,
        ii.precio_unitario,
        ii.stock_minimo,
        ii.proveedor_id,
        ii.ubicacion_id,
        u.nombre::text AS ubicacion_nombre,
        ii.fecha_registro,
        p.nombre::text AS proveedor_nombre,
        ii.activo
    FROM inventario_insumos ii
    LEFT JOIN proveedores p ON ii.proveedor_id = p.id
    LEFT JOIN ubicaciones u ON ii.ubicacion_id = u.id
    WHERE (par_categoria IS NULL OR ii.categoria = par_categoria)
      AND (par_stock_bajo = FALSE OR ii.cantidad <= ii.stock_minimo)
      AND (par_activo IS NULL OR ii.activo = par_activo)
    ORDER BY ii.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de inventario no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_obtener_inventario_maquinaria(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado text DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
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
    ubicacion_id int,
    ubicacion_nombre text,
    fecha_registro timestamp,
    activo boolean
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        im.id,
        im.nombre::text,
        im.tipo::text,
        im.marca::text,
        im.modelo::text,
        im.numero_serie::text,
        im.fecha_compra,
        im.valor_compra,
        im.estado::text,
        im.ultima_mantenimiento,
        im.proxima_mantenimiento,
        im.ubicacion_id,
        u.nombre::text AS ubicacion_nombre,
        im.fecha_registro,
        im.activo
    FROM inventario_maquinaria im
    LEFT JOIN ubicaciones u ON im.ubicacion_id = u.id
    WHERE (par_estado IS NULL OR im.estado = par_estado)
      AND (par_activo IS NULL OR im.activo = par_activo)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Operacion de inventario no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- FUNCIONES CRUD PARA INVENTARIO_ORO
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_oro(
    par_tipo_oro text,
    par_peso_gramos numeric,
    par_precio_gramo numeric,
    par_proveedor_id int DEFAULT NULL,
    par_fecha_ingreso date DEFAULT CURRENT_DATE,
    par_ubicacion text DEFAULT NULL,
    par_pureza numeric DEFAULT NULL,
    par_lote text DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_oro (tipo_oro, peso_gramos, precio_gramo, proveedor_id, fecha_ingreso, ubicacion, pureza, lote, activo)
    VALUES (par_tipo_oro, par_peso_gramos, par_precio_gramo, par_proveedor_id, par_fecha_ingreso, par_ubicacion, par_pureza, par_lote, TRUE)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear registro de oro.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_oro(
    par_id int,
    par_tipo_oro text DEFAULT NULL,
    par_peso_gramos numeric DEFAULT NULL,
    par_precio_gramo numeric DEFAULT NULL,
    par_proveedor_id int DEFAULT NULL,
    par_fecha_ingreso date DEFAULT NULL,
    par_ubicacion text DEFAULT NULL,
    par_pureza numeric DEFAULT NULL,
    par_lote text DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_oro
    SET 
        tipo_oro = COALESCE(par_tipo_oro, tipo_oro),
        peso_gramos = COALESCE(par_peso_gramos, peso_gramos),
        precio_gramo = COALESCE(par_precio_gramo, precio_gramo),
        proveedor_id = COALESCE(par_proveedor_id, proveedor_id),
        fecha_ingreso = COALESCE(par_fecha_ingreso, fecha_ingreso),
        ubicacion = COALESCE(par_ubicacion, ubicacion),
        pureza = COALESCE(par_pureza, pureza),
        lote = COALESCE(par_lote, lote)
    WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro de oro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar registro de oro.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_oro(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_oro SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro de oro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar registro de oro.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- FUNCIONES CRUD PARA INVENTARIO_INSUMOS
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_insumos(
    par_nombre text,
    par_categoria text,
    par_unidad_medida text,
    par_precio_unitario numeric,
    par_descripcion text DEFAULT NULL,
    par_cantidad numeric DEFAULT 0,
    par_stock_minimo numeric DEFAULT 0,
    par_proveedor_id int DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_insumos (nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id, activo)
    VALUES (par_nombre, par_categoria, par_descripcion, par_cantidad, par_unidad_medida, par_precio_unitario, par_stock_minimo, par_proveedor_id, par_ubicacion_id, TRUE)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear insumo.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_insumos(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_categoria text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_cantidad numeric DEFAULT NULL,
    par_unidad_medida text DEFAULT NULL,
    par_precio_unitario numeric DEFAULT NULL,
    par_stock_minimo numeric DEFAULT NULL,
    par_proveedor_id int DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_insumos
    SET 
        nombre = COALESCE(par_nombre, nombre),
        categoria = COALESCE(par_categoria, categoria),
        descripcion = COALESCE(par_descripcion, descripcion),
        cantidad = COALESCE(par_cantidad, cantidad),
        unidad_medida = COALESCE(par_unidad_medida, unidad_medida),
        precio_unitario = COALESCE(par_precio_unitario, precio_unitario),
        stock_minimo = COALESCE(par_stock_minimo, stock_minimo),
        proveedor_id = COALESCE(par_proveedor_id, proveedor_id),
        ubicacion_id = COALESCE(par_ubicacion_id, ubicacion_id)
    WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Insumo no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar insumo.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_insumos(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_insumos SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Insumo no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar insumo.' USING ERRCODE = SQLSTATE;
END;
$$;

-- ============================================
-- FUNCIONES CRUD PARA INVENTARIO_MAQUINARIA
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_maquinaria(
    par_nombre text,
    par_tipo text,
    par_fecha_compra date,
    par_valor_compra numeric,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_numero_serie text DEFAULT NULL,
    par_estado text DEFAULT 'operativa',
    par_ultima_mantenimiento date DEFAULT NULL,
    par_proxima_mantenimiento date DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_maquinaria (nombre, tipo, marca, modelo, numero_serie, fecha_compra, valor_compra, estado, ultima_mantenimiento, proxima_mantenimiento, ubicacion_id, activo)
    VALUES (par_nombre, par_tipo, par_marca, par_modelo, par_numero_serie, par_fecha_compra, par_valor_compra, par_estado, par_ultima_mantenimiento, par_proxima_mantenimiento, par_ubicacion_id, TRUE)
    RETURNING id INTO v_id;
    
    RETURN v_id;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al crear maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_maquinaria(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_tipo text DEFAULT NULL,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_numero_serie text DEFAULT NULL,
    par_fecha_compra date DEFAULT NULL,
    par_valor_compra numeric DEFAULT NULL,
    par_estado text DEFAULT NULL,
    par_ultima_mantenimiento date DEFAULT NULL,
    par_proxima_mantenimiento date DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_maquinaria
    SET 
        nombre = COALESCE(par_nombre, nombre),
        tipo = COALESCE(par_tipo, tipo),
        marca = COALESCE(par_marca, marca),
        modelo = COALESCE(par_modelo, modelo),
        numero_serie = COALESCE(par_numero_serie, numero_serie),
        fecha_compra = COALESCE(par_fecha_compra, fecha_compra),
        valor_compra = COALESCE(par_valor_compra, valor_compra),
        estado = COALESCE(par_estado, estado),
        ultima_mantenimiento = COALESCE(par_ultima_mantenimiento, ultima_mantenimiento),
        proxima_mantenimiento = COALESCE(par_proxima_mantenimiento, proxima_mantenimiento),
        ubicacion_id = COALESCE(par_ubicacion_id, ubicacion_id)
    WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Maquinaria no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al actualizar maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_maquinaria(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_maquinaria SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Maquinaria no encontrada.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Error al eliminar maquinaria.' USING ERRCODE = SQLSTATE;
END;
$$;
