-- ============================================================================
-- FUNCIONES: ARTESANO
-- ============================================================================
--
-- Funciones específicas para el módulo de artesano.
-- Permiten a los artesanos gestionar sus órdenes asignadas.
--
-- FUNCIONES INCLUIDAS:
--
-- GESTIÓN DE ÓRDENES:
-- - fun_obtener_ordenes_artesano(artesano_id, offset, limit)
-- - fun_actualizar_estado_orden(orden_id, estado_id)
--
-- CONSUMO DE MATERIALES:
-- - fun_registrar_consumo_oro(orden_id, inventario_oro_id, cantidad, usuario_id)
-- - fun_registrar_consumo_insumo(orden_id, inventario_insumos_id, cantidad, usuario_id)
-- - fun_obtener_consumos_orden(orden_id)
--
-- CREACIONES TERMINADAS:
-- - fun_registrar_creacion_terminada(orden_id, descripcion, peso_final, ...)
-- - fun_obtener_creaciones_artesano(artesano_id, offset, limit)
--
-- FLUJO TÍPICO:
-- 1. Artesano ve sus órdenes (fun_obtener_ordenes_artesano)
-- 2. Inicia trabajo (fun_actualizar_estado_orden -> en_proceso)
-- 3. Registra consumos (fun_registrar_consumo_*)
-- 4. Termina trabajo (fun_registrar_creacion_terminada)
-- 5. Cierra orden (fun_actualizar_estado_orden -> terminada)
--
-- SEGURIDAD:
-- El artesano solo puede ver/modificar sus propias órdenes.
-- La validación de propiedad se hace en PHP.
--
-- ============================================================================
-- FUNCIONES DE ARTESANO
-- Fecha: 2026-01-19
-- Funciones para el módulo de artesano
-- ============================================

SET search_path TO joyeria, seguridad, public;

-- ============================================
-- OBTENER ÓRDENES DE UN ARTESANO
-- ============================================

DROP FUNCTION IF EXISTS fun_obtener_ordenes_artesano(int, int, int);

CREATE OR REPLACE FUNCTION fun_obtener_ordenes_artesano(
    par_artesano_id int,
    par_offset int DEFAULT 0,
    par_limit int DEFAULT 50
)
RETURNS TABLE (
    id int,
    producto_id int,
    producto_nombre text,
    cantidad int,
    estado_id int,
    estado text,
    prioridad_id int,
    prioridad text,
    fecha_creacion timestamp,
    fecha_inicio timestamp,
    fecha_fin_estimada timestamp,
    fecha_fin_real timestamp,
    observaciones text
)
LANGUAGE plpgsql
STABLE
AS $$
BEGIN
    RETURN QUERY
    SELECT
        op.id,
        op.producto_id,
        p.nombre::text AS producto_nombre,
        op.cantidad,
        op.estado_id,
        eo.nombre::text AS estado,
        op.prioridad_id,
        pr.nombre::text AS prioridad,
        op.fecha_creacion,
        op.fecha_inicio,
        op.fecha_fin_estimada,
        op.fecha_fin_real,
        op.observaciones::text
    FROM ordenes_produccion op
    LEFT JOIN productos p ON op.producto_id = p.id
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    LEFT JOIN prioridades pr ON op.prioridad_id = pr.id
    WHERE op.artesano_id = par_artesano_id
      AND op.estado_id <> 3
    ORDER BY COALESCE(pr.orden, 99), op.fecha_creacion DESC
    OFFSET par_offset
    LIMIT par_limit;
END;
$$;

-- ============================================
-- ACTUALIZAR ESTADO DE ORDEN (ARTESANO)
-- Actualiza estado y fechas automáticamente
-- ============================================

DROP FUNCTION IF EXISTS fun_actualizar_estado_orden(int, int);

