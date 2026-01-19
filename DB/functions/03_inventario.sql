-- ============================================================================
-- FUNCIONES: INVENTARIO
-- ============================================================================
--
-- Funciones CRUD para los tres tipos de inventario:
-- - Inventario de Oro (inventario_oro)
-- - Inventario de Insumos (inventario_insumos)
-- - Inventario de Maquinaria (inventario_maquinaria)
--
-- FUNCIONES POR MÓDULO:
--
-- INVENTARIO ORO:
-- - fun_obtener_inventario_oro(offset, limit, tipo_id, activo)
-- - fun_crear_inventario_oro(tipo_oro_id, peso, precio, proveedor_id, ...)
-- - fun_actualizar_inventario_oro(id, tipo_oro_id, peso, precio, ...)
-- - fun_eliminar_inventario_oro(id) - Soft delete
--
-- INVENTARIO INSUMOS:
-- - fun_obtener_inventario_insumos(offset, limit, categoria, activo)
-- - fun_crear_insumo(nombre, cantidad, unidad, proveedor_id, ...)
-- - fun_actualizar_insumo(id, nombre, cantidad, ...)
-- - fun_eliminar_insumo(id) - Soft delete
--
-- INVENTARIO MAQUINARIA:
-- - fun_obtener_inventario_maquinaria(offset, limit, tipo_id, activo)
-- - fun_crear_maquinaria(nombre, tipo_id, marca, modelo, ...)
-- - fun_actualizar_maquinaria(id, nombre, estado_id, ...)
-- - fun_eliminar_maquinaria(id) - Soft delete
--
-- NOTAS:
-- - Todas las funciones usan IDs normalizados (no códigos)
-- - El campo 'activo' implementa soft-delete
-- - Los estados usan FK a tablas lookup
--
-- ============================================================================
-- FUNCIONES DE INVENTARIO
-- Fecha: 2026-01-18
-- Normalizado: estado_id en lugar de estado varchar
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- INVENTARIO DE ORO
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_inventario_oro(int, int, int, boolean);

CREATE OR REPLACE FUNCTION fun_obtener_inventario_oro(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_tipo_id int DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    tipo_oro_id int,
    tipo_oro_nombre text,
    peso_gramos numeric,
    precio_gramo numeric,
    proveedor_id int,
    proveedor_nombre text,
    fecha_ingreso date,
    ubicacion text,
    pureza numeric,
    fecha_registro timestamp,
    valor_total numeric,
    activo boolean
)
LANGUAGE sql
AS $$
    SELECT
        io.id,
        io.tipo_oro_id,
        t.nombre::text AS tipo_oro_nombre,
        io.peso_gramos,
        io.precio_gramo,
        io.proveedor_id,
        p.nombre::text AS proveedor_nombre,
        io.fecha_ingreso,
        io.ubicacion::text,
        io.pureza,
        io.fecha_registro,
        (io.peso_gramos * io.precio_gramo) AS valor_total,
        io.activo
    FROM inventario_oro io
    LEFT JOIN proveedores p ON io.proveedor_id = p.id
    LEFT JOIN tipos_oro t ON io.tipo_oro_id = t.id
    WHERE (par_tipo_id IS NULL OR io.tipo_oro_id = par_tipo_id)
      AND (par_activo IS NULL OR io.activo = par_activo)
    ORDER BY io.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
$$;

-- ============================================
-- INVENTARIO DE INSUMOS
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_inventario_insumos(int, int, text, boolean, boolean);

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
    proveedor_nombre text,
    ubicacion_id int,
    ubicacion_nombre text,
    fecha_registro timestamp,
    activo boolean
)
LANGUAGE sql
AS $$
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
        p.nombre::text AS proveedor_nombre,
        ii.ubicacion_id,
        u.nombre::text AS ubicacion_nombre,
        ii.fecha_registro,
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
$$;

-- ============================================
-- INVENTARIO DE MAQUINARIA (normalizado con estado_id)
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_inventario_maquinaria(int, int, text, boolean);
DROP FUNCTION IF EXISTS fun_obtener_inventario_maquinaria(int, int, int, boolean);

