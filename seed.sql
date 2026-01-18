-- ============================================
-- SEED DATA (NO SEGURIDAD SCHEMA)
-- ============================================

BEGIN;
SET search_path TO joyeria, public;

-- ============================================
-- PROVEEDORES
-- ============================================
INSERT INTO proveedores (nombre, tipo, contacto, telefono, email, direccion, activo)
SELECT
    'Oro Andino SA',
    'oro',
    'Juan Perez',
    '+57-300-1111111',
    'ventas@oroandino.com',
    'Calle 10 #20-30',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM proveedores WHERE nombre = 'Oro Andino SA' AND tipo = 'oro'
);

INSERT INTO proveedores (nombre, tipo, contacto, telefono, email, direccion, activo)
SELECT
    'Piedras del Norte',
    'insumos',
    'Maria Lopez',
    '+57-301-2222222',
    'contacto@piedrasnorte.com',
    'Carrera 15 #45-20',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM proveedores WHERE nombre = 'Piedras del Norte' AND tipo = 'insumos'
);

INSERT INTO proveedores (nombre, tipo, contacto, telefono, email, direccion, activo)
SELECT
    'Maquinaria Central',
    'maquinaria',
    'Carlos Ruiz',
    '+57-302-3333333',
    'ventas@maqcentral.com',
    'Zona Industrial Bodega 8',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM proveedores WHERE nombre = 'Maquinaria Central' AND tipo = 'maquinaria'
);

-- ============================================
-- ARTESANOS
-- ============================================
INSERT INTO artesanos (nombre, apellido, telefono, email, fecha_ingreso, activo)
SELECT
    'Pedro',
    'Martinez',
    '+57-310-1111111',
    'pedro.martinez@taller.com',
    '2020-01-15',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM artesanos WHERE nombre = 'Pedro' AND apellido = 'Martinez'
);

INSERT INTO artesanos (nombre, apellido, telefono, email, fecha_ingreso, activo)
SELECT
    'Ana',
    'Lopez',
    '+57-311-2222222',
    'ana.lopez@taller.com',
    '2021-03-20',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM artesanos WHERE nombre = 'Ana' AND apellido = 'Lopez'
);

INSERT INTO artesanos (nombre, apellido, telefono, email, fecha_ingreso, activo)
SELECT
    'Luis',
    'Garcia',
    '+57-312-3333333',
    'luis.garcia@taller.com',
    '2019-06-10',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM artesanos WHERE nombre = 'Luis' AND apellido = 'Garcia'
);

INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Fundicion'
FROM artesanos a
WHERE a.nombre = 'Pedro' AND a.apellido = 'Martinez'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Fundicion'
  );

INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Pulido'
FROM artesanos a
WHERE a.nombre = 'Ana' AND a.apellido = 'Lopez'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Pulido'
  );

INSERT INTO artesano_especialidad (artesano_id, especialidad)
SELECT a.id, 'Engaste'
FROM artesanos a
WHERE a.nombre = 'Luis' AND a.apellido = 'Garcia'
  AND NOT EXISTS (
      SELECT 1 FROM artesano_especialidad ae
      WHERE ae.artesano_id = a.id AND ae.especialidad = 'Engaste'
  );

-- ============================================
-- UBICACIONES
-- ============================================
INSERT INTO ubicaciones (nombre, area)
SELECT 'estante A', 'Bodega'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE nombre = 'estante A');

INSERT INTO ubicaciones (nombre, area)
SELECT 'estante B', 'Bodega'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE nombre = 'estante B');

INSERT INTO ubicaciones (nombre, area)
SELECT 'taller', 'Taller'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE nombre = 'taller');

INSERT INTO ubicaciones (nombre, area)
SELECT 'taller 1', 'Taller'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE nombre = 'taller 1');

INSERT INTO ubicaciones (nombre, area)
SELECT 'taller 2', 'Taller'
WHERE NOT EXISTS (SELECT 1 FROM ubicaciones WHERE nombre = 'taller 2');

-- ============================================
-- INVENTARIO ORO
-- ============================================
INSERT INTO inventario_oro (
    tipo_oro,
    peso_gramos,
    precio_gramo,
    proveedor_id,
    fecha_ingreso,
    ubicacion,
    pureza,
    lote
)
SELECT
    '18k',
    250.000,
    210.00,
    p.id,
    (CURRENT_DATE - INTERVAL '45 days')::date,
    'boveda A',
    75.00,
    'L2026-01'