CREATE OR REPLACE FUNCTION fun_actualizar_estado_orden(
    par_orden_id int,
    par_estado_id int
)
RETURNS TABLE (
    success boolean,
    mensaje text,
    rows_affected int
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_estado_nombre text;
    v_rows int;
BEGIN
    -- Obtener nombre del estado
    SELECT LOWER(nombre) INTO v_estado_nombre
    FROM estados_orden
    WHERE id = par_estado_id;
    
    IF v_estado_nombre IS NULL THEN
        RETURN QUERY SELECT FALSE, 'Estado no encontrado'::text, 0;
        RETURN;
    END IF;
    
    -- Actualizar según el estado
    IF v_estado_nombre = 'en_proceso' THEN
        UPDATE ordenes_produccion
        SET estado_id = par_estado_id,
            fecha_inicio = COALESCE(fecha_inicio, CURRENT_TIMESTAMP)
        WHERE id = par_orden_id;
    ELSIF v_estado_nombre = 'terminada' THEN
        UPDATE ordenes_produccion
        SET estado_id = par_estado_id,
            fecha_fin_real = COALESCE(fecha_fin_real, CURRENT_TIMESTAMP)
        WHERE id = par_orden_id;
    ELSE
        UPDATE ordenes_produccion
        SET estado_id = par_estado_id
        WHERE id = par_orden_id;
    END IF;
    
    GET DIAGNOSTICS v_rows = ROW_COUNT;
    
    IF v_rows = 0 THEN
        RETURN QUERY SELECT FALSE, 'Orden no encontrada'::text, 0;
    ELSE
        RETURN QUERY SELECT TRUE, 'Estado actualizado'::text, v_rows;
    END IF;
END;
$$;

-- ============================================
-- REGISTRAR CONSUMO DE MATERIAL
-- Descuenta inventario, registra consumo y movimiento
-- ============================================

DROP FUNCTION IF EXISTS fun_registrar_consumo_material(int, text, int, numeric, int);

CREATE OR REPLACE FUNCTION fun_registrar_consumo_material(
    par_orden_id int,
    par_tipo_material text,  -- 'oro' o 'insumo'
    par_material_id int,
    par_cantidad numeric,
    par_usuario_id int DEFAULT NULL
)
RETURNS TABLE (
    success boolean,
    mensaje text,
    consumo_id int
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_stock_actual numeric;
    v_consumo_id int;
BEGIN
    -- Validar tipo de material
    IF par_tipo_material NOT IN ('oro', 'insumo') THEN
        RETURN QUERY SELECT FALSE, 'Tipo de material inválido'::text, NULL::int;
        RETURN;
    END IF;
    
    -- Validar cantidad
    IF par_cantidad <= 0 THEN
        RETURN QUERY SELECT FALSE, 'Cantidad debe ser mayor a 0'::text, NULL::int;
        RETURN;
    END IF;
    
    IF par_tipo_material = 'oro' THEN
        -- Verificar stock de oro
        SELECT peso_gramos INTO v_stock_actual
        FROM inventario_oro
        WHERE id = par_material_id AND activo = TRUE;
        
        IF v_stock_actual IS NULL THEN
            RETURN QUERY SELECT FALSE, 'Inventario de oro no encontrado'::text, NULL::int;
            RETURN;
        END IF;
        
        IF v_stock_actual < par_cantidad THEN
            RETURN QUERY SELECT FALSE, ('Stock insuficiente. Disponible: ' || v_stock_actual || 'g')::text, NULL::int;
            RETURN;
        END IF;
        
        -- Descontar del inventario
        UPDATE inventario_oro
        SET peso_gramos = peso_gramos - par_cantidad
        WHERE id = par_material_id;
        
        -- Registrar consumo
        INSERT INTO consumo_oro (orden_produccion_id, inventario_oro_id, cantidad_consumida, usuario_id)
        VALUES (par_orden_id, par_material_id, par_cantidad, par_usuario_id)
        RETURNING id INTO v_consumo_id;
        
        -- Registrar movimiento
        INSERT INTO movimientos_oro (inventario_oro_id, tipo_movimiento, cantidad, motivo, usuario_id)
        VALUES (par_material_id, 'consumo_produccion', -par_cantidad, 'Consumo orden #' || par_orden_id, par_usuario_id);
        
    ELSE
        -- Verificar stock de insumo
        SELECT cantidad INTO v_stock_actual
        FROM inventario_insumos
        WHERE id = par_material_id AND activo = TRUE;
        
        IF v_stock_actual IS NULL THEN
            RETURN QUERY SELECT FALSE, 'Insumo no encontrado'::text, NULL::int;
            RETURN;
        END IF;
        
        IF v_stock_actual < par_cantidad THEN
            RETURN QUERY SELECT FALSE, ('Stock insuficiente. Disponible: ' || v_stock_actual)::text, NULL::int;
            RETURN;
        END IF;
        
        -- Descontar del inventario
        UPDATE inventario_insumos
        SET cantidad = cantidad - par_cantidad
        WHERE id = par_material_id;
        
        -- Registrar consumo
        INSERT INTO consumo_insumos (orden_produccion_id, inventario_insumos_id, cantidad_consumida, usuario_id)
        VALUES (par_orden_id, par_material_id, par_cantidad, par_usuario_id)
        RETURNING id INTO v_consumo_id;
        
        -- Registrar movimiento
        INSERT INTO movimientos_insumos (inventario_insumos_id, tipo_movimiento, cantidad, motivo, usuario_id)
        VALUES (par_material_id, 'consumo_produccion', -par_cantidad, 'Consumo orden #' || par_orden_id, par_usuario_id);
    END IF;
    
    RETURN QUERY SELECT TRUE, 'Consumo registrado correctamente'::text, v_consumo_id;
END;
$$;

-- ============================================
-- REGISTRAR PIEZA TERMINADA
-- Crea registro en creaciones_terminadas y actualiza orden
-- ============================================

DROP FUNCTION IF EXISTS fun_registrar_pieza_terminada(int, numeric, numeric, int, text);

CREATE OR REPLACE FUNCTION fun_registrar_pieza_terminada(
    par_orden_id int,
    par_peso_final numeric,
    par_tiempo_real numeric DEFAULT NULL,
    par_calidad_id int DEFAULT NULL,
    par_observaciones text DEFAULT NULL
)
RETURNS TABLE (
    success boolean,
    mensaje text,
    creacion_id int,
    costo_materiales numeric
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_orden RECORD;
    v_costo_oro numeric;
    v_costo_insumos numeric;
    v_costo_total numeric;
    v_creacion_id int;
    v_estado_terminada_id int;
BEGIN
    -- Validar peso
    IF par_peso_final <= 0 THEN
        RETURN QUERY SELECT FALSE, 'Peso final debe ser mayor a 0'::text, NULL::int, NULL::numeric;
        RETURN;
    END IF;
    
    -- Obtener datos de la orden
    SELECT op.id, op.producto_id, op.artesano_id, op.estado_id, eo.nombre AS estado_nombre
    INTO v_orden
    FROM ordenes_produccion op
    LEFT JOIN estados_orden eo ON op.estado_id = eo.id
    WHERE op.id = par_orden_id;
    
    IF v_orden.id IS NULL THEN
        RETURN QUERY SELECT FALSE, 'Orden no encontrada'::text, NULL::int, NULL::numeric;
        RETURN;
    END IF;
    
    -- Calcular costo de oro consumido
    SELECT COALESCE(SUM(co.cantidad_consumida * io.precio_gramo), 0)
    INTO v_costo_oro
    FROM consumo_oro co
    JOIN inventario_oro io ON co.inventario_oro_id = io.id
    WHERE co.orden_produccion_id = par_orden_id;
    
    -- Calcular costo de insumos consumidos
    SELECT COALESCE(SUM(ci.cantidad_consumida * COALESCE(ii.precio_unitario, 0)), 0)
    INTO v_costo_insumos
    FROM consumo_insumos ci
    JOIN inventario_insumos ii ON ci.inventario_insumos_id = ii.id
    WHERE ci.orden_produccion_id = par_orden_id;
    
    v_costo_total := v_costo_oro + v_costo_insumos;
    
    -- Insertar creación terminada
    INSERT INTO creaciones_terminadas (
        orden_id, producto_id, artesano_id, peso_final_gramos,
        costo_materiales, tiempo_real_horas, calidad_id, observaciones
    )
    VALUES (
        par_orden_id, v_orden.producto_id, v_orden.artesano_id, par_peso_final,
        v_costo_total, par_tiempo_real, par_calidad_id, par_observaciones
    )
    RETURNING id INTO v_creacion_id;
    
    -- Obtener ID del estado "terminada"
    SELECT id INTO v_estado_terminada_id
    FROM estados_orden
    WHERE LOWER(nombre) = 'terminada'
    LIMIT 1;
    
    -- Actualizar estado de la orden
    IF v_estado_terminada_id IS NOT NULL THEN
        UPDATE ordenes_produccion
        SET estado_id = v_estado_terminada_id,
            fecha_fin_real = CURRENT_TIMESTAMP
        WHERE id = par_orden_id;
    END IF;
    
    RETURN QUERY SELECT TRUE, 'Pieza terminada registrada correctamente'::text, v_creacion_id, v_costo_total;
END;
$$;

-- ============================================
-- CALCULAR COSTO MATERIALES DE ORDEN
-- Función auxiliar para obtener costo total
-- ============================================

DROP FUNCTION IF EXISTS fun_calcular_costo_materiales_orden(int);

CREATE OR REPLACE FUNCTION fun_calcular_costo_materiales_orden(
    par_orden_id int
)
RETURNS numeric
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    v_total numeric;
BEGIN
    SELECT COALESCE(
        (SELECT SUM(co.cantidad_consumida * io.precio_gramo)
         FROM consumo_oro co
         JOIN inventario_oro io ON co.inventario_oro_id = io.id
         WHERE co.orden_produccion_id = par_orden_id), 0
    ) + COALESCE(
        (SELECT SUM(ci.cantidad_consumida * COALESCE(ii.precio_unitario, 0))
         FROM consumo_insumos ci
         JOIN inventario_insumos ii ON ci.inventario_insumos_id = ii.id
         WHERE ci.orden_produccion_id = par_orden_id), 0
    )
    INTO v_total;

    RETURN v_total;
END;
$$;
