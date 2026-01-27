# 🤖 AGENTS.md - Guía para Agentes de IA

> **Proyecto:** Dedumsoft - Sistema de Gestión para Joyería  
> **Versión:** 1.1  
> **Stack:** PHP 7.4+ | PostgreSQL 17+ | Bootstrap 5 | jQuery  
> **Última actualización:** 2026-01-26

---

## 📋 Resumen Ejecutivo

Dedumsoft es un sistema ERP de gestión para una joyería llamada "Joyas Van". El sistema maneja inventario (oro, insumos, maquinaria), producción (órdenes de trabajo), proveedores, usuarios y reportes.

**Características clave:**

- Arquitectura MVC simplificada (PHP + PostgreSQL)
- Autenticación híbrida: Sesiones PHP + JWT
- Sistema de permisos RBAC (Role-Based Access Control)
- **⚠️ CRÍTICO:** Compatibilidad con Internet Explorer 8
- API REST con funciones almacenadas PostgreSQL

---

## 🏗️ Arquitectura del Proyecto

### Estructura de Directorios

```
Dedumsoft/
├── public/               # 🌐 WEB ROOT (DocumentRoot Apache)
│   ├── *.php            # Páginas principales (login, inventario, etc.)
│   ├── api/             # Endpoints REST organizados por dominio
│   │   ├── auth/        # Login, logout, password reset
│   │   ├── inventario/  # Oro, insumos, maquinaria
│   │   ├── produccion/  # Órdenes de trabajo
│   │   ├── catalogos/   # Catálogos y configuración
│   │   └── reportes/    # Reportes varios
│   └── assets/          # Recursos estáticos (CSS, JS, iconos)
│
├── private/             # 🔒 Código interno (NO accesible via web)
│   ├── bootstrap.php    # Punto de entrada: config + autoload + rutas
│   ├── Auth/            # Autenticación (JWT, sesiones, rate limiting)
│   ├── Database/        # Conexión PDO a PostgreSQL
│   ├── Http/            # Utilidades HTTP (validación, logging)
│   └── Mail/            # Envío de emails (PHPMailer)
│
├── views/               # 📄 Plantillas PHP
│   └── layouts/         # Header, nav, footer compartidos
│
├── config/              # ⚙️ Configuración
│   ├── env.php          # Variables de entorno (¡NO commitear!)
│   └── env.example.php  # Plantilla de configuración
│
├── database/            # 🗄️ Scripts SQL
│   ├── db_schema.sql    # Esquema de tablas
│   ├── seed.sql         # Datos iniciales
│   ├── functions/       # Funciones PostgreSQL (fun_*)
│   └── pglite-validator/  # ⚠️ Validador SQL (OBLIGATORIO)
│
└── vendor/              # 📦 Dependencias Composer (PHPMailer)
```

### Flujo de una Petición HTTP

```
[Cliente]
    │
    ▼
[public/*.php]  ─────────────────────────────────────┐
    │                                                 │
    ├── require bootstrap.php                         │
    │       ├── Carga config/env.php                  │
    │       ├── Define constantes de rutas            │
    │       └── Configura error reporting             │
    │                                                 │
    ├── require Auth/AuthMiddleware.php               │
    │       ├── require_login() → Valida sesión       │
    │       └── require_menu_access(id) → Valida RBAC │
    │                                                 │
    ├── require Database/Connection.php               │
    │       └── Crea $connLogic (PDO)                 │
    │                                                 │
    ├── Llama función PostgreSQL (fun_*)              │
    │       └── SELECT col1, col2 FROM fun_obtener_xxx() │
    │                                                 │
    └── Renderiza HTML o JSON ──────────────────────►[Respuesta]
```

---

## 🔐 Sistema de Autenticación

### Archivos Principales