FROM proveedores p
WHERE p.nombre = 'Oro Andino SA' AND p.tipo = 'oro'
  AND NOT EXISTS (SELECT 1 FROM inventario_oro WHERE lote = 'L2026-01');

INSERT INTO inventario_oro (
    tipo_oro,
    peso_gramos,
    precio_gramo,
    proveedor_id,
    fecha_ingreso,
    ubicacion,
    pureza,
    lote
)
SELECT
    '14k',
    180.000,
    165.00,
    p.id,
    (CURRENT_DATE - INTERVAL '30 days')::date,
    'boveda A',
    58.50,
    'L2026-02'
FROM proveedores p
WHERE p.nombre = 'Oro Andino SA' AND p.tipo = 'oro'
  AND NOT EXISTS (SELECT 1 FROM inventario_oro WHERE lote = 'L2026-02');

INSERT INTO inventario_oro (
    tipo_oro,
    peso_gramos,
    precio_gramo,
    proveedor_id,
    fecha_ingreso,
    ubicacion,
    pureza,
    lote
)
SELECT
    '24k',
    120.000,
    285.00,
    p.id,
    (CURRENT_DATE - INTERVAL '10 days')::date,
    'boveda B',
    99.90,
    'L2026-03'
FROM proveedores p
WHERE p.nombre = 'Oro Andino SA' AND p.tipo = 'oro'
  AND NOT EXISTS (SELECT 1 FROM inventario_oro WHERE lote = 'L2026-03');

-- ============================================
-- INVENTARIO INSUMOS
-- ============================================
INSERT INTO inventario_insumos (
    nombre,
    categoria,
    descripcion,
    cantidad,
    unidad_medida,
    precio_unitario,
    stock_minimo,
    proveedor_id,
    ubicacion_id
)
SELECT
    'Diamante 1mm',
    'piedras',
    'Diamante sintetico para engaste fino',
    250.000,
    'pieza',
    15.50,
    50.000,
    p.id,
    u.id
FROM proveedores p
JOIN ubicaciones u ON u.nombre = 'estante A'
WHERE p.nombre = 'Piedras del Norte' AND p.tipo = 'insumos'
  AND NOT EXISTS (
      SELECT 1 FROM inventario_insumos WHERE nombre = 'Diamante 1mm' AND categoria = 'piedras'
  );

INSERT INTO inventario_insumos (
    nombre,
    categoria,
    descripcion,
    cantidad,
    unidad_medida,
    precio_unitario,
    stock_minimo,
    proveedor_id,
    ubicacion_id
)
SELECT
    'Zirconia 3mm',
    'piedras',
    'Zirconia blanca para pendientes',
    80.000,
    'pieza',
    2.50,
    30.000,
    p.id,
    u.id
FROM proveedores p
JOIN ubicaciones u ON u.nombre = 'estante A'
WHERE p.nombre = 'Piedras del Norte' AND p.tipo = 'insumos'
  AND NOT EXISTS (
      SELECT 1 FROM inventario_insumos WHERE nombre = 'Zirconia 3mm' AND categoria = 'piedras'
  );

INSERT INTO inventario_insumos (
    nombre,
    categoria,
    descripcion,
    cantidad,
    unidad_medida,
    precio_unitario,
    stock_minimo,
    proveedor_id,
    ubicacion_id
)
SELECT
    'Cadena plata 45cm',
    'cadenas',
    'Cadena de plata para collares',
    8.000,
    'pieza',
    35.00,
    10.000,
    p.id,
    u.id
FROM proveedores p
JOIN ubicaciones u ON u.nombre = 'estante B'
WHERE p.nombre = 'Piedras del Norte' AND p.tipo = 'insumos'
  AND NOT EXISTS (
      SELECT 1 FROM inventario_insumos WHERE nombre = 'Cadena plata 45cm' AND categoria = 'cadenas'
  );

