# Migraciones de Base de Datos

Este directorio está reservado para migraciones futuras **incrementales**.

## Setup Inicial

Para configurar la base de datos desde cero, ejecutar los scripts en este orden:

```bash
# 1. Crear la base de datos (si no existe)
createdb db_dedumsoft

# 2. Ejecutar schema principal
psql -d db_dedumsoft -f DB/db_schema.sql

# 3. Ejecutar funciones en orden
psql -d db_dedumsoft -f DB/functions/01_triggers.sql
psql -d db_dedumsoft -f DB/functions/02_auth.sql
psql -d db_dedumsoft -f DB/functions/03_inventario.sql
psql -d db_dedumsoft -f DB/functions/04_proveedores.sql
psql -d db_dedumsoft -f DB/functions/05_ordenes.sql
psql -d db_dedumsoft -f DB/functions/06_reportes.sql
psql -d db_dedumsoft -f DB/functions/07_ubicaciones.sql
psql -d db_dedumsoft -f DB/functions/08_catalog_functions.sql

# 4. Cargar datos iniciales
psql -d db_dedumsoft -f DB/seed.sql
```

## Migraciones Futuras

Las migraciones deben nombrarse con el formato:
`XXX_descripcion_breve.sql`

Donde XXX es un número secuencial de 3 dígitos (001, 002, etc.)

Ejemplo: `001_add_column_x_to_table_y.sql`

## Nota

Las migraciones anteriores fueron consolidadas en los scripts base el 2026-01-18.
No es necesario ejecutar migraciones históricas en instalaciones nuevas.