CREATE OR REPLACE FUNCTION fun_obtener_inventario_maquinaria(
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50,
    par_estado_id int DEFAULT NULL,
    par_activo boolean DEFAULT TRUE
)
RETURNS TABLE (
    id int,
    sku text,
    nombre text,
    tipo_maquinaria_id int,
    tipo_nombre text,
    marca text,
    modelo text,
    fecha_compra date,
    valor_compra numeric,
    estado_id int,
    estado_nombre text,
    estado_color text,
    ultima_mantenimiento date,
    proxima_mantenimiento date,
    ubicacion_id int,
    ubicacion_nombre text,
    fecha_registro timestamp,
    activo boolean
)
LANGUAGE sql
AS $$
    SELECT
        im.id,
        im.sku::text AS sku,
        im.nombre::text,
        im.tipo_maquinaria_id,
        tm.nombre::text AS tipo_nombre,
        im.marca::text,
        im.modelo::text,
        im.fecha_compra,
        im.valor_compra,
        im.estado_id,
        em.nombre::text AS estado_nombre,
        em.color::text AS estado_color,
        im.ultima_mantenimiento,
        im.proxima_mantenimiento,
        im.ubicacion_id,
        u.nombre::text AS ubicacion_nombre,
        im.fecha_registro,
        im.activo
    FROM inventario_maquinaria im
    LEFT JOIN ubicaciones u ON im.ubicacion_id = u.id
    LEFT JOIN tipos_maquinaria tm ON im.tipo_maquinaria_id = tm.id
    LEFT JOIN estados_maquinaria em ON im.estado_id = em.id
    WHERE (par_estado_id IS NULL OR im.estado_id = par_estado_id)
      AND (par_activo IS NULL OR im.activo = par_activo)
    ORDER BY im.fecha_registro DESC
    OFFSET par_offset
    LIMIT par_limit;
$$;

-- ============================================
-- OBTENER ESTADOS DE MAQUINARIA (para dropdowns)
-- ============================================

CREATE OR REPLACE FUNCTION fun_obtener_estados_maquinaria()
RETURNS TABLE (
    id int,
    nombre text,
    descripcion text,
    color text
)
LANGUAGE sql
AS $$
    SELECT em.id, em.nombre::text, em.descripcion::text, em.color::text
    FROM estados_maquinaria em
    WHERE em.activo = TRUE
    ORDER BY em.orden;
$$;