INSERT INTO inventario_insumos (
    nombre,
    categoria,
    descripcion,
    cantidad,
    unidad_medida,
    precio_unitario,
    stock_minimo,
    proveedor_id,
    ubicacion_id
)
SELECT
    'Cera modelado',
    'consumibles',
    'Cera para prototipos',
    4.000,
    'kg',
    120.00,
    2.000,
    p.id,
    u.id
FROM proveedores p
JOIN ubicaciones u ON u.nombre = 'taller'
WHERE p.nombre = 'Piedras del Norte' AND p.tipo = 'insumos'
  AND NOT EXISTS (
      SELECT 1 FROM inventario_insumos WHERE nombre = 'Cera modelado' AND categoria = 'consumibles'
  );

-- ============================================
-- INVENTARIO MAQUINARIA
-- ============================================
INSERT INTO inventario_maquinaria (
    nombre,
    tipo,
    marca,
    modelo,
    numero_serie,
    fecha_compra,
    valor_compra,
    estado,
    ultima_mantenimiento,
    proxima_mantenimiento,
    ubicacion_id
)
SELECT
    'Horno de fundicion',
    'fundicion',
    'GoldHeat',
    'GH-200',
    'GH200-001',
    (CURRENT_DATE - INTERVAL '400 days')::date,
    8500.00,
    'operativa',
    (CURRENT_DATE - INTERVAL '30 days')::date,
    (CURRENT_DATE + INTERVAL '150 days')::date,
    u.id
FROM ubicaciones u
WHERE u.nombre = 'taller 1'
  AND NOT EXISTS (
    SELECT 1 FROM inventario_maquinaria WHERE numero_serie = 'GH200-001'
);

INSERT INTO inventario_maquinaria (
    nombre,
    tipo,
    marca,
    modelo,
    numero_serie,
    fecha_compra,
    valor_compra,
    estado,
    ultima_mantenimiento,
    proxima_mantenimiento,
    ubicacion_id
)
SELECT
    'Pulidora industrial',
    'acabado',
    'ShinePro',
    'SP-55',
    'SP55-014',
    (CURRENT_DATE - INTERVAL '300 days')::date,
    4200.00,
    'operativa',
    (CURRENT_DATE - INTERVAL '20 days')::date,
    (CURRENT_DATE + INTERVAL '120 days')::date,
    u.id
FROM ubicaciones u
WHERE u.nombre = 'taller 2'
  AND NOT EXISTS (
    SELECT 1 FROM inventario_maquinaria WHERE numero_serie = 'SP55-014'
);

