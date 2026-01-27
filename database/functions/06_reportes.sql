-- ============================================================================
-- FUNCIONES: REPORTES
-- ============================================================================
--
-- Funciones para generar reportes y estadísticas del sistema.
-- Usadas por el módulo de reportes y el dashboard.
--
-- FUNCIONES INCLUIDAS:
--
-- PRODUCCIÓN:
-- - fun_reporte_produccion(desde, hasta): Órdenes en rango de fechas
-- - fun_reporte_eficiencia_artesanos(desde, hasta): Piezas por artesano
-- - fun_reporte_ordenes_estado(): Conteo de órdenes por estado
-- - fun_reporte_ordenes_dashboard(desde, hasta): KPIs de órdenes para dashboard
--
-- INVENTARIO:
-- - fun_reporte_inventario(): Resumen de stock actual
-- - fun_reporte_uso_materiales(desde, hasta): Consumo de materiales
--
-- VENTAS:
-- - fun_reporte_ventas(desde, hasta): Ventas en rango de fechas
--
-- COMPRAS:
-- - fun_reporte_compras(desde, hasta): Compras por tipo de inventario
--
-- USUARIOS:
-- - fun_reporte_usuarios(): Lista de usuarios con roles
--
-- PARÁMETROS COMUNES:
-- - par_desde (date): Fecha inicial del rango
-- - par_hasta (date): Fecha final del rango
--
-- NOTAS:
-- - Los reportes son de solo lectura (SELECT)
-- - Incluyen JOINs para mostrar nombres en lugar de IDs
-- - Manejan excepciones con mensajes genéricos
--
-- ============================================================================
-- FUNCIONES DE REPORTES
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION fun_reporte_produccion(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    id int,
    producto text,
    cantidad int,
    artesano text,
    estado text,
    fecha_inicio timestamp,
    fecha_fin_real timestamp
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        op.id,
        pr.nombre::text AS producto,
        op.cantidad,
        a.nombre || ' ' || a.apellido AS artesano,
        eo.nombre::text AS estado,
        op.fecha_inicio,
        op.fecha_fin_real
    FROM ordenes_produccion op
    INNER JOIN productos pr ON op.producto_id = pr.id
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    LEFT JOIN artesanos a ON op.artesano_id = a.id
    WHERE op.fecha_creacion::date BETWEEN par_desde AND par_hasta
    ORDER BY op.fecha_creacion DESC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_eficiencia_artesanos(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    artesano text,
    piezas int,
    horas numeric,
    promedio_horas numeric
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        a.nombre || ' ' || a.apellido AS artesano,
        COUNT(ct.id)::int AS piezas,
        COALESCE(SUM(ct.tiempo_real_horas), 0) AS horas,
        COALESCE(AVG(ct.tiempo_real_horas), 0) AS promedio_horas
    FROM creaciones_terminadas ct
    LEFT JOIN artesanos a ON ct.artesano_id = a.id
    WHERE ct.fecha_terminado::date BETWEEN par_desde AND par_hasta
    GROUP BY a.nombre, a.apellido
    ORDER BY piezas DESC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_uso_materiales(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    tipo_material text,
    material_id int,
    material_nombre text,
    cantidad_total numeric,
    costo_total numeric
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        x.tipo_material,
        x.material_id,
        x.material_nombre,
        x.cantidad_total,
        x.costo_total
    FROM (
        SELECT
            'oro'::text AS tipo_material,
            co.inventario_oro_id AS material_id,
            ('Oro ' || COALESCE(tor.nombre, 'Sin tipo'))::text AS material_nombre,
            SUM(co.cantidad_consumida) AS cantidad_total,
            SUM(co.cantidad_consumida * COALESCE(io.precio_gramo, 0)) AS costo_total
        FROM consumo_oro co
        INNER JOIN inventario_oro io ON co.inventario_oro_id = io.id
        LEFT JOIN tipos_oro tor ON io.tipo_oro_id = tor.id
        WHERE co.fecha_consumo::date BETWEEN par_desde AND par_hasta
        GROUP BY co.inventario_oro_id, tor.nombre
        UNION ALL
        SELECT
            'insumo'::text AS tipo_material,
            ci.inventario_insumos_id AS material_id,
            ii.nombre::text AS material_nombre,
            SUM(ci.cantidad_consumida) AS cantidad_total,
            SUM(ci.cantidad_consumida * COALESCE(ii.precio_unitario, 0)) AS costo_total
        FROM consumo_insumos ci
        INNER JOIN inventario_insumos ii ON ci.inventario_insumos_id = ii.id
        WHERE ci.fecha_consumo::date BETWEEN par_desde AND par_hasta
        GROUP BY ci.inventario_insumos_id, ii.nombre
    ) x
    ORDER BY x.cantidad_total DESC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_inventario()
RETURNS TABLE (
    tipo text,
    item_id int,
    nombre text,
    cantidad numeric,
    stock_minimo numeric,
    proveedor text
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        'insumo'::text AS tipo,
        ii.id AS item_id,
        ii.nombre::text,
        ii.cantidad,
        ii.stock_minimo,
        p.nombre::text AS proveedor
    FROM inventario_insumos ii
    LEFT JOIN proveedores p ON ii.proveedor_id = p.id
    WHERE ii.cantidad <= ii.stock_minimo
    ORDER BY ii.cantidad ASC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_ventas(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    id int,
    producto_id int,
    fecha_venta timestamp,
    precio_venta numeric,
    costo_total numeric,
    utilidad numeric
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        ct.id,
        ct.producto_id,
        ct.fecha_venta,
        ct.precio_venta_real AS precio_venta,
        (ct.costo_materiales + ct.costo_mano_obra) AS costo_total,
        (ct.precio_venta_real - (ct.costo_materiales + ct.costo_mano_obra)) AS utilidad
    FROM creaciones_terminadas ct
    WHERE ct.vendida = TRUE
      AND ct.fecha_venta::date BETWEEN par_desde AND par_hasta
    ORDER BY ct.fecha_venta DESC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_ordenes_estado()
RETURNS TABLE (
    estado text,
    total int
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        COALESCE(eo.nombre, 'sin_estado')::text AS estado,
        COUNT(1)::int AS total
    FROM ordenes_produccion op
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    GROUP BY COALESCE(eo.nombre, 'sin_estado')
    ORDER BY COALESCE(eo.nombre, 'sin_estado');
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_ordenes_dashboard(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    ordenes_activas int,
    ordenes_completadas int
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_activas int;
    v_completadas int;
BEGIN
    SELECT COUNT(1)::int
    INTO v_activas
    FROM ordenes_produccion op
    INNER JOIN estados_orden eo ON op.estado_id = eo.id
    WHERE eo.nombre NOT IN ('terminada', 'cancelada');

    SELECT COUNT(1)::int
    INTO v_completadas
    FROM ordenes_produccion op
    INNER JOIN estados_orden eo ON op.estado_id = eo.id
    WHERE eo.nombre = 'terminada'
      AND op.fecha_fin_real IS NOT NULL
      AND op.fecha_fin_real::date BETWEEN par_desde AND par_hasta;

    RETURN QUERY SELECT COALESCE(v_activas, 0), COALESCE(v_completadas, 0);
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION fun_reporte_compras(
    par_desde date,
    par_hasta date
)
RETURNS TABLE (
    tipo_inventario text,
    cantidad_total numeric,
    movimientos int
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        x.tipo_inventario,
        COALESCE(SUM(x.cantidad_total), 0) AS cantidad_total,
        COALESCE(SUM(x.movimientos), 0)::int AS movimientos
    FROM (
        SELECT
            'oro'::text AS tipo_inventario,
            COALESCE(SUM(mo.cantidad), 0) AS cantidad_total,
            COALESCE(COUNT(mo.id), 0)::int AS movimientos
        FROM movimientos_oro mo
        WHERE mo.tipo_movimiento = 'entrada'
          AND mo.fecha::date BETWEEN par_desde AND par_hasta
        UNION ALL
        SELECT
            'insumos'::text AS tipo_inventario,
            COALESCE(SUM(mi.cantidad), 0) AS cantidad_total,
            COALESCE(COUNT(mi.id), 0)::int AS movimientos
        FROM movimientos_insumos mi
        WHERE mi.tipo_movimiento = 'entrada'
          AND mi.fecha::date BETWEEN par_desde AND par_hasta
        UNION ALL
        SELECT
            'maquinaria'::text AS tipo_inventario,
            COALESCE(SUM(mm.cantidad), 0) AS cantidad_total,
            COALESCE(COUNT(mm.id), 0)::int AS movimientos
        FROM movimientos_maquinaria mm
        WHERE mm.tipo_movimiento = 'entrada'
          AND mm.fecha::date BETWEEN par_desde AND par_hasta
    ) x
    GROUP BY x.tipo_inventario
    ORDER BY x.tipo_inventario;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;

CREATE OR REPLACE FUNCTION seguridad.fun_reporte_usuarios()
RETURNS TABLE (
    id_usuario int,
    username text,
    nombre text,
    rol text,
    activo boolean,
    created_at timestamp
)
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT
        u.id_usuario,
        u.username,
        u.nombre,
        r.nombre AS rol,
        (u.deleted_at IS NULL) AS activo,
        u.created_at
    FROM seguridad.seg_usuario u
    INNER JOIN seguridad.seg_rol r ON u.rolid = r.id_rol
    ORDER BY u.created_at DESC;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Reporte no disponible.' USING ERRCODE = SQLSTATE;
END;
$$;