| Archivo                           | Propósito                                         |
| --------------------------------- | ------------------------------------------------- |
| `private/Auth/LoginService.php`   | Proceso de login (9 pasos)                        |
| `private/Auth/AuthMiddleware.php` | Funciones `require_login()`, `require_api_auth()` |
| `private/Auth/JwtHandler.php`     | Codificación/decodificación JWT (HMAC-SHA256)     |
| `private/Auth/SessionManager.php` | Configuración segura de sesiones PHP              |
| `private/Auth/RateLimiter.php`    | Protección contra fuerza bruta                    |

### Flujo de Login

```php
// LoginService::login_user() - 9 pasos:
1. Verificar rate limiting (max intentos por IP)
2. Validar campos requeridos (username, password)
3. Buscar usuario en BD (fun_iniciar_sesion)
4. Verificar contraseña (password_verify + bcrypt)
5. Generar token JWT
6. Registrar sesión en seg_login (para revocación)
7. Configurar sesión PHP + regenerar ID
8. Cargar permisos de menú del rol
9. Limpiar rate limit + rotar CSRF
```

### Validación de Autenticación

```php
// En páginas protegidas (HTML):
require_login();           // Redirige a login.php si no autenticado

// En APIs (JSON):
require_api_auth();        // Retorna 401 JSON si no autenticado

// Verificar permisos de menú:
require_menu_access(2);    // Bloquea si no tiene permiso al menú #2
```

---

## 👥 Sistema de Permisos (RBAC)

### Estructura de Tablas

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   seg_rol   │      │ seg_menurol  │      │  seg_menu   │
├─────────────┤      ├──────────────┤      ├─────────────┤
│ id_rol (PK) │◄────┐│ rolid (FK)   │┌────►│ id_menu(PK) │
│ nombre      │     ││ menuid (FK)  ││     │ nombre      │
│ comentario  │     │├──────────────┤│     │ ruta        │
└─────────────┘     ││ abrir        ││     │ comentario  │
                    ││ guardar      ││     └─────────────┘
                    ││ editar       │┘
                    ││ eliminar     │
                    │└──────────────┘
                    └── Tabla pivote
```

### Roles del Sistema

| ID  | Rol      | Descripción                            |
| --- | -------- | -------------------------------------- |
| 1   | ADMIN    | Acceso total a todos los módulos       |
| 2   | OPERADOR | Producción y configuración (artesanos) |
| 3   | LECTURA  | Solo consulta, sin modificaciones      |

### Menús del Sistema

| ID  | Nombre         | Archivo Principal        |
| --- | -------------- | ------------------------ |
| 1   | Dashboard      | `index.php`              |
| 2   | Inventario     | `inventario_insumos.php` |
| 3   | Producción     | `produccion.php`         |
| 4   | Reportes       | `reportes.php`           |
| 5   | Usuarios       | `usuarios.php`           |
| 6   | Proveedores    | `proveedores.php`        |
| 7   | Configuración  | `configuracion.php`      |

**Nota:** Especialidades se gestiona dentro de **Ajustes > Catálogos** (`catalogos.php`) usando el mismo menú 7.

**Catálogos maestros (Ajustes > Catálogos):**

- Configuración central: `private/Database/CatalogConfig.php` (tabla, columnas, campos, orden).
- API genérica: `public/api/catalogos/maestros.php?catalog=...`.
- UI: `public/catalogos.php` (modo moderno y legacy).
- Catálogos incluidos: `areas`, `tipos_oro`, `tipos_proveedor`, `tipos_maquinaria`, `especialidades`,
  `estados_maquinaria`, `estados_orden`, `prioridades`, `tipos_material`, `niveles_calidad`, `productos`.
- Iconos: en legacy PNG de `public/assets/icons/fatcow/16`, en moderno emojis del config.

### ⚠️ IMPORTANTE: Caché de Permisos

Los permisos se cargan **UNA VEZ** durante el login y se almacenan en `$_SESSION['user']['permisos_menu']`. Si se modifican permisos en la BD, el usuario debe cerrar sesión y volver a entrar.

---

## 🗄️ Base de Datos PostgreSQL

### Esquemas

```sql
CREATE SCHEMA joyeria;    -- Tablas de negocio
CREATE SCHEMA seguridad;  -- Autenticación y autorización
-- El search_path es: joyeria, seguridad, public
```

### Convención de Nombres

| Prefijo | Significado | Ejemplos                                      |
| ------- | ----------- | --------------------------------------------- |
| `inv_`  | Inventario  | `inventario_oro`, `inventario_insumos`        |
| `ord_`  | Órdenes     | `ordenes_produccion`, `ord_consumos`          |
| `prov_` | Proveedores | `proveedores`, `prov_compras`                 |
| `cat_`  | Catálogos   | `tipos_oro`, `tipos_proveedor`                |
| `seg_`  | Seguridad   | `seg_usuario`, `seg_rol`, `seg_menu`          |
| `fun_`  | Funciones   | `fun_obtener_ordenes()`, `fun_crear_insumo()` |

**Reportes usados en el dashboard (fun_rep_*):**

- `fun_reporte_ordenes_estado()` → conteo de órdenes por estado (gráficas / legacy_chart).
- `fun_reporte_ordenes_dashboard(desde, hasta)` → KPIs de órdenes activas y completadas del mes (dashboard).

### Reglas SQL Obligatorias

⚠️ **NUNCA usar `SELECT *` ni `COUNT(*)`:**

```sql
-- ❌ PROHIBIDO
SELECT * FROM usuarios;
SELECT COUNT(*) FROM ordenes;