-- ============================================
-- MOVIMIENTOS (ENTRADAS PARA REPORTES DE COMPRAS)
-- ============================================
INSERT INTO movimientos_oro (inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
SELECT
    o.id,
    'entrada',
    300.000,
    'Compra proveedor',
    'FAC-1001',
    NULL,
    (CURRENT_TIMESTAMP - INTERVAL '40 days')
FROM inventario_oro o
WHERE o.lote = 'L2026-01'
  AND NOT EXISTS (
      SELECT 1 FROM movimientos_oro mo
      WHERE mo.inventario_oro_id = o.id AND mo.referencia = 'FAC-1001'
  );

INSERT INTO movimientos_oro (inventario_oro_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
SELECT
    o.id,
    'entrada',
    200.000,
    'Compra proveedor',
    'FAC-1002',
    NULL,
    (CURRENT_TIMESTAMP - INTERVAL '20 days')
FROM inventario_oro o
WHERE o.lote = 'L2026-02'
  AND NOT EXISTS (
      SELECT 1 FROM movimientos_oro mo
      WHERE mo.inventario_oro_id = o.id AND mo.referencia = 'FAC-1002'
  );

INSERT INTO movimientos_insumos (inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
SELECT
    i.id,
    'entrada',
    100.000,
    'Compra proveedor',
    'FAC-2001',
    NULL,
    (CURRENT_TIMESTAMP - INTERVAL '25 days')
FROM inventario_insumos i
WHERE i.nombre = 'Cadena plata 45cm'
  AND NOT EXISTS (
      SELECT 1 FROM movimientos_insumos mi
      WHERE mi.inventario_insumos_id = i.id AND mi.referencia = 'FAC-2001'
  );

INSERT INTO movimientos_insumos (inventario_insumos_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
SELECT
    i.id,
    'entrada',
    200.000,
    'Compra proveedor',
    'FAC-2002',
    NULL,
    (CURRENT_TIMESTAMP - INTERVAL '15 days')
FROM inventario_insumos i
WHERE i.nombre = 'Zirconia 3mm'
  AND NOT EXISTS (
      SELECT 1 FROM movimientos_insumos mi
      WHERE mi.inventario_insumos_id = i.id AND mi.referencia = 'FAC-2002'
  );

INSERT INTO movimientos_maquinaria (inventario_maquinaria_id, tipo_movimiento, cantidad, motivo, referencia, usuario_id, fecha)
SELECT
    m.id,
    'entrada',
    1.000,
    'Compra proveedor',
    'FAC-3001',
    NULL,
    (CURRENT_TIMESTAMP - INTERVAL '200 days')
FROM inventario_maquinaria m
WHERE m.numero_serie = 'GH200-001'
  AND NOT EXISTS (
      SELECT 1 FROM movimientos_maquinaria mm
      WHERE mm.inventario_maquinaria_id = m.id AND mm.referencia = 'FAC-3001'
  );

-- ============================================
-- PRODUCTOS
-- ============================================
INSERT INTO productos (
    nombre,
    codigo_sku,
    tipo,
    descripcion,
    tiempo_fabricacion_horas,
    precio_venta,
    imagen_url,
    activo
)
SELECT
    'Anillo Sol',
    'ANILLO-SOL-01',
    'anillo',
    'Anillo clasico con piedra central',
    6.50,
    1200.00,
    'https://example.com/anillo-sol.jpg',
    TRUE
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo_sku = 'ANILLO-SOL-01');

INSERT INTO productos (
    nombre,
    codigo_sku,
    tipo,
    descripcion,
    tiempo_fabricacion_horas,
    precio_venta,
    imagen_url,
    activo
)
SELECT
    'Collar Luna',
    'COLLAR-LUNA-01',
    'collar',
    'Collar delicado con acabado mate',
    8.00,
    1800.00,
    'https://example.com/collar-luna.jpg',
    TRUE
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo_sku = 'COLLAR-LUNA-01');

INSERT INTO productos (
    nombre,
    codigo_sku,
    tipo,
    descripcion,
    tiempo_fabricacion_horas,
    precio_venta,
    imagen_url,
    activo
)
SELECT
    'Aretes Brisa',
    'ARETES-BRISA-01',
    'aretes',
    'Aretes livianos con zirconia',
    3.00,
    650.00,
    'https://example.com/aretes-brisa.jpg',
    TRUE
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE codigo_sku = 'ARETES-BRISA-01');

-- ============================================
-- RECETAS ORO / INSUMOS
-- ============================================
INSERT INTO recetas_oro (producto_id, inventario_oro_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, o.id, 5.000, FALSE, 'Base del anillo'
FROM productos p
JOIN inventario_oro o ON o.lote = 'L2026-01'
WHERE p.codigo_sku = 'ANILLO-SOL-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_oro r
      WHERE r.producto_id = p.id AND r.inventario_oro_id = o.id
  );

INSERT INTO recetas_oro (producto_id, inventario_oro_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, o.id, 8.000, FALSE, 'Cadena principal'
FROM productos p
JOIN inventario_oro o ON o.lote = 'L2026-02'
WHERE p.codigo_sku = 'COLLAR-LUNA-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_oro r
      WHERE r.producto_id = p.id AND r.inventario_oro_id = o.id
  );

INSERT INTO recetas_oro (producto_id, inventario_oro_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, o.id, 2.500, FALSE, 'Ganchos'
FROM productos p
JOIN inventario_oro o ON o.lote = 'L2026-02'
WHERE p.codigo_sku = 'ARETES-BRISA-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_oro r
      WHERE r.producto_id = p.id AND r.inventario_oro_id = o.id
  );

INSERT INTO recetas_insumos (producto_id, inventario_insumos_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, i.id, 1.000, FALSE, 'Piedra central'
FROM productos p
JOIN inventario_insumos i ON i.nombre = 'Diamante 1mm'
WHERE p.codigo_sku = 'ANILLO-SOL-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_insumos r
      WHERE r.producto_id = p.id AND r.inventario_insumos_id = i.id
  );

INSERT INTO recetas_insumos (producto_id, inventario_insumos_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, i.id, 1.000, FALSE, 'Cadena'
FROM productos p
JOIN inventario_insumos i ON i.nombre = 'Cadena plata 45cm'
WHERE p.codigo_sku = 'COLLAR-LUNA-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_insumos r
      WHERE r.producto_id = p.id AND r.inventario_insumos_id = i.id
  );

INSERT INTO recetas_insumos (producto_id, inventario_insumos_id, cantidad_requerida, es_opcional, notas)
SELECT p.id, i.id, 2.000, FALSE, 'Piedras laterales'
FROM productos p
JOIN inventario_insumos i ON i.nombre = 'Zirconia 3mm'
WHERE p.codigo_sku = 'ARETES-BRISA-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_insumos r
      WHERE r.producto_id = p.id AND r.inventario_insumos_id = i.id
  );

