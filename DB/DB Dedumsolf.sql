DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id_usuario   SERIAL PRIMARY KEY,
  username     VARCHAR(50) UNIQUE NOT NULL,
  password     TEXT NOT NULL,
  rol          VARCHAR(20) NOT NULL CHECK (rol IN ('super_admin','admin','operario')),
  estado       BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ==========================================
-- INVENTARIO
-- ==========================================
DROP TABLE IF EXISTS inventario_oro;
DROP TABLE IF EXISTS inventario_insumos;
DROP TABLE IF EXISTS inventario_equipos;

-- ORO
CREATE TABLE inventario_oro (
  id_tipo        VARCHAR(10) PRIMARY KEY
                 CHECK (id_tipo IN ('puro','retal')),
  quilataje      NUMERIC(4,2) NOT NULL
                 CHECK (quilataje IN (18.00, 24.00)),
  peso_neto      NUMERIC(10,2) NOT NULL CHECK (peso_neto >= 0),
  peso_bruto     NUMERIC(10,2) NOT NULL CHECK (peso_bruto >= peso_neto),
  unidad_medida  VARCHAR(10) NOT NULL DEFAULT 'gr',
  stock_actual   NUMERIC(12,2) NOT NULL DEFAULT 0,
  stock_minimo   NUMERIC(12,2) NOT NULL,
  punto_reorden  NUMERIC(12,2) NOT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

-- INSUMOS
CREATE TABLE inventario_insumos (
  id_insumo      VARCHAR(20) PRIMARY KEY
                 CHECK (id_insumo IN ('químicos','empaques')),
  descripcion    VARCHAR(30) NOT NULL,
  unidad_medida  VARCHAR(10) NOT NULL DEFAULT 'und',
  stock_actual   NUMERIC(12,2) NOT NULL DEFAULT 0,
  stock_minimo   NUMERIC(12,2) NOT NULL,
  punto_reorden  NUMERIC(12,2) NOT NULL,
  valor_unitario NUMERIC(12,2) NOT NULL,
  estado         BOOLEAN NOT NULL DEFAULT TRUE,
  created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

-- EQUIPOS
CREATE TABLE inventario_equipos (
  id_equipos     VARCHAR(20) PRIMARY KEY
                 CHECK (id_equipos IN ('herramienta','maquinaria')),
  descripcion    VARCHAR(30) NOT NULL,
  valor_unitario NUMERIC(12,2) NOT NULL,
  estado         BOOLEAN NOT NULL DEFAULT TRUE,
  created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ==========================================
-- PROCESOS Y PASOS
-- ==========================================
DROP TABLE IF EXISTS orden_pasos;
DROP TABLE IF EXISTS orden_fabricacion;
DROP TABLE IF EXISTS proceso_pasos;
DROP TABLE IF EXISTS procesos;
DROP TABLE IF EXISTS pasos;

-- Catálogo de pasos
CREATE TABLE pasos (
  id_paso VARCHAR(20) PRIMARY KEY
    CHECK (id_paso IN ('alistamiento','fundicion','acabado','calidad','entrega')),
  descripcion VARCHAR(100) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Proceso genérico
CREATE TABLE procesos (
  id_proceso  SERIAL PRIMARY KEY,
  nombre      VARCHAR(60) UNIQUE NOT NULL,
  estado      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Plantilla de pasos de cada proceso
CREATE TABLE proceso_pasos (
  id_proceso   INT NOT NULL,
  id_paso      VARCHAR(20) NOT NULL,
  secuencia    INT NOT NULL CHECK (secuencia > 0),
  tiempo_std_min INT NOT NULL DEFAULT 0 CHECK (tiempo_std_min >= 0),
  PRIMARY KEY (id_proceso, id_paso),
  UNIQUE (id_proceso, secuencia),
  FOREIGN KEY (id_proceso) REFERENCES procesos(id_proceso) ON DELETE RESTRICT,
  FOREIGN KEY (id_paso) REFERENCES pasos(id_paso) ON DELETE RESTRICT
);

-- Órdenes de fabricación
CREATE TABLE orden_fabricacion (
  id_orden      SERIAL PRIMARY KEY,
  codigo_orden  VARCHAR(30) UNIQUE NOT NULL,
  id_proceso    INT NOT NULL,
  cantidad      NUMERIC(10,2) NOT NULL CHECK (cantidad > 0),
  estado        VARCHAR(15) NOT NULL DEFAULT 'abierta'
                CHECK (estado IN ('abierta','en_proceso','pausada','cerrada','cancelada')),
  observaciones TEXT,
  created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  FOREIGN KEY (id_proceso) REFERENCES procesos(id_proceso) ON DELETE RESTRICT
);

-- Pasos de la orden (instancia de la plantilla)
CREATE TABLE orden_pasos (
  id_orden     INT NOT NULL,
  secuencia    INT NOT NULL,
  id_paso      VARCHAR(20) NOT NULL,
  estado       VARCHAR(15) NOT NULL DEFAULT 'pendiente'
               CHECK (estado IN ('pendiente','en_ejecucion','hecho','anulado')),
  responsable  INT NULL,
  inicio_ts    TIMESTAMP NULL,
  fin_ts       TIMESTAMP NULL,
  notas        TEXT,
  PRIMARY KEY (id_orden, secuencia),
  UNIQUE (id_orden, id_paso),
  FOREIGN KEY (id_orden) REFERENCES orden_fabricacion(id_orden) ON DELETE RESTRICT,
  FOREIGN KEY (id_paso) REFERENCES pasos(id_paso) ON DELETE RESTRICT,
  FOREIGN KEY (responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- ==========================================
-- CONSUMOS DE ORO / INSUMOS
-- ==========================================
DROP TABLE IF EXISTS consumo_insumos;
DROP TABLE IF EXISTS consumo_oro;

-- Oro consumido en una orden
CREATE TABLE consumo_oro (
  id_consumo    SERIAL PRIMARY KEY,
  id_orden      INT NOT NULL,
  id_tipo       VARCHAR(10) NOT NULL,
  quilataje     NUMERIC(4,2) NOT NULL CHECK (quilataje IN (18.00,24.00)),
  peso_neto     NUMERIC(12,2) NOT NULL CHECK (peso_neto > 0),
  peso_bruto    NUMERIC(12,2) NOT NULL CHECK (peso_bruto >= peso_neto),
  merma_prevista NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (merma_prevista >= 0),
  created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  FOREIGN KEY (id_orden) REFERENCES orden_fabricacion(id_orden) ON DELETE RESTRICT,
  FOREIGN KEY (id_tipo) REFERENCES inventario_oro(id_tipo) ON DELETE RESTRICT
);

-- Insumos consumidos en una orden
CREATE TABLE consumo_insumos (
  id_consumo    SERIAL PRIMARY KEY,
  id_orden      INT NOT NULL,
  id_insumo     VARCHAR(20) NOT NULL,
  cantidad      NUMERIC(12,2) NOT NULL CHECK (cantidad > 0),
  unidad_medida VARCHAR(10) NOT NULL DEFAULT 'und',
  valor_unitario NUMERIC(12,2) NOT NULL CHECK (valor_unitario >= 0),
  created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  FOREIGN KEY (id_orden) REFERENCES orden_fabricacion(id_orden) ON DELETE RESTRICT,
  FOREIGN KEY (id_insumo) REFERENCES inventario_insumos(id_insumo) ON DELETE RESTRICT
);