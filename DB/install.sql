-- ============================================
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

\echo ''
\echo '3. Cargando datos iniciales...'
\i seed.sql

\echo ''
\echo '============================================'
\echo 'Instalación completada exitosamente!'
\echo 'Usuario: admin'
\echo 'Password: Admin123!'
\echo '============================================'