-- ============================================
-- RECETAS PRODUCCION (NUEVA TABLA)
-- ============================================
INSERT INTO recetas_produccion (
    tipo_material_recetas,
    id_oro,
    id_productos,
    cantidad_requerida,
    es_opcional,
    notas
)
SELECT
    'oro',
    o.id,
    p.id,
    5.000,
    FALSE,
    'Base anillo (receta_produccion)'
FROM inventario_oro o
JOIN productos p ON p.codigo_sku = 'ANILLO-SOL-01'
WHERE o.lote = 'L2026-01'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_produccion rp
      WHERE rp.id_productos = p.id AND rp.tipo_material_recetas = 'oro' AND rp.id_oro = o.id
  );

INSERT INTO recetas_produccion (
    tipo_material_recetas,
    id_insumos,
    id_productos,
    cantidad_requerida,
    es_opcional,
    notas
)
SELECT
    'insumo',
    i.id,
    p.id,
    1.000,
    FALSE,
    'Piedra central (receta_produccion)'
FROM inventario_insumos i
JOIN productos p ON p.codigo_sku = 'ANILLO-SOL-01'
WHERE i.nombre = 'Diamante 1mm'
  AND NOT EXISTS (
      SELECT 1 FROM recetas_produccion rp
      WHERE rp.id_productos = p.id AND rp.tipo_material_recetas = 'insumo' AND rp.id_insumos = i.id
  );

-- ============================================
-- ORDENES DE PRODUCCION
-- ============================================
INSERT INTO ordenes_produccion (
    codigo_orden,
    producto_id,
    cantidad,
    fecha_creacion,
    fecha_inicio,
    fecha_fin_estimada,
    fecha_fin_real,
    artesano_id,
    estado,
    prioridad,
    observaciones,
    creado_por
)
SELECT
    'OP-2026-001',
    p.id,
    10,
    (CURRENT_TIMESTAMP - INTERVAL '25 days'),
    (CURRENT_TIMESTAMP - INTERVAL '23 days'),
    (CURRENT_TIMESTAMP - INTERVAL '10 days'),
    NULL,
    a.id,
    'en_proceso',
    'alta',
    'Pedido vitrina',
    NULL
FROM productos p
JOIN artesanos a ON a.nombre = 'Pedro' AND a.apellido = 'Martinez'
WHERE p.codigo_sku = 'ANILLO-SOL-01'
  AND NOT EXISTS (SELECT 1 FROM ordenes_produccion WHERE codigo_orden = 'OP-2026-001');

INSERT INTO ordenes_produccion (
    codigo_orden,
    producto_id,
    cantidad,
    fecha_creacion,
    fecha_inicio,
    fecha_fin_estimada,
    fecha_fin_real,
    artesano_id,
    estado,
    prioridad,
    observaciones,
    creado_por
)
SELECT
    'OP-2026-002',
    p.id,
    5,
    (CURRENT_TIMESTAMP - INTERVAL '45 days'),
    (CURRENT_TIMESTAMP - INTERVAL '43 days'),
    (CURRENT_TIMESTAMP - INTERVAL '30 days'),
    (CURRENT_TIMESTAMP - INTERVAL '24 days'),
    a.id,
    'terminada',
    'media',
    'Produccion para pedido online',
    NULL
FROM productos p
JOIN artesanos a ON a.nombre = 'Ana' AND a.apellido = 'Lopez'
WHERE p.codigo_sku = 'COLLAR-LUNA-01'
  AND NOT EXISTS (SELECT 1 FROM ordenes_produccion WHERE codigo_orden = 'OP-2026-002');

