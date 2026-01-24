# 🔍 Dedumsoft - Validador SQL con PGlite

Herramienta de validación de SQL que usa [PGlite](https://github.com/electric-sql/pglite) (PostgreSQL compilado a WebAssembly) para verificar que los cambios en la base de datos son correctos **antes** de aplicarlos en PostgreSQL real.

## ⚠️ REGLA OBLIGATORIA

> **TODO cambio en `db_schema.sql` o `functions/*.sql` DEBE pasar esta validación antes de ser aplicado.**

Esta regla está documentada en `AGENTS.md` y aplica a:

- Agentes de IA (Copilot, Claude, etc.)
- Desarrolladores humanos
- Scripts automatizados

## 📋 Requisitos

- Node.js 18+
- npm o pnpm

## 🚀 Instalación

```bash
cd database/pglite-validator
npm install
```

## 📖 Uso

### Validar todo (recomendado)

```bash
npm run validate:all
```

Ejecuta en orden:

1. `db_schema.sql` - Estructura de tablas
2. `functions/*.sql` - Funciones almacenadas
3. `seed.sql` - Datos iniciales
4. Pruebas de smoke test

### Validar solo esquema

```bash
npm run validate:schema
```

### Validar solo funciones

```bash
npm run validate:functions
```

### Validar archivo específico

```bash
npm run validate:file -- ../functions/03_inventario.sql
```

## 🔧 Cómo funciona

1. **PGlite** crea una instancia de PostgreSQL en memoria (WebAssembly)
2. Carga el esquema (`db_schema.sql`)
3. Ejecuta cada archivo SQL y captura errores
4. Reporta éxito/fallo con detalles

### Limitaciones de PGlite

- No soporta extensiones (`pgcrypto`, `uuid-ossp`)
- Comandos `\i`, `\echo` son ignorados automáticamente
- Algunas funciones avanzadas pueden no estar disponibles

El validador preprocesa los archivos para manejar estas limitaciones.

## 📊 Ejemplo de output

```
╔════════════════════════════════════════════════════════╗
║     DEDUMSOFT - Validador de SQL con PGlite            ║
╚════════════════════════════════════════════════════════╝

ℹ Inicializando PGlite (PostgreSQL en memoria)...
✓ PGlite inicializado

📋 Validando db_schema.sql

✓ db_schema.sql ejecutado correctamente
✓ Esquemas joyeria y seguridad creados
ℹ Tablas creadas: 25

🔧 Validando funciones almacenadas

✓ 01_triggers.sql
✓ 02_auth.sql
✓ 03_inventario.sql
✓ 04_proveedores.sql
✓ 05_ordenes.sql
✓ 06_reportes.sql
✓ 07_ubicaciones.sql
✓ 08_catalog_functions.sql
✓ 09_artesano.sql

ℹ Funciones: 9 OK, 0 errores
ℹ Total funciones fun_*: 42

══════════════════════════════════════════════════════

  ✓ VALIDACIÓN EXITOSA

  Los cambios SQL pueden aplicarse a PostgreSQL.
```

## 🛠️ Integración con desarrollo

### Antes de commitear

```bash
cd database/pglite-validator && npm test
```

### En CI/CD (opcional)

```yaml
# .github/workflows/validate-sql.yml
- name: Validar SQL
  run: |
    cd database/pglite-validator
    npm install
    npm run validate:all
```

## 📚 Referencias

- [PGlite GitHub](https://github.com/electric-sql/pglite)
- [AGENTS.md](../../AGENTS.md) - Guía completa del proyecto
