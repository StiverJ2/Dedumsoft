-- ============================================================================
-- DEDUMSOFT - SCRIPT DE INSTALACIÓN COMPLETA
-- ============================================================================
--
-- Instala todo el sistema de base de datos desde cero.
-- Ejecuta los scripts en el orden correcto para evitar dependencias rotas.
--
-- PREREQUISITOS:
-- 1. PostgreSQL 17+ instalado y corriendo
-- 2. Base de datos creada: createdb db_dedumsoft
-- 3. Usuario con permisos de creación de esquemas y tablas
--
-- EJECUCIÓN:
--   cd DB
--   psql -d db_dedumsoft -f install.sql
--
-- ORDEN DE EJECUCIÓN:
-- 1. db_schema.sql - Crea esquemas y tablas
-- 2. functions/*.sql - Instala funciones almacenadas
-- 3. seed.sql - Carga datos iniciales
--
-- USUARIOS CREADOS:
-- - admin / Admin123! (rol ADMIN - acceso total)
-- - artesano / Artesano123! (rol OPERADOR - producción)
--
-- ============================================================================
-- DEDUMSOFT - INSTALACIÓN COMPLETA
-- Fecha: 2026-01-18
-- ============================================
-- Este script instala todo el sistema desde cero.
-- Ejecutar con: psql -d db_dedumsoft -f DB/install.sql
-- ============================================

\echo '============================================'
\echo 'DEDUMSOFT - Instalación de Base de Datos'
\echo '============================================'

\echo ''
\echo '1. Creando schema y tablas...'
\i db_schema.sql

\echo ''
\echo '2. Instalando funciones...'
\i functions/01_triggers.sql
\i functions/02_auth.sql
\i functions/03_inventario.sql
\i functions/04_proveedores.sql
\i functions/05_ordenes.sql
\i functions/06_reportes.sql
\i functions/07_ubicaciones.sql
\i functions/08_catalog_functions.sql
\i functions/09_artesano.sql

\echo ''
\echo '3. Cargando datos iniciales...'
\i seed.sql

\echo ''
\echo '============================================'
\echo 'Instalación completada exitosamente!'
\echo ''
\echo 'Usuarios creados:'
\echo '  - admin / Admin123! (ADMIN)'
\echo '  - artesano / Artesano123! (OPERADOR)'
\echo '============================================'