INSERT INTO ordenes_produccion (
    codigo_orden,
    producto_id,
    cantidad,
    fecha_creacion,
    fecha_inicio,
    fecha_fin_estimada,
    fecha_fin_real,
    artesano_id,
    estado,
    prioridad,
    observaciones,
    creado_por
)
SELECT
    'OP-2026-003',
    p.id,
    12,
    (CURRENT_TIMESTAMP - INTERVAL '18 days'),
    (CURRENT_TIMESTAMP - INTERVAL '16 days'),
    (CURRENT_TIMESTAMP - INTERVAL '12 days'),
    (CURRENT_TIMESTAMP - INTERVAL '11 days'),
    a.id,
    'terminada',
    'media',
    'Pedido minorista',
    NULL
FROM productos p
JOIN artesanos a ON a.nombre = 'Luis' AND a.apellido = 'Garcia'
WHERE p.codigo_sku = 'ARETES-BRISA-01'
  AND NOT EXISTS (SELECT 1 FROM ordenes_produccion WHERE codigo_orden = 'OP-2026-003');

-- ============================================
-- CONSUMO DE MATERIALES (CONSULTAS DE REPORTES)
-- ============================================
INSERT INTO consumo_oro (
    orden_produccion_id,
    inventario_oro_id,
    cantidad_consumida,
    costo_unitario,
    fecha_consumo,
    usuario_id
)
SELECT
    op.id,
    o.id,
    40.000,
    210.00,
    (op.fecha_inicio + INTERVAL '1 day'),
    NULL
FROM ordenes_produccion op
JOIN inventario_oro o ON o.lote = 'L2026-01'
WHERE op.codigo_orden = 'OP-2026-001'
  AND NOT EXISTS (
      SELECT 1 FROM consumo_oro c
      WHERE c.orden_produccion_id = op.id AND c.inventario_oro_id = o.id
  );

INSERT INTO consumo_oro (
    orden_produccion_id,
    inventario_oro_id,
    cantidad_consumida,
    costo_unitario,
    fecha_consumo,
    usuario_id
)
SELECT
    op.id,
    o.id,
    55.000,
    165.00,
    (op.fecha_inicio + INTERVAL '1 day'),
    NULL
FROM ordenes_produccion op
JOIN inventario_oro o ON o.lote = 'L2026-02'
WHERE op.codigo_orden = 'OP-2026-002'
  AND NOT EXISTS (
      SELECT 1 FROM consumo_oro c
      WHERE c.orden_produccion_id = op.id AND c.inventario_oro_id = o.id
  );

INSERT INTO consumo_insumos (
    orden_produccion_id,
    inventario_insumos_id,
    cantidad_consumida,
    costo_unitario,
    fecha_consumo,
    usuario_id
)
SELECT
    op.id,
    i.id,
    20.000,
    2.50,
    (op.fecha_inicio + INTERVAL '2 days'),
    NULL
FROM ordenes_produccion op
JOIN inventario_insumos i ON i.nombre = 'Zirconia 3mm'
WHERE op.codigo_orden = 'OP-2026-001'
  AND NOT EXISTS (
      SELECT 1 FROM consumo_insumos c
      WHERE c.orden_produccion_id = op.id AND c.inventario_insumos_id = i.id
  );

INSERT INTO consumo_insumos (
    orden_produccion_id,
    inventario_insumos_id,
    cantidad_consumida,
    costo_unitario,
    fecha_consumo,
    usuario_id
)
SELECT
    op.id,
    i.id,
    5.000,
    35.00,
    (op.fecha_inicio + INTERVAL '2 days'),
    NULL
FROM ordenes_produccion op
JOIN inventario_insumos i ON i.nombre = 'Cadena plata 45cm'
WHERE op.codigo_orden = 'OP-2026-002'
  AND NOT EXISTS (
      SELECT 1 FROM consumo_insumos c
      WHERE c.orden_produccion_id = op.id AND c.inventario_insumos_id = i.id
  );