-- ✅ CORRECTO - Especificar columnas explícitamente
SELECT id, username, nombre, rolid FROM usuarios;
SELECT COUNT(1) FROM ordenes;
```

**Razones:**

- Rendimiento: evita transferir columnas innecesarias
- Seguridad: previene exposición accidental de datos sensibles
- Mantenibilidad: código explícito sobre qué datos se usan
- Compatibilidad: evita errores si la estructura de tablas cambia

### Funciones Almacenadas (fun\_\*)

El sistema usa funciones PostgreSQL para toda la lógica de datos:

```sql
-- Patrón: fun_[accion]_[entidad](parámetros)
-- NOTA: Siempre especificar columnas, nunca SELECT *
SELECT id, tipo_oro_nombre, peso_gramos, precio_gramo
  FROM fun_obtener_inventario_oro(0, 50, NULL, TRUE);
SELECT resultado FROM fun_crear_orden(producto_id, cantidad, artesano_id, ...);
SELECT resultado FROM fun_actualizar_maquinaria(id, nombre, estado_id, ...);
SELECT fun_eliminar_insumo(id);  -- Soft delete
```

**Archivos de funciones:**

- `database/functions/01_triggers.sql` - Triggers de auditoría
- `database/functions/02_auth.sql` - Autenticación
- `database/functions/03_inventario.sql` - CRUD inventario
- `database/functions/04_proveedores.sql` - Proveedores y compras
- `database/functions/05_ordenes.sql` - Órdenes de producción
- `database/functions/06_reportes.sql` - Reportes
- `database/functions/07_ubicaciones.sql` - Ubicaciones físicas
- `database/functions/08_catalog_functions.sql` - Catálogos
- `database/functions/09_artesano.sql` - Gestión de artesanos
  - Nota: al modificar funciones o la estructura de la base de datos, solo modificar los scripts SQL base, no generar migraciones.
  - Nota: Si una query es demasiado compleja para solo manejarla en PHP, debe implementarse como función PostgreSQL PL/pgSQL. Así mismo, todas las funciones deben operar bajo LANGUAGE PLPGSQL, no LANGUAGE SQL.

### ⚠️ OBLIGATORIO: Validación con PGlite

**TODO cambio en la base de datos DEBE validarse con PGlite antes de aplicarse.**

PGlite es PostgreSQL compilado a WebAssembly que corre en Node.js. Permite validar SQL sin afectar la base de datos real.

```bash
# Instalar (solo la primera vez, en caso que no esté la carpeta node_modules)
cd database/pglite-validator
npm install

