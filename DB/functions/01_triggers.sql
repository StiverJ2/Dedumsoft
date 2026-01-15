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
END;
$$ LANGUAGE plpgsql;

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
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_registrar_consumo_insumos
AFTER INSERT ON consumo_insumos
FOR EACH ROW
EXECUTE FUNCTION registrar_consumo_insumos_movimiento();