-- ============================================
-- CREACIONES TERMINADAS
-- ============================================
INSERT INTO creaciones_terminadas (
    orden_produccion_id,
    producto_id,
    codigo_pieza,
    artesano_id,
    fecha_terminado,
    peso_final_gramos,
    costo_materiales,
    costo_mano_obra,
    tiempo_real_horas,
    calidad,
    observaciones,
    vendida,
    fecha_venta,
    precio_venta_real,
    ubicacion_actual
)
SELECT
    op.id,
    p.id,
    'PZ-ANILLO-0001',
    a.id,
    (CURRENT_TIMESTAMP - INTERVAL '7 days'),
    12.500,
    320.00,
    150.00,
    6.50,
    'A',
    'Acabado premium',
    TRUE,
    (CURRENT_TIMESTAMP - INTERVAL '3 days'),
    1200.00,
    'vendida'
FROM ordenes_produccion op
JOIN productos p ON p.codigo_sku = 'ANILLO-SOL-01'
JOIN artesanos a ON a.nombre = 'Pedro' AND a.apellido = 'Martinez'
WHERE op.codigo_orden = 'OP-2026-001'
  AND NOT EXISTS (
      SELECT 1 FROM creaciones_terminadas ct WHERE ct.codigo_pieza = 'PZ-ANILLO-0001'
  );

INSERT INTO creaciones_terminadas (
    orden_produccion_id,
    producto_id,
    codigo_pieza,
    artesano_id,
    fecha_terminado,
    peso_final_gramos,
    costo_materiales,
    costo_mano_obra,
    tiempo_real_horas,
    calidad,
    observaciones,
    vendida,
    fecha_venta,
    precio_venta_real,
    ubicacion_actual
)
SELECT
    op.id,
    p.id,
    'PZ-COLLAR-0001',
    a.id,
    (CURRENT_TIMESTAMP - INTERVAL '25 days'),
    22.300,
    480.00,
    220.00,
    8.00,
    'B',
    'Ligeras marcas internas',
    FALSE,
    NULL,
    NULL,
    'inventario'
FROM ordenes_produccion op
JOIN productos p ON p.codigo_sku = 'COLLAR-LUNA-01'
JOIN artesanos a ON a.nombre = 'Ana' AND a.apellido = 'Lopez'
WHERE op.codigo_orden = 'OP-2026-002'
  AND NOT EXISTS (
      SELECT 1 FROM creaciones_terminadas ct WHERE ct.codigo_pieza = 'PZ-COLLAR-0001'
  );

INSERT INTO creaciones_terminadas (
    orden_produccion_id,
    producto_id,
    codigo_pieza,
    artesano_id,
    fecha_terminado,
    peso_final_gramos,
    costo_materiales,
    costo_mano_obra,
    tiempo_real_horas,
    calidad,
    observaciones,
    vendida,
    fecha_venta,
    precio_venta_real,
    ubicacion_actual
)
SELECT
    op.id,
    p.id,
    'PZ-ARETES-0001',
    a.id,
    (CURRENT_TIMESTAMP - INTERVAL '12 days'),
    6.800,
    180.00,
    90.00,
    3.20,
    'A',
    'Listo para vitrina',
    TRUE,
    (CURRENT_TIMESTAMP - INTERVAL '5 days'),
    650.00,
    'vendida'
FROM ordenes_produccion op
JOIN productos p ON p.codigo_sku = 'ARETES-BRISA-01'
JOIN artesanos a ON a.nombre = 'Luis' AND a.apellido = 'Garcia'
WHERE op.codigo_orden = 'OP-2026-003'
  AND NOT EXISTS (
      SELECT 1 FROM creaciones_terminadas ct WHERE ct.codigo_pieza = 'PZ-ARETES-0001'
  );

-- ============================================
-- RETRABAJOS
-- ============================================
INSERT INTO retrabajos (id_terminados, motivo_retrabajo, fecha_retrabajo, estado)
SELECT
    ct.id,
    'rayon en pulido',
    (CURRENT_TIMESTAMP - INTERVAL '2 days'),
    'Finalizado'
FROM creaciones_terminadas ct
WHERE ct.codigo_pieza = 'PZ-COLLAR-0001'
  AND NOT EXISTS (
      SELECT 1 FROM retrabajos r
      WHERE r.id_terminados = ct.id AND r.motivo_retrabajo = 'rayon en pulido'
  );

COMMIT;