# Validar TODOS los cambios (esquema + funciones + seed)
npm run validate:all

# Validar solo esquema
npm run validate:schema

# Validar solo funciones
npm run validate:functions

# Validar archivo específico
npm run validate:file -- ../functions/03_inventario.sql
```

**Reglas de validación:**

1. **Antes de commitear:** Ejecutar `npm run validate:all`
2. **Nuevas funciones:** El archivo debe pasar validación
3. **Cambios en esquema:** Validar que no rompa funciones existentes
4. **Si falla la validación:** NO aplicar cambios hasta corregir errores

**Flujo obligatorio para cambios SQL:**

```
[Editar SQL] → [npm run validate:all] → [¿Pasa?] → [Sí] → [Aplicar a PostgreSQL]
                                           ↓
                                         [No] → [Corregir errores]
```

---

## 🔌 Patrón de APIs REST

### Estructura Estándar de un Endpoint

```php
<?php
// 1. Cargar bootstrap (SIEMPRE primero)
require_once __DIR__ . '/../../../private/bootstrap.php';

// 2. Cargar dependencias
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

// 3. Header JSON
header('Content-Type: application/json');

// 4. Validar autenticación
if (!require_api_auth()) exit;

// 5. Validar autorización (menú)
require_menu_access(3);  // ID del menú requerido

// 6. Procesar según método HTTP
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        // Listar/buscar con función fun_obtener_*
        break;
    case 'POST':
        // Crear con función fun_crear_*
        break;
    case 'PATCH':
        // Actualizar con función fun_actualizar_*
        break;
    case 'DELETE':
        // Eliminar con función fun_eliminar_*
        break;
}

// 7. Respuesta JSON estándar
echo json_encode(['CODIGO' => 200, 'MENSAJE' => 'OK', 'DATOS' => $rows]);
```

### Formato de Respuestas

```json
// Éxito
{ "CODIGO": 200, "MENSAJE": "Operación exitosa", "DATOS": [...] }

// Error cliente
{ "CODIGO": 400, "MENSAJE": "Descripción del error" }

// No autenticado
{ "CODIGO": 401, "MENSAJE": "No autenticado." }

// Sin permisos
{ "CODIGO": 403, "MENSAJE": "Acceso denegado." }

// Error servidor
{ "CODIGO": 500, "MENSAJE": "Error interno del servidor." }
```

---

## ⚠️ CRÍTICO: Compatibilidad IE8

### ¿Por qué IE8?

Algunos clientes usan equipos antiguos con Windows XP/Vista que no pueden actualizarse. El sistema **DEBE** funcionar en Internet Explorer 8.

### ⚠️ IMPORTANTE: Alcance de las restricciones legacy

Las restricciones de compatibilidad IE8 **SOLO aplican a:**

- Código JavaScript en `public/assets/js/`
- Scripts inline en páginas PHP (`<script>` tags)
- Cualquier JS que se ejecute en el navegador del usuario

**NO aplican a:**

- Herramientas de desarrollo (ej: `pglite-validator/`)
- Scripts de Node.js
- Código PHP del servidor
- Scripts de deploy/build

### ❌ PROHIBIDO en JavaScript (cliente)

```javascript
// NO USAR - Incompatible con IE8:
const x = 1;              // usar var
let y = 2;                // usar var
() => {}                  // usar function(){}
`template ${var}`         // usar "string " + var
{a, b} = obj              // usar obj.a, obj.b
[...array]                // usar bucle for
Promise/fetch             // usar $.ajax
element.classList         // usar element.className
forEach en NodeList       // usar bucle for
```

### ✅ USAR en JavaScript

```javascript
// CORRECTO - Compatible con IE8:
var x = 1;
var y = 2;
var fn = function () {};
"Hello " + name;
var a = obj.a;
for (var i = 0; i < arr.length; i++) {}
$.ajax({ url: "/api", success: function (d) {} });
element.className += " clase";
```

- Nota: estas reglas SOLO se deben aplicar cuando se escriba código que esté dirigido a la vista legacy. No es necesario e inclusive se incita a usar normas de desarrollo modernas de JS si se está trabajando en código que solo se ejecutará en navegadores modernos.

### Archivos de Compatibilidad

| Archivo                    | Propósito                      |
| -------------------------- | ------------------------------ |
| `assets/css/ie8.css`       | Estilos específicos IE8        |
| `assets/js/ie8.js`         | Polyfills generales            |
| `assets/js/json2.min.js`   | Polyfill JSON.parse/stringify  |
| `assets/js/crud-legacy.js` | CRUD con XHR compatible IE8    |
| `public/legacy_chart.php`  | Gráficos como PNG (GD Library) |

### Method Override (HTTP)

IE8 no soporta PATCH/PUT/DELETE nativamente. Se usa method override:

```javascript
// Enviar via POST con override
$.ajax({
  url: "/api/usuarios.php?_method=PATCH",
  method: "POST",
  headers: { "X-HTTP-Method-Override": "PATCH" },
  data: JSON.stringify(payload),
});
```

El `bootstrap.php` detecta el override y establece el método correcto.

### Detección de Legacy

```php
// En PHP (Guard.php)
$legacy = dedumsoft_is_legacy_browser();

