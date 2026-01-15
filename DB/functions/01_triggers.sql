-- ============================================
-- TRIGGERS Y FUNCIONES AUXILIARES
-- ============================================

SET search_path TO joyeria, seguridad, public;

CREATE OR REPLACE FUNCTION registrar_consumo_oro_movimiento()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO movimientos_oro (inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id)
    VALUES (
        NEW.inventario_oro_id,
        'salida',
        NEW.cantidad_consumida,
        'Consumo en produccion',
        'OP-' || NEW.orden_produccion_id,
        NEW.usuario_id
    );
    RETURN NEW;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Movimiento no registrado.' USING ERRCODE = SQLSTATE;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_registrar_consumo_oro ON consumo_oro;
CREATE TRIGGER trg_registrar_consumo_oro
AFTER INSERT ON consumo_oro
FOR EACH ROW
EXECUTE FUNCTION registrar_consumo_oro_movimiento();

CREATE OR REPLACE FUNCTION registrar_consumo_insumos_movimiento()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO movimientos_insumos (inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id)
    VALUES (
        NEW.inventario_insumos_id,
        'salida',
        NEW.cantidad_consumida,
        'Consumo en produccion',
        'OP-' || NEW.orden_produccion_id,
        NEW.usuario_id
    );
    RETURN NEW;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Movimiento no registrado.' USING ERRCODE = SQLSTATE;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_registrar_consumo_insumos ON consumo_insumos;
CREATE TRIGGER trg_registrar_consumo_insumos
AFTER INSERT ON consumo_insumos
FOR EACH ROW
EXECUTE FUNCTION registrar_consumo_insumos_movimiento();

CREATE OR REPLACE FUNCTION registrar_movimiento_general_oro()
RETURNS TRIGGER AS $$
DECLARE
    v_user_id INTEGER;
    v_role_id INTEGER;
BEGIN
    SELECT u.id_usuario, u.rolid INTO v_user_id, v_role_id
    FROM seguridad.seg_usuario u
    WHERE u.id_usuario = NEW.usuario_id;

    INSERT INTO movimientos (
        tipo_inventario,
        item_id,
        tipo_movim,
        cantidad_movim,
        motivo_movim,
        ref_movim,
        id_user,
        id_role,
        fecha_movim
    )
    VALUES (
        'oro',
        NEW.inventario_oro_id,
        NEW.tipo_movimiento,
        NEW.cantidad,
        NEW.motivo,
        NEW.referencia,
        v_user_id,
        v_role_id,
        COALESCE(NEW.fecha, CURRENT_TIMESTAMP)
    );
    RETURN NEW;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Movimiento no registrado.' USING ERRCODE = SQLSTATE;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_registrar_movimiento_general_oro ON movimientos_oro;
CREATE TRIGGER trg_registrar_movimiento_general_oro
AFTER INSERT ON movimientos_oro
FOR EACH ROW
EXECUTE FUNCTION registrar_movimiento_general_oro();

CREATE OR REPLACE FUNCTION registrar_movimiento_general_insumos()
RETURNS TRIGGER AS $$
DECLARE
    v_user_id INTEGER;
    v_role_id INTEGER;
BEGIN
    SELECT u.id_usuario, u.rolid INTO v_user_id, v_role_id
    FROM seguridad.seg_usuario u
    WHERE u.id_usuario = NEW.usuario_id;

    INSERT INTO movimientos (
        tipo_inventario,
        item_id,
        tipo_movim,
        cantidad_movim,
        motivo_movim,
        ref_movim,
        id_user,
        id_role,
        fecha_movim
    )
    VALUES (
        'insumos',
        NEW.inventario_insumos_id,
        NEW.tipo_movimiento,
        NEW.cantidad,
        NEW.motivo,
        NEW.referencia,
        v_user_id,
        v_role_id,
        COALESCE(NEW.fecha, CURRENT_TIMESTAMP)
    );
    RETURN NEW;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Movimiento no registrado.' USING ERRCODE = SQLSTATE;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_registrar_movimiento_general_insumos ON movimientos_insumos;
CREATE TRIGGER trg_registrar_movimiento_general_insumos
AFTER INSERT ON movimientos_insumos
FOR EACH ROW
EXECUTE FUNCTION registrar_movimiento_general_insumos();

CREATE OR REPLACE FUNCTION registrar_movimiento_general_maquinaria()
RETURNS TRIGGER AS $$
DECLARE
    v_user_id INTEGER;
    v_role_id INTEGER;
BEGIN
    SELECT u.id_usuario, u.rolid INTO v_user_id, v_role_id
    FROM seguridad.seg_usuario u
    WHERE u.id_usuario = NEW.usuario_id;

    INSERT INTO movimientos (
        tipo_inventario,
        item_id,
        tipo_movim,
        cantidad_movim,
        motivo_movim,
        ref_movim,
        id_user,
        id_role,
        fecha_movim
    )
    VALUES (
        'maquinaria',
        NEW.inventario_maquinaria_id,
        NEW.tipo_movimiento,
        NEW.cantidad,
        NEW.motivo,
        NEW.referencia,
        v_user_id,
        v_role_id,
        COALESCE(NEW.fecha, CURRENT_TIMESTAMP)
    );
    RETURN NEW;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'Movimiento no registrado.' USING ERRCODE = SQLSTATE;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_registrar_movimiento_general_maquinaria ON movimientos_maquinaria;
CREATE TRIGGER trg_registrar_movimiento_general_maquinaria
AFTER INSERT ON movimientos_maquinaria
FOR EACH ROW
EXECUTE FUNCTION registrar_movimiento_general_maquinaria();