-- ============================================
-- CRUD INVENTARIO ORO
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_oro(
    par_tipo_oro_id int,
    par_peso_gramos numeric,
    par_precio_gramo numeric,
    par_proveedor_id int DEFAULT NULL,
    par_ubicacion text DEFAULT NULL,
    par_pureza numeric DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_oro (tipo_oro_id, peso_gramos, precio_gramo, proveedor_id, ubicacion, pureza)
    VALUES (par_tipo_oro_id, par_peso_gramos, par_precio_gramo, par_proveedor_id, par_ubicacion, par_pureza)
    RETURNING id INTO v_id;
    
    RETURN v_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_oro(
    par_id int,
    par_tipo_oro_id int DEFAULT NULL,
    par_peso_gramos numeric DEFAULT NULL,
    par_precio_gramo numeric DEFAULT NULL,
    par_proveedor_id int DEFAULT NULL,
    par_ubicacion text DEFAULT NULL,
    par_pureza numeric DEFAULT NULL,
    par_activo boolean DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_oro
    SET 
        tipo_oro_id = COALESCE(par_tipo_oro_id, tipo_oro_id),
        peso_gramos = COALESCE(par_peso_gramos, peso_gramos),
        precio_gramo = COALESCE(par_precio_gramo, precio_gramo),
        proveedor_id = COALESCE(par_proveedor_id, proveedor_id),
        ubicacion = COALESCE(par_ubicacion, ubicacion),
        pureza = COALESCE(par_pureza, pureza),
        activo = COALESCE(par_activo, activo)
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_oro(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_oro SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- CRUD INVENTARIO INSUMOS
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_insumo(
    par_nombre text,
    par_categoria text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_cantidad numeric DEFAULT 0,
    par_unidad_medida text DEFAULT 'unidad',
    par_precio_unitario numeric DEFAULT 0,
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
    INSERT INTO inventario_insumos (nombre, categoria, descripcion, cantidad, unidad_medida, precio_unitario, stock_minimo, proveedor_id, ubicacion_id)
    VALUES (par_nombre, par_categoria, par_descripcion, par_cantidad, par_unidad_medida, par_precio_unitario, par_stock_minimo, par_proveedor_id, par_ubicacion_id)
    RETURNING id INTO v_id;
    
    RETURN v_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_insumo(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_categoria text DEFAULT NULL,
    par_descripcion text DEFAULT NULL,
    par_cantidad numeric DEFAULT NULL,
    par_unidad_medida text DEFAULT NULL,
    par_precio_unitario numeric DEFAULT NULL,
    par_stock_minimo numeric DEFAULT NULL,
    par_proveedor_id int DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL,
    par_activo boolean DEFAULT NULL
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
        ubicacion_id = COALESCE(par_ubicacion_id, ubicacion_id),
        activo = COALESCE(par_activo, activo)
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_insumo(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_insumos SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- CRUD INVENTARIO MAQUINARIA (normalizado)
-- ============================================

CREATE OR REPLACE FUNCTION fun_crear_inventario_maquinaria(
    par_nombre text,
    par_sku text,
    par_tipo_maquinaria_id int DEFAULT NULL,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_fecha_compra date DEFAULT NULL,
    par_valor_compra numeric DEFAULT NULL,
    par_estado_id int DEFAULT 1,
    par_ubicacion_id int DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_id int;
BEGIN
    INSERT INTO inventario_maquinaria (nombre, sku, tipo_maquinaria_id, marca, modelo, fecha_compra, valor_compra, estado_id, ubicacion_id)
    VALUES (par_nombre, par_sku, par_tipo_maquinaria_id, par_marca, par_modelo, par_fecha_compra, par_valor_compra, par_estado_id, par_ubicacion_id)
    RETURNING id INTO v_id;
    
    RETURN v_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_actualizar_inventario_maquinaria(
    par_id int,
    par_nombre text DEFAULT NULL,
    par_sku text DEFAULT NULL,
    par_tipo_maquinaria_id int DEFAULT NULL,
    par_marca text DEFAULT NULL,
    par_modelo text DEFAULT NULL,
    par_fecha_compra date DEFAULT NULL,
    par_valor_compra numeric DEFAULT NULL,
    par_estado_id int DEFAULT NULL,
    par_ultima_mantenimiento date DEFAULT NULL,
    par_proxima_mantenimiento date DEFAULT NULL,
    par_ubicacion_id int DEFAULT NULL,
    par_activo boolean DEFAULT NULL
)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_maquinaria
    SET 
        nombre = COALESCE(par_nombre, nombre),
        sku = COALESCE(par_sku, sku),
        tipo_maquinaria_id = COALESCE(par_tipo_maquinaria_id, tipo_maquinaria_id),
        marca = COALESCE(par_marca, marca),
        modelo = COALESCE(par_modelo, modelo),
        fecha_compra = COALESCE(par_fecha_compra, fecha_compra),
        valor_compra = COALESCE(par_valor_compra, valor_compra),
        estado_id = COALESCE(par_estado_id, estado_id),
        ultima_mantenimiento = COALESCE(par_ultima_mantenimiento, ultima_mantenimiento),
        proxima_mantenimiento = COALESCE(par_proxima_mantenimiento, proxima_mantenimiento),
        ubicacion_id = COALESCE(par_ubicacion_id, ubicacion_id),
        activo = COALESCE(par_activo, activo)
    WHERE id = par_id;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_eliminar_inventario_maquinaria(par_id int)
RETURNS boolean
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE inventario_maquinaria SET activo = FALSE WHERE id = par_id AND activo = TRUE;
    
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Registro no encontrado.' USING ERRCODE = 'P0002';
    END IF;
    
    RETURN TRUE;
END;
$$;

-- ============================================
-- REGISTRAR COMPRAS (ENTRADAS)
-- ============================================

CREATE OR REPLACE FUNCTION fun_registrar_compra_oro(
    par_inventario_oro_id int,
    par_cantidad numeric,
    par_motivo text DEFAULT 'Compra proveedor',
    par_referencia text DEFAULT NULL,
    par_usuario_id int DEFAULT NULL,
    par_fecha timestamp DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_mov_id int;
BEGIN
    IF par_cantidad IS NULL OR par_cantidad <= 0 THEN
        RAISE EXCEPTION 'Cantidad invalida.' USING ERRCODE = '22000';
    END IF;

    UPDATE inventario_oro
    SET peso_gramos = peso_gramos + par_cantidad
    WHERE id = par_inventario_oro_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Inventario oro no encontrado.' USING ERRCODE = 'P0002';
    END IF;

    INSERT INTO movimientos_oro (inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
    VALUES (
        par_inventario_oro_id,
        'entrada',
        par_cantidad,
        COALESCE(par_motivo, 'Compra proveedor'),
        par_referencia,
        par_usuario_id,
        COALESCE(par_fecha, CURRENT_TIMESTAMP)
    )
    RETURNING id INTO v_mov_id;

    RETURN v_mov_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_registrar_compra_insumo(
    par_inventario_insumos_id int,
    par_cantidad numeric,
    par_motivo text DEFAULT 'Compra proveedor',
    par_referencia text DEFAULT NULL,
    par_usuario_id int DEFAULT NULL,
    par_fecha timestamp DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_mov_id int;
BEGIN
    IF par_cantidad IS NULL OR par_cantidad <= 0 THEN
        RAISE EXCEPTION 'Cantidad invalida.' USING ERRCODE = '22000';
    END IF;

    UPDATE inventario_insumos
    SET cantidad = cantidad + par_cantidad
    WHERE id = par_inventario_insumos_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Inventario insumos no encontrado.' USING ERRCODE = 'P0002';
    END IF;

    INSERT INTO movimientos_insumos (inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
    VALUES (
        par_inventario_insumos_id,
        'entrada',
        par_cantidad,
        COALESCE(par_motivo, 'Compra proveedor'),
        par_referencia,
        par_usuario_id,
        COALESCE(par_fecha, CURRENT_TIMESTAMP)
    )
    RETURNING id INTO v_mov_id;

    RETURN v_mov_id;
END;
$$;

CREATE OR REPLACE FUNCTION fun_registrar_compra_maquinaria(
    par_inventario_maquinaria_id int,
    par_cantidad int DEFAULT 1,
    par_motivo text DEFAULT 'Compra proveedor',
    par_referencia text DEFAULT NULL,
    par_usuario_id int DEFAULT NULL,
    par_fecha timestamp DEFAULT NULL
)
RETURNS int
LANGUAGE plpgsql
AS $$
DECLARE
    v_mov_id int;
BEGIN
    IF par_cantidad IS NULL OR par_cantidad <= 0 THEN
        RAISE EXCEPTION 'Cantidad invalida.' USING ERRCODE = '22000';
    END IF;

    PERFORM 1 FROM inventario_maquinaria WHERE id = par_inventario_maquinaria_id;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Inventario maquinaria no encontrado.' USING ERRCODE = 'P0002';
    END IF;

    INSERT INTO movimientos_maquinaria (inventario_maquinaria_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
    VALUES (
        par_inventario_maquinaria_id,
        'entrada',
        par_cantidad,
        COALESCE(par_motivo, 'Compra proveedor'),
        par_referencia,
        par_usuario_id,
        COALESCE(par_fecha, CURRENT_TIMESTAMP)
    )
    RETURNING id INTO v_mov_id;

    RETURN v_mov_id;
END;
$$;