// En JavaScript
var isIE8 = navigator.userAgent.indexOf('MSIE 8') !== -1;
```

---

## 🎨 Frontend

### Componentes Principales

| Archivo                    | Propósito                               |
| -------------------------- | --------------------------------------- |
| `views/layouts/header.php` | `<head>`, headers de seguridad, CSS     |
| `views/layouts/nav.php`    | Menú lateral dinámico según permisos    |
| `views/layouts/footer.php` | Scripts JS, cierre HTML                 |
| `assets/js/crud.js`        | Modales, CRUD, notificaciones (moderno) |
| `assets/js/crud-legacy.js` | Lo mismo para IE8                       |

### Bibliotecas Usadas

**Navegadores Modernos:**

- jQuery 3.7.1
- Axios (HTTP)
- Notyf (notificaciones toast)
- DataTables + Bootstrap 5
- uPlot (gráficos)

**Navegadores Legacy (IE8):**

- JSON2 polyfill
- table-sort.js (ordenamiento simple)
- Iconos Fatcow (16x16 PNG)
- GD Library (gráficos como imágenes PNG)

### Sistema de Notificaciones

```javascript
// Moderno (crud.js)
DsCrud.toast('Mensaje de éxito', 'success');
DsCrud.toast('Mensaje de error', 'error');

