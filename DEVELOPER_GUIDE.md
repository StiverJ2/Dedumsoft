# 🛠️ Guía del Desarrollador - Dedumsoft

> **Sistema de Gestión de Joyería**  
> Versión: 1.1 | PHP 7.4+ | PostgreSQL 17+ | Última actualización: 2026-01-26

---

## 📑 Tabla de Contenidos

1. [Arquitectura General](#1-arquitectura-general)
2. [Sistema de Autenticación](#2-sistema-de-autenticación)
3. [Sistema de Permisos (RBAC)](#3-sistema-de-permisos-rbac)
4. [Estructura de Base de Datos](#4-estructura-de-base-de-datos)
5. [Patrón de APIs](#5-patrón-de-apis)
6. [⚠️ Compatibilidad Legacy (IE8)](#6-️-compatibilidad-legacy-ie8)
7. [Seguridad](#7-seguridad)
8. [Despliegue](#8-despliegue)
9. [Gotchas y Errores Comunes](#9-gotchas-y-errores-comunes)
10. [Glosario de Términos](#-glosario-de-términos)

---

## 1. Arquitectura General

### Estructura de Carpetas

```
Dedumsoft/
├── Back/                        # 🗂️ Legacy/backup (no usado en runtime)
│   └── api/                     # Ej: password_reset.php (histórico)
├── public/                      # 🌐 WEB ROOT (DocumentRoot de Apache)
│   ├── index.php                # Dashboard principal
│   ├── login.php                # Página de login
│   ├── auth/                    # Wrappers legacy (logout)
│   ├── assets/                  # Recursos estáticos
│   │   ├── css/                 # Estilos (Bootstrap, DataTables, custom)
│   │   ├── js/                  # Scripts (jQuery, axios, crud.js, crud-legacy.js)
│   │   ├── icons/               # Iconos Fatcow para IE8
│   │   └── uplot/               # Librería de gráficos
│   └── api/                     # Endpoints REST agrupados por dominio
│       ├── auth/                # Login, logout, password reset
│       ├── inventario/          # Oro, insumos, maquinaria
│       ├── produccion/          # Órdenes, artesanos
│       ├── reportes/            # Reportes varios
│       ├── catalogos/           # Catálogos, proveedores, ubicaciones
│       └── *.php                # Shims en raíz (usuarios.php, ordenes.php, etc.)
│
├── private/                     # 🔒 Código PHP interno (NO accesible via web)
│   ├── bootstrap.php            # Inicialización: config + autoload + rutas
│   ├── Auth/                    # Autenticación y autorización
│   │   ├── AuthMiddleware.php   # Funciones require_login, require_api_auth
│   │   ├── JwtHandler.php       # Generación/validación JWT
│   │   ├── SessionManager.php   # Gestión de sesiones PHP + CSRF
│   │   ├── LoginService.php     # Lógica de login (9 pasos)
│   │   └── RateLimiter.php      # Protección contra fuerza bruta
│   ├── Database/                # Conexión a base de datos
│   │   ├── Connection.php       # Conexión PDO a PostgreSQL
│   │   └── Guard.php            # Protección acceso directo
│   ├── Http/                    # Utilidades HTTP
│   │   ├── MethodValidator.php  # Validación método HTTP
│   │   └── SecurityLogger.php   # Logging de eventos de seguridad
│   └── Mail/                    # Envío de emails
│       └── Mailer.php           # PHPMailer wrapper
│
├── views/                       # 📄 Vistas PHP (templates)
│   └── layouts/                 # Componentes reutilizables
│       ├── header.php           # <head> y apertura <body>
│       ├── nav.php              # Menú lateral según permisos
│       └── footer.php           # Scripts y cierre </body>
│
├── config/                      # ⚙️ Configuración
│   ├── env.php                  # Variables de entorno (¡NO commitear!)
│   └── env.example.php          # Plantilla de configuración
│
├── database/                    # 🗄️ Scripts SQL
│   ├── db_schema.sql            # Esquema de tablas
│   ├── seed.sql                 # Datos iniciales
│   └── functions/               # Funciones PostgreSQL (fun_*)
│   └── pglite-validator/        # ✅ Validador SQL (OBLIGATORIO)
│
├── vendor/                      # 📦 Dependencias Composer
├── composer.json                # Definición de dependencias
├── scripts/                     # 🚀 Scripts de deploy
└── DEVELOPER_GUIDE.md           # Esta guía
```

### Flujo de una Petición

```
[Cliente] → [public/*.php] → [private/Auth/*] → [public/api/*] → [PostgreSQL]
               │                    │                  │
               │                    │                  └── Funciones fun_*
               │                    └── Valida sesión/JWT
               └── Renderiza HTML + incluye views/layouts/*
```

### Stack Tecnológico

| Capa          | Tecnología                                 |
| ------------- | ------------------------------------------ |
| Frontend      | HTML5, Bootstrap 5, jQuery 3.7, DataTables |
| Backend       | PHP 7.4+ (PDO)                             |
| Base de Datos | PostgreSQL 17+                             |
| Autenticación | Sesiones PHP + JWT (API)                   |
| Gráficos      | uPlot (moderno) / GD Library (legacy)      |

---

## 2. Sistema de Autenticación

### Archivos Clave

| Archivo                           | Propósito                 |
| --------------------------------- | ------------------------- |
| `public/api/auth/login.php`       | Endpoint POST para login  |
| `private/Auth/LoginService.php`   | Lógica de login (9 pasos) |
| `private/Auth/AuthMiddleware.php` | Funciones de autorización |
| `private/Auth/JwtHandler.php`     | Generación/validación JWT |
| `private/Auth/SessionManager.php` | Gestión de sesión PHP     |
| `public/api/auth/logout.php`      | Cierre de sesión          |

### Flujo de Login (9 Pasos)

```php
// En login_service.php - función login_user()

// Paso 1: Validar campos requeridos
// Paso 2: Sanitizar entrada
// Paso 3: Conectar a BD
// Paso 4: Buscar usuario por username
// Paso 5: Verificar contraseña (password_verify)
// Paso 6: Verificar estado activo
// Paso 7: Registrar log de acceso
// Paso 8: Cargar permisos de menú ← ¡IMPORTANTE!
// Paso 9: Crear sesión y JWT
```

### Funciones de Autorización

```php
// En private/Auth/AuthMiddleware.php

require_login();              // Redirige a login si no autenticado
require_api_auth();           // Valida JWT, retorna 401 si falla
get_session_user();           // Obtiene datos del usuario actual
dedumsoft_user_can_menu($id); // Verifica permiso de menú
require_menu_access($id);     // Bloquea acceso si no tiene permiso
```

### Recuperación de Contraseña

El sistema incluye un flujo completo de recuperación de contraseña:

```
[Usuario] → forgot_password.php (usuario + email) → API/password_reset.php?action=request
                                         │
                                         ▼
                                  Genera token (1h)
                                         │
                                         ▼
                              [Email con link de recuperación]
                                         │
                                         ▼
[Usuario] → reset_password.php?token=xxx → API/password_reset.php?action=reset
                                         │
                                         ▼
                              Cambia contraseña + invalida sesiones
```

**Archivos del sistema:**

| Archivo                      | Propósito                                                                          |
| ---------------------------- | ---------------------------------------------------------------------------------- |
| `public/forgot_password.php` | Formulario para solicitar reset                                                    |
| `public/reset_password.php`  | Formulario para nueva contraseña                                                   |
| `public/api/auth/password_reset.php` | API con 3 acciones: request, validate, reset                                 |
| `private/Mail/Mailer.php`            | Servicio de envío de emails con PHPMailer                                    |
| `database/db_schema.sql`             | Tabla `seg_password_reset`                                                   |
| `database/functions/02_auth.sql`     | Funciones `fun_crear_reset_token`, `fun_validar_reset_token`, `fun_reset_password` |

**Seguridad implementada:**

- Tokens de 256 bits (64 caracteres hex)
- Expiración de 1 hora
- Rate limiting (5 solicitudes / 15 min por IP y por usuario)
- Valida usuario + email antes de emitir token (respuesta generica)
- No revela si el usuario/email existe (previene enumeración)
- Invalida todas las sesiones al cambiar contraseña
- Validación de fortaleza: 8+ chars, mayúscula, minúscula, número
- Emails enviados via SMTP con TLS/SSL (PHPMailer)

**Configuración de Email (config/env.php):**

```php
'MAIL_HOST' => 'smtp.gmail.com',        // Servidor SMTP
'MAIL_PORT' => 587,                      // Puerto (587=TLS, 465=SSL)
'MAIL_USERNAME' => 'tu_email@gmail.com', // Usuario SMTP
'MAIL_PASSWORD' => 'app_password_16char', // App Password (no contraseña normal)
'MAIL_ENCRYPTION' => 'tls',              // 'tls' o 'ssl'
'MAIL_FROM_ADDRESS' => 'noreply@dedumsoft.com',
'MAIL_FROM_NAME' => 'Dedumsoft Joyería',
```

**Nota Gmail:** Usar App Passwords (16 caracteres), no la contraseña de la cuenta.

### Uso en Vistas

```php
<?php
// Cargar bootstrap (siempre primero)
require_once __DIR__ . '/../private/bootstrap.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';
require_once PRIVATE_PATH . '/Database/Connection.php';

require_login();                    // Paso 1: Verificar sesión
require_menu_access(2);             // Paso 2: Verificar permiso menú #2

// Si llega aquí, usuario autenticado Y autorizado
$user = get_session_user();
?>
```

### Uso en APIs

```php
<?php
// Cargar bootstrap
require_once __DIR__ . '/../../../private/bootstrap.php';
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

require_api_auth();  // Valida JWT del header Authorization: Bearer xxx

// Si llega aquí, JWT válido
$user = get_session_user();
```

---

## 3. Sistema de Permisos (RBAC)

### ⚠️ CONCEPTO CRÍTICO

El sistema usa **Role-Based Access Control (RBAC)** con 3 tablas:

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   seg_rol   │      │ seg_menurol  │      │  seg_menu   │
├─────────────┤      ├──────────────┤      ├─────────────┤
│ id_rol (PK) │◄────┐│ rolid (FK)   │┌────►│ id_menu(PK) │
│ nombre      │     ││ menuid (FK)  ││     │ nombre      │
│ comentario  │     │├──────────────┤│     │ ruta        │
└─────────────┘     ││ abrir        ││     │ comentario  │
                    ││ guardar      ││     └─────────────┘
                    ││ editar       ││
                    ││ eliminar     │┘
                    │└──────────────┘
                    │
                    └── Tabla pivote con permisos CRUD
```

### Tipos de Permisos

| Permiso    | Descripción        | Uso     |
| ---------- | ------------------ | ------- |
| `abrir`    | Puede ver/acceder  | Lectura |
| `guardar`  | Puede crear nuevos | INSERT  |
| `editar`   | Puede modificar    | UPDATE  |
| `eliminar` | Puede borrar       | DELETE  |

### Roles Predefinidos

```sql
-- En database/seed.sql
INSERT INTO seguridad.seg_rol VALUES
(1, 'ADMIN', 'Acceso total'),
(2, 'OPERADOR', 'Producción y configuración'),
(3, 'LECTURA', 'Solo consulta');
```

### Menús del Sistema

| ID  | Nombre        | Ruta (seg_menu.ruta) | Página principal (public) |
| --- | ------------- | -------------------- | ------------------------- |
| 1   | Dashboard     | /dashboard           | `public/index.php`        |
| 2   | Inventario    | /inventario          | `public/inventario_insumos.php` |
| 3   | Produccion    | /produccion          | `public/produccion.php`   |
| 4   | Reportes      | /reportes            | `public/reportes.php`     |
| 5   | Usuarios      | /usuarios            | `public/usuarios.php`     |
| 6   | Proveedores   | /proveedores         | `public/proveedores.php`  |
| 7   | Configuracion | /configuracion       | `public/configuracion.php` |

**Nota:** `seg_menu.ruta` es una ruta lógica. La navegación real usa archivos PHP
en `public/` (ver `views/layouts/nav.php`). Para operadores sin menú 1, el home
puede ser `public/index_operario.php`.

**Especialidades:** ahora se administran dentro de `public/catalogos.php` (Ajustes > Catálogos).

### ⚠️ IMPORTANTE: Caché de Permisos

```php
// Los permisos se cargan UNA VEZ durante el login
// Se almacenan en: $_SESSION['user']['permisos_menu']
// Estructura:
$_SESSION['user']['permisos_menu'] = [
    1 => ['abrir' => true, 'guardar' => true, 'editar' => true, 'eliminar' => true],
    2 => ['abrir' => true, 'guardar' => false, 'editar' => false, 'eliminar' => false],
    // ...
];

// ⚠️ Si cambias permisos en BD, el usuario debe RE-LOGUEARSE
// para que los cambios surtan efecto.
```

### Verificar Permisos en Código

```php
// Verificar si puede acceder a un menú
if (dedumsoft_user_can_menu(2)) {
    // Usuario puede acceder a Inventario
}

// Bloquear acceso (redirige a forbidden)
require_menu_access(5); // Solo ADMIN puede ver Usuarios

// Verificar permiso específico CRUD
$permisos = $_SESSION['user']['permisos_menu'][2] ?? [];
if ($permisos['editar'] ?? false) {
    // Mostrar botón de editar
}
```

---

## 4. Estructura de Base de Datos

### Esquemas PostgreSQL

```sql
-- Tres esquemas principales:
CREATE SCHEMA joyeria;     -- Datos de negocio
CREATE SCHEMA seguridad;   -- Usuarios, roles, logs
CREATE SCHEMA public;      -- Extensiones y utilidades
```

### Tablas Principales

#### Esquema `joyeria`

| Tabla              | Propósito                    |
| ------------------ | ---------------------------- |
| `inv_insumos`      | Inventario de insumos        |
| `inv_oro`          | Inventario de oro/materiales |
| `inv_maquinaria`   | Maquinaria y equipos         |
| `ord_ordenes`      | Órdenes de producción        |
| `ord_consumos`     | Materiales consumidos        |
| `prov_proveedores` | Catálogo de proveedores      |
| `prov_compras`     | Registro de compras          |
| `cat_*`            | Tablas de catálogos          |

#### Esquema `seguridad`

| Tabla          | Propósito                     |
| -------------- | ----------------------------- |
| `seg_usuario`  | Usuarios del sistema          |
| `seg_rol`      | Roles (ADMIN, OPERADOR, etc.) |
| `seg_menu`     | Menús/módulos del sistema     |
| `seg_menurol`  | Permisos rol-menú (pivote)    |
| `seg_login`    | Logs de acceso                |
| `seg_bitacora` | Auditoría de cambios          |

### Convención de Nombres

```
Prefijo     Significado
--------    -----------
inv_        Inventario
ord_        Órdenes/Producción
prov_       Proveedores/Compras
cat_        Catálogos
seg_        Seguridad
fun_        Funciones almacenadas
```

### Funciones Almacenadas (fun\_\*)

Las operaciones complejas usan funciones PostgreSQL:

```sql
-- Ejemplo: Buscar en inventario (las funciones retornan columnas definidas)
SELECT id, nombre, cantidad, unidad
FROM joyeria.fun_inv_buscar_insumos('oro', 10, 0);

-- Patrón común:
-- fun_[modulo]_[accion]_[entidad](parámetros)
```

| Función      | Propósito                 |
| ------------ | ------------------------- |
| `fun_inv_*`  | Operaciones de inventario |
| `fun_ord_*`  | Gestión de órdenes        |
| `fun_prov_*` | Proveedores y compras     |
| `fun_seg_*`  | Seguridad y auditoría     |
| `fun_rep_*`  | Reportes                  |

**Reglas de SQL:**

- ❌ No usar `SELECT *` ni `COUNT(*)` (usar columnas explícitas y `COUNT(1)`).
- ✅ Toda función debe ser **PL/pgSQL** (no `LANGUAGE sql`).
- ✅ Si retorna múltiples filas, debe ser `RETURNS TABLE`.
- ✅ No usar migraciones: modificar **solo** los scripts base en `database/`.

**Validación obligatoria con PGlite (antes de commitear cambios SQL):**

```bash
cd database/pglite-validator
npm install
npm run validate:all
```

**Reportes usados en el dashboard (fun_rep_*):**

- `fun_reporte_ordenes_estado()` → conteo de órdenes por estado (gráficas / legacy_chart).
- `fun_reporte_ordenes_dashboard(desde, hasta)` → KPIs de órdenes activas y completadas del mes (dashboard).

---

## 5. Patrón de APIs

### Estructura Estándar

Todas las APIs siguen el mismo patrón (ubicadas en `public/api/{dominio}/`):

```php
<?php
/**
 * API de [Entidad]
 * Métodos: GET, POST, PATCH, DELETE
 */

// Cargar bootstrap (siempre primero)
require_once __DIR__ . '/../../../private/bootstrap.php';

// Dependencias
require_once PRIVATE_PATH . '/Database/Connection.php';
require_once PRIVATE_PATH . '/Http/MethodValidator.php';
require_once PRIVATE_PATH . '/Auth/AuthMiddleware.php';

header('Content-Type: application/json');

// 1. Autenticación
require_api_auth();

// 2. Validar método HTTP
if (!validateHttpMethod('POST')) {
    exit;
}

// 3. Procesar según método
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        // Listar o buscar
        break;
    case 'POST':
        // Crear nuevo
        break;
    case 'PATCH':
        // Actualizar existente
        break;
    case 'DELETE':
        // Eliminar
        break;
}

// 4. Respuesta JSON
echo json_encode($response);
```

### Respuestas Estándar

```php
// Éxito
[
    'CODIGO' => 200,
    'MENSAJE' => 'Operación exitosa',
    'DATOS' => [...]
]

// Error
[
    'CODIGO' => 400,
    'MENSAJE' => 'Descripción del error'
]
```

### Llamadas desde Frontend

```javascript
// Con Axios (recomendado)
axios.get('/api/inventario/oro.php')
    .then(res => console.log(res.data))
    .catch(err => console.error(err));

// Con jQuery (legacy)
$.ajax({
    url: '/api/inventario/oro.php',
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    success: function(data) { ... }
});

// Con utilidades internas
DsCrud.api('/api/inventario/oro.php', 'GET', null, onSuccess, onError);       // Moderno
DsCrud.apiLegacy('/api/inventario/oro.php', 'PATCH', payload, ok, fail);      // Legacy (IE8)
```

### Método Override (Legacy)

Para compatibilidad con IE8 y proxies antiguos, el backend soporta **method override**:

- `X-HTTP-Method-Override: PATCH|PUT|DELETE`
- `_method=PATCH|PUT|DELETE` en querystring

`private/bootstrap.php` aplica el override **solo si** el método original es POST.

### API de Usuarios (crear/activar)

La gestión de usuarios se realiza desde `public/usuarios.php` y la API
`public/api/usuarios.php` (Menú 5).

**Crear usuario (POST):**

```json
{
  "username": "juan",
  "nombre": "Juan Perez",
  "apellido": "Gomez",
  "especialidad_id": 3,
  "telefono": "555-1234",
  "email": "juan@empresa.com",
  "rolid": 2,
  "password": "Secreto123!"
}
```

- Para rol **OPERADOR (2)**, `apellido` es obligatorio y se crea registro en `joyeria.artesanos`.
- El hash de contraseña se genera en PHP (`password_hash`).
- Inserta vía `seguridad.fun_crear_usuario(...)`.
- La especialidad se guarda en `joyeria.artesano_especialidad` y referencia `joyeria.cat_especialidad` (fuente de verdad).
- Para cargar opciones de especialidad usar `api/catalogos/opciones.php?tipo=especialidades`.
- En `public/usuarios.php`, los campos de artesano se muestran solo cuando el rol seleccionado es **Operador**.

**Activar/Desactivar (PATCH):**

```json
{ "id": 12, "activo": false }
```

**Catálogo de especialidades (Ajustes > Catalogos):**

- API: `api/catalogos/maestros.php?catalog=especialidades` (GET/POST/PATCH/DELETE).
- Tabla fuente: `joyeria.cat_especialidad` (activo, descripción).
- UI: `public/catalogos.php?catalog=especialidades`.

**Catálogos maestros (Ajustes > Catálogos):**

- Configuración central: `private/Database/CatalogConfig.php` (define tabla, columnas, campos y orden).
- API genérica: `public/api/catalogos/maestros.php?catalog=...`.
- UI: `public/catalogos.php` (modo moderno y legacy).
- Catálogos incluidos: `areas`, `tipos_oro`, `tipos_proveedor`, `tipos_maquinaria`, `especialidades`,
  `estados_maquinaria`, `estados_orden`, `prioridades`, `tipos_material`, `niveles_calidad`, `productos`.
- Iconos: en legacy se usan PNG de `public/assets/icons/fatcow/16`, en moderno emojis definidos en el config.

### API de Órdenes de Producción (crear/asignar)

La gestión de órdenes se realiza desde `public/produccion.php` y la API
`public/api/produccion/ordenes.php` (Menú 3).

**Crear orden (POST):**

```json
{
  "producto_id": 12,
  "cantidad": 3,
  "prioridad_id": 2,
  "artesano_id": null,
  "observaciones": "Pedido urgente"
}
```

- Inserta vía `joyeria.fun_crear_orden(...)`.
- `prioridad_id` opcional (default 2 si no se envía).
- `artesano_id` es opcional y puede quedar sin asignar.
- Para poblar el formulario usar `api/catalogos/opciones.php?tipo=productos`, `prioridades` y `artesanos`.

**Asignar artesano (PATCH):**

```json
{ "id": 15, "artesano_id": 7 }
```

---

## 6. ⚠️ Compatibilidad Legacy (IE8)

### 🚨 ADVERTENCIA IMPORTANTE

Este proyecto **mantiene compatibilidad con Internet Explorer 8**.  
Esto afecta SIGNIFICATIVAMENTE cómo se escribe el código.

**Alcance:** estas restricciones **solo** aplican al JavaScript que se ejecuta
en la vista legacy (IE8). El JS moderno puede usar `const`, `let`, `fetch`, etc.

### ¿Por qué IE8?

Algunos clientes usan equipos antiguos con Windows XP/Vista que no pueden actualizarse.

### Archivos de Compatibilidad

| Archivo                   | Propósito                    |
| ------------------------- | ---------------------------- |
| `assets/css/ie8.css`      | Estilos específicos IE8      |
| `assets/js/ie8.js`        | **Polyfill JSON2** (crítico) |
| `assets/js/crud-legacy.js`| CRUD + XHR compatible IE8    |
| `public/legacy_chart.php` | Genera gráficos PNG con GD   |

### AJAX en Legacy

Usa siempre `DsCrud.apiLegacy(...)` en bloques legacy para:
- Forzar POST cuando el método sea PATCH/PUT/DELETE.
- Enviar `X-HTTP-Method-Override` + `_method` en querystring.
- Mantener compatibilidad con ActiveX XHR de IE8.

### ⚠️ NO USAR (Incompatible con IE8)

```javascript
// ❌ PROHIBIDO - No funciona en IE8

// Arrow functions
const fn = () => {};

// let/const
let x = 1;
const y = 2;

// Template literals
`Hello ${name}`;

// Destructuring
const {a, b} = obj;

// Spread operator
[...array];

// Promises
fetch('/api').then(...);

// forEach en NodeList
document.querySelectorAll('div').forEach(...);

// classList
element.classList.add('clase');
```

### ✅ USAR ESTO EN SU LUGAR

```javascript
// ✅ CORRECTO - Compatible con IE8

// Funciones tradicionales
var fn = function () {};

// Siempre var
var x = 1;
var y = 2;

// Concatenación
"Hello " + name;

// Acceso directo
var a = obj.a;
var b = obj.b;

// Bucles for tradicionales
for (var i = 0; i < array.length; i++) {}

// jQuery para AJAX
$.ajax({ url: "/api", success: function (data) {} });

// jQuery para iterar
$("div").each(function () {});

// className para clases
element.className += " clase";
```

### JSON en IE8

```javascript
// IE8 no tiene JSON nativo
// El polyfill en ie8.js provee JSON.parse y JSON.stringify

// ❌ NUNCA usar eval para JSON
var data = eval("(" + jsonString + ")");

// ✅ SIEMPRE usar JSON.parse (con polyfill)
var data = JSON.parse(jsonString);
```

### Gráficos en IE8

IE8 no soporta Canvas ni SVG modernos. Usamos **generación de PNG server-side**:

```php
// legacy_chart.php - Genera gráficos como imágenes PNG

// En el HTML:
<img src="legacy_chart.php?type=bar&data=..." alt="Gráfico">

// El servidor usa GD Library para dibujar el gráfico
// y devuelve una imagen PNG
```

### Detección de IE8

```php
// En PHP (detectar legacy y cargar assets)
$legacy = dedumsoft_is_legacy_browser();
if ($legacy) {
    // Cargar assets legacy
}
```

```javascript
// En JavaScript
var isIE8 = (function () {
  var ua = navigator.userAgent;
  return ua.indexOf("MSIE 8") !== -1 || ua.indexOf("MSIE 7") !== -1;
})();

if (isIE8) {
  // Usar fallback legacy
}
```

---

## 7. Seguridad

### Medidas Implementadas

| Medida              | Archivo                          | Descripción             |
| ------------------- | -------------------------------- | ----------------------- |
| Rate Limiting       | `private/Auth/RateLimiter.php`   | 60 req/min por IP       |
| CSRF                | `private/Auth/SessionManager.php`| Token en formularios    |
| Password Hashing    | `private/Auth/LoginService.php`  | bcrypt                  |
| Prepared Statements | Todos                            | PDO con parámetros      |
| JWT                 | `private/Auth/JwtHandler.php`    | HMAC-SHA256, 24h expiry |
| CSP Headers         | `views/layouts/header.php`       | Content-Security-Policy |
| Cookie HttpOnly     | `private/Auth/SessionManager.php`| Previene XSS            |
| Security Logging    | `private/Http/SecurityLogger.php`| Logs en Apache/PHP      |

### Logging de Seguridad

El sistema registra eventos de seguridad en los **logs de Apache/PHP** usando `error_log()`.

**Eventos registrados:**

| Evento          | Cuándo ocurre                                  |
| --------------- | ---------------------------------------------- |
| `LOGIN_SUCCESS` | Usuario inicia sesión correctamente            |
| `LOGIN_FAILED`  | Credenciales incorrectas                       |
| `RATE_LIMITED`  | IP bloqueada por demasiados intentos           |
| `ACCESS_DENIED` | Usuario intenta acceder a recurso no permitido |
| `CSRF_INVALID`  | Token CSRF ausente o incorrecto                |

**Formato del log:**

```
[DEDUMSOFT_SECURITY] EVENT_TYPE | IP | Username | URI | Details
```

**Ejemplo en logs de Apache:**

```
[Sun Jan 19 10:30:45 2026] [DEDUMSOFT_SECURITY] LOGIN_FAILED | 192.168.1.100 | juan | /public/login.php | Invalid password
[Sun Jan 19 10:31:02 2026] [DEDUMSOFT_SECURITY] LOGIN_SUCCESS | 192.168.1.100 | juan | /public/login.php | OK
[Sun Jan 19 10:35:12 2026] [DEDUMSOFT_SECURITY] ACCESS_DENIED | 192.168.1.100 | juan | /public/usuarios.php | Resource: /public/usuarios.php
```

**Ver los logs:**

```bash
# macOS
tail -f /var/log/apache2/error_log

# Linux
tail -f /var/log/apache2/error.log

# Filtrar solo eventos de seguridad
grep "DEDUMSOFT_SECURITY" /var/log/apache2/error.log

# Filtrar logins fallidos
grep "LOGIN_FAILED" /var/log/apache2/error.log
```

### Validación de Entrada

```php
// SIEMPRE usar prepared statements con columnas explícitas
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);

// NUNCA concatenar SQL ni usar SELECT *
// ❌ $sql = "SELECT * FROM users WHERE id = " . $id;
```

### Rate Limiting

```php
// En private/Auth/RateLimiter.php
// 60 req/min, bloquea si excede
```

### CSRF Token

```php
// Generar token
$csrf = generate_csrf_token();

// En formularios
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">

// Validar en backend
if (!validate_csrf_token($_POST['csrf_token'])) {
    die('Token CSRF inválido');
}
```

---

## 8. Despliegue

### Scripts Disponibles

| Script                    | Plataforma | Uso                |
| ------------------------- | ---------- | ------------------ |
| `scripts/deploy_back.sh`  | Linux/Mac  | `./deploy_back.sh` |
| `scripts/deploy_back.bat` | Windows    | `deploy_back.bat`  |

### Pasos de Despliegue

1. **Configurar entorno**

   ```bash
   cp config/env.example.php config/env.php
   # Editar env.php con credenciales reales
   ```

2. **Instalar dependencias PHP**

   ```bash
   composer install
   ```

3. **Crear base de datos**

   ```bash
   psql -U postgres -f database/install.sql
   psql -U postgres -d dedumsoft -f database/db_schema.sql
   psql -U postgres -d dedumsoft -f database/functions/*.sql
   psql -U postgres -d dedumsoft -f database/seed.sql
   ```

4. **Configurar Apache (DocumentRoot)**

   ```apache
   DocumentRoot "/path/to/Dedumsoft/public"
   <Directory "/path/to/Dedumsoft/public">
       AllowOverride All
       Require all granted
   </Directory>
   ```

5. **Configurar permisos**

   ```bash
   chmod 755 public/ private/ views/
   chmod 600 config/env.php
   ```

6. **Ejecutar script de deploy**
   ```bash
   cd scripts
   ./deploy_back.sh
   ```

### Variables de Entorno

```php
// En config/env.php
return [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '5432',
    'DB_NAME' => 'dedumsoft',
    'DB_USER' => 'app_user',
    'DB_PASS' => 'secure_password',
    'JWT_SECRET' => 'clave_secreta_32_chars_minimo',
    'ENVIRONMENT' => 'production'  // development | production
];
```

---

## 9. Gotchas y Errores Comunes

### ❌ Errores Frecuentes

#### 1. Permisos no actualizan

```
Problema: Cambié permisos en BD pero usuario sigue igual
Causa: Permisos cacheados en sesión
Solución: Usuario debe cerrar sesión y volver a entrar
```

#### 2. API retorna HTML en lugar de JSON

```
Problema: Error PHP antes del json_encode
Solución: Verificar logs de PHP, revisar require_once paths
```

#### 3. DataTables no carga

```
Problema: Tabla no se inicializa
Causa: jQuery no cargado primero
Solución: Verificar orden de scripts en header.php
```

#### 4. Error de conexión a BD

```
Problema: "Connection refused" o "Authentication failed"
Causa: Credenciales incorrectas en env.php
Solución: Verificar host, puerto, usuario, contraseña
```

#### 5. Funciones fun\_\* no encontradas

```
Problema: "function does not exist"
Causa: No se ejecutaron los scripts de functions/
Solución: Ejecutar todos los archivos en database/functions/ en orden
```

#### 6. IE8/VM no carga assets o el login apunta a localhost

```
Problema: La pagina se ve rota o el POST de login va a localhost desde una VM
Causa: SITE_URL en config/env.php sigue en localhost y <base href> reescribe rutas
Solución: Usar la IP/host real accesible por el cliente (ej: http://192.168.64.1:8080/dedumsoft)
```

### 💡 Tips de Desarrollo

1. **Siempre probar en IE8** (o emulador) antes de deploy
2. **Legacy:** usar `var` y funciones tradicionales. **Moderno:** usar `const`/`let` y funciones flecha.
3. **Revisar logs de PostgreSQL** para errores de funciones
4. **Probar APIs con Postman** antes de integrar
5. **Documentar cambios** en español siguiendo el estilo existente

### Debugging

```php
// Habilitar errores en desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log personalizado
error_log("Debug: " . print_r($variable, true));

// Ver query ejecutado
$stmt->debugDumpParams();
```

```javascript
// Console legacy-safe
if (window.console && console.log) {
  console.log("Debug:", variable);
}
```

---

## 📚 Referencias Rápidas

### Rutas Importantes

| Ruta                         | Descripción                      |
| ---------------------------- | -------------------------------- |
| `public/login.php`           | Página de login                  |
| `public/index.php`           | Dashboard principal              |
| `public/catalogos.php`       | Catálogos maestros (Ajustes)     |
| `public/api/{dominio}/*.php` | Endpoints REST                   |
| `private/Auth/*.php`         | Módulos de autenticación         |
| `private/bootstrap.php`      | Inicialización (incluir siempre) |
| `config/env.php`             | Configuración (¡NO commitear!)   |
| `views/layouts/*.php`        | Componentes reutilizables        |
| `public/assets/js/sidebar.js`| Toggle del menú lateral (móvil)  |
| `public/api/catalogos/maestros.php` | CRUD de catálogos maestros |

### Comandos Útiles

```bash
# Reiniciar BD (¡CUIDADO! Borra todo)
psql -d dedumsoft -f database/nuke_functions.sql
psql -d dedumsoft -f database/install.sql

# Ver logs PostgreSQL
tail -f /var/log/postgresql/postgresql-17-main.log

# Verificar sintaxis PHP
php -l archivo.php
```

---

## 📖 Glosario de Términos

| Término                | Significado                                                                                                |
| ---------------------- | ---------------------------------------------------------------------------------------------------------- |
| **Artesano**           | Usuario que fabrica las piezas de joyería. Tiene un `artesano_id` y acceso limitado a sus propias órdenes. |
| **Orden**              | Pedido de producción de una pieza. Pasa por estados: `pendiente` → `en_proceso` → `terminada`.             |
| **Consumo**            | Material usado en una orden (oro, piedras, insumos). Registrado en `ord_consumos`.                         |
| **Insumo**             | Material auxiliar (soldadura, lijas, etc.). Diferente de oro/piedras preciosas.                            |
| **RBAC**               | Role-Based Access Control. Sistema de permisos basado en roles (ADMIN, OPERADOR, LECTURA).                 |
| **JWT**                | JSON Web Token. Token de autenticación para APIs. Expira en 24h.                                           |
| **CSRF**               | Cross-Site Request Forgery. Ataque prevenido con tokens en formularios.                                    |
| **Rate Limiting**      | Límite de peticiones por IP (60/min) para prevenir ataques de fuerza bruta.                                |
| **Legacy**             | Código/funcionalidad para navegadores antiguos (IE8). Ver sección 6.                                       |
| **Polyfill**           | Código que agrega funcionalidad faltante a navegadores viejos (ej: JSON2 para IE8).                        |
| **fun\_\***            | Prefijo de funciones PostgreSQL almacenadas. Ej: `fun_inv_buscar_insumos()`.                               |
| **seg\_\***            | Prefijo de tablas de seguridad. Ej: `seg_usuario`, `seg_rol`, `seg_menurol`.                               |
| **inv\_\***            | Prefijo de tablas de inventario. Ej: `inv_oro`, `inv_insumos`, `inv_maquinaria`.                           |
| **ord\_\***            | Prefijo de tablas de órdenes/producción. Ej: `ord_ordenes`, `ord_consumos`.                                |
| **prov\_\***           | Prefijo de tablas de proveedores. Ej: `prov_proveedores`, `prov_compras`.                                  |
| **cat\_\***            | Prefijo de tablas de catálogos. Ej: `cat_tipos_material`, `cat_unidades`.                                  |
| **PDO**                | PHP Data Objects. Extensión para acceso a base de datos con prepared statements.                           |
| **Prepared Statement** | Query SQL con parámetros que previene SQL injection.                                                       |
| **Session Fixation**   | Ataque donde se fuerza un ID de sesión. Prevenido con `session_regenerate_id()`.                           |
| **XSS**                | Cross-Site Scripting. Ataque de inyección de JavaScript. Prevenido con `htmlspecialchars()`.               |
| **HttpOnly**           | Flag de cookie que impide acceso desde JavaScript. Protege contra XSS.                                     |
| **SameSite**           | Flag de cookie que controla envío cross-origin. Configurado como `Lax`.                                    |
| **GD Library**         | Extensión PHP para generar imágenes. Usada en `legacy_chart.php` para gráficos PNG.                        |
| **DataTables**         | Plugin jQuery para tablas interactivas con búsqueda, paginación y ordenamiento.                            |
| **uPlot**              | Librería de gráficos ligera. Usada en navegadores modernos (no IE8).                                       |
| **Notyf**              | Librería de notificaciones toast. Muestra mensajes de éxito/error.                                         |
| **Guard**              | Archivo `private/Database/Guard.php`. Protege contra inclusión directa de archivos.                        |
| **Esquema**            | Namespace en PostgreSQL. Usamos: `joyeria`, `seguridad`, `public`.                                         |

### Acrónimos Comunes en el Código

| Acrónimo    | Significado                                        |
| ----------- | -------------------------------------------------- |
| `connLogic` | Connection Logic - Conexión PDO a la base de datos |
| `stmt`      | Statement - Objeto de consulta preparada           |
| `pdo`       | PHP Data Objects                                   |
| `ENV`       | Environment - Variables de configuración           |
| `CSRF`      | Cross-Site Request Forgery                         |
| `JWT`       | JSON Web Token                                     |
| `RBAC`      | Role-Based Access Control                          |
| `CRUD`      | Create, Read, Update, Delete                       |
| `API`       | Application Programming Interface                  |
| `REST`      | Representational State Transfer                    |
| `HSTS`      | HTTP Strict Transport Security                     |
| `CSP`       | Content Security Policy                            |
| `MIME`      | Multipurpose Internet Mail Extensions              |

---

### Contacto

Para dudas sobre el sistema, revisar primero:

1. Este documento
2. Comentarios en el código fuente
3. Archivos en `database/` (scripts SQL y funciones)

---

_Última actualización: Enero 2026_