// Modales
DsCrud.openModal({
    title: 'Nuevo Registro',
    body: '<form>...</form>',
    onSave: function() { ... }
});
```

---

## 🔒 Seguridad

### Medidas Implementadas

| Medida           | Implementación                           |
| ---------------- | ---------------------------------------- |
| Rate Limiting    | 60 req/min por IP (RateLimiter.php)      |
| CSRF             | Token en sesión, validado en formularios |
| Password Hash    | bcrypt via password_hash/verify          |
| SQL Injection    | PDO con prepared statements              |
| XSS              | htmlspecialchars() en salidas            |
| Session Fixation | session_regenerate_id() post-login       |
| JWT              | HMAC-SHA256, expiración configurable     |
| Headers HTTP     | X-Frame-Options, CSP, etc.               |

### Logging de Seguridad

```php
// Eventos registrados en error_log:
[DEDUMSOFT_SECURITY] LOGIN_SUCCESS | IP | usuario | URI
[DEDUMSOFT_SECURITY] LOGIN_FAILED | IP | usuario | URI | motivo
[DEDUMSOFT_SECURITY] RATE_LIMITED | IP | intentos
[DEDUMSOFT_SECURITY] ACCESS_DENIED | IP | usuario | recurso
```

---

## 📝 Guía para Modificaciones

### Agregar un Nuevo Endpoint API

1. Crear archivo en `public/api/{dominio}/nuevo.php`
2. Seguir el patrón estándar (bootstrap → auth → switch método → respuesta)
3. Crear función PostgreSQL `fun_*` correspondiente en `database/functions/`
4. Probar con Postman antes de integrar

### Agregar una Nueva Página

1. Crear `public/nueva_pagina.php`
2. Incluir bootstrap, auth, layouts:

```php
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_login();
require_menu_access(ID_MENU);
include VIEWS_PATH . '/layouts/header.php';
include VIEWS_PATH . '/layouts/nav.php';
// ... contenido ...
include VIEWS_PATH . '/layouts/footer.php';
```

3. Agregar entrada en `seg_menu` y permisos en `seg_menurol`

### Modificar Permisos

1. Actualizar `seg_menurol` en la base de datos
2. El usuario debe cerrar sesión y volver a entrar

### Agregar Nueva Función PostgreSQL

1. Crear en `database/functions/XX_modulo.sql`
2. Seguir convención `fun_[accion]_[entidad]`
3. Ejecutar: `psql -d dedumsoft -f database/functions/XX_modulo.sql`

---

## 🐛 Debugging

### Habilitar Errores (Desarrollo)

```php
// En config/env.php
'PROD' => false  // Muestra errores en pantalla
```

### Ver Logs

```bash
# macOS
tail -f /var/log/apache2/error_log | grep DEDUMSOFT

# Linux
tail -f /var/log/apache2/error.log | grep DEDUMSOFT

# PostgreSQL
tail -f /var/log/postgresql/postgresql-17-main.log
```

### Probar API con cURL

```bash
# Login
curl -X POST http://localhost/dedumsoft/public/api/auth/login.php \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}'

# GET con sesión
curl -X GET http://localhost/dedumsoft/public/api/inventario/oro.php \
     --cookie "PHPSESSID=xxxxx"
```

---

## 📚 Glosario Rápido

| Término         | Significado                                         |
| --------------- | --------------------------------------------------- |
| `$connLogic`    | Conexión PDO global a PostgreSQL                    |
| `bootstrap.php` | Archivo de inicialización (incluir siempre primero) |
| `fun_*`         | Funciones almacenadas PostgreSQL                    |
| `seg_*`         | Tablas del esquema de seguridad                     |
| `RBAC`          | Role-Based Access Control                           |
| `Legacy`        | Modo compatibilidad IE8                             |
| `Artesano`      | Usuario rol OPERADOR que fabrica joyas              |
| `Orden`         | Pedido de producción de una pieza                   |
| `PGlite`        | PostgreSQL en WebAssembly para validar SQL          |

---

## ✅ Checklist para Cambios

### Cambios en Base de Datos (SQL)

- [ ] ¿**Pasó validación PGlite**? (`cd database/pglite-validator && npm run validate:all`)
- [ ] ¿Evita `SELECT *` y `COUNT(*)`? (usar columnas explícitas y `COUNT(1)`)
- [ ] ¿Usa prepared statements (`:param`)?
- [ ] ¿La función sigue la convención `fun_[accion]_[entidad]`?

### Cambios en JavaScript

- [ ] ¿Funciona en IE8? (probar con VM o emulador)
- [ ] ¿Usa `var` en lugar de `let`/`const`?
- [ ] ¿Las funciones son `function(){}` no arrow?

### Cambios en PHP/API

- [ ] ¿Tiene validación de autenticación?
- [ ] ¿Tiene validación de permisos (menú)?
- [ ] ¿Los mensajes de error no revelan información sensible?

### General

- [ ] ¿Se documentó el cambio en español?
- [ ] ¿Se documentó el cambio en DEVELOPER_GUIDE.md?

---

_Generado automáticamente para asistir agentes de IA en el desarrollo de Dedumsoft._
