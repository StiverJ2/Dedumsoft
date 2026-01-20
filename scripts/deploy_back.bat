@echo off
rem ============================================================================
rem SCRIPT DE DESPLIEGUE: DEDUMSOFT (Windows)
rem ============================================================================
rem
rem Despliega la aplicación Dedumsoft al servidor web local (Apache/IIS).
rem Copia public\, private\, views\, config\, vendor\ y otros archivos necesarios.
rem
rem USO:
rem   scripts\deploy_back.bat [destino]
rem
rem EJEMPLOS:
rem   scripts\deploy_back.bat                               REM Usa C:\Apache24\htdocs\dedumsoft
rem   scripts\deploy_back.bat C:\inetpub\wwwroot\myproject  REM Copia a C:\inetpub\wwwroot\myproject
rem
rem NOTAS:
rem   - Copia el contenido directamente al destino especificado
rem   - Robocopy retorna codigos de error especiales (0-7 = exito)
rem   - IMPORTANTE: Configurar Apache DocumentRoot apuntando a public\
rem
rem REQUISITOS:
rem   - Windows con robocopy (incluido desde Vista)
rem
rem ESTRUCTURA DESPLEGADA:
rem   destino\
rem     +-- public\        <- DocumentRoot debe apuntar aquí
rem     +-- private\
rem     +-- views\
rem     +-- config\
rem     +-- vendor\
rem     +-- composer.json
rem
rem ============================================================================
setlocal enabledelayedexpansion

rem Determinar rutas del script y repositorio
set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..") do set "REPO_ROOT=%%~fI"

rem Verificar que existe la estructura necesaria
if not exist "%REPO_ROOT%\public" (
  echo Error: public\ directory not found in: %REPO_ROOT% 1>&2
  echo Please run this script from the Dedumsoft repository root. 1>&2
  exit /b 1
)
if not exist "%REPO_ROOT%\private" (
  echo Error: private\ directory not found in: %REPO_ROOT% 1>&2
  exit /b 1
)

rem Procesar destino (argumento o valor por defecto)
set "DEST_DIR=%~1"
if "%DEST_DIR%"=="" set "DEST_DIR=C:\Apache24\htdocs\dedumsoft"

rem Normalizar barra final
if "%DEST_DIR:~-1%"=="\" set "DEST_DIR=%DEST_DIR:~0,-1%"

rem Crear directorio destino si no existe
if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

echo Deploying Dedumsoft to %DEST_DIR%...

rem Copiar directorios con robocopy
rem /E = recursivo, /XD = excluir directorios, /XF = excluir archivos
robocopy "%REPO_ROOT%\public" "%DEST_DIR%\public" /E /XD .git
robocopy "%REPO_ROOT%\private" "%DEST_DIR%\private" /E
robocopy "%REPO_ROOT%\views" "%DEST_DIR%\views" /E
robocopy "%REPO_ROOT%\config" "%DEST_DIR%\config" /E
robocopy "%REPO_ROOT%\vendor" "%DEST_DIR%\vendor" /E

rem Copiar archivos individuales
copy /Y "%REPO_ROOT%\composer.json" "%DEST_DIR%\" >nul
copy /Y "%REPO_ROOT%\composer.lock" "%DEST_DIR%\" >nul
copy /Y "%REPO_ROOT%\.htaccess" "%DEST_DIR%\" >nul

rem Robocopy: codigos 0-7 son exito, 8+ son errores
if %ERRORLEVEL% GEQ 8 (
  exit /b %ERRORLEVEL%
)

echo.
echo ============================================================================
echo   DEPLOYMENT COMPLETE!
echo ============================================================================
echo.
echo   CONFIGURATION OPTIONS:
echo.
echo   Option A - Apache Config (Recommended):
echo     DocumentRoot "%DEST_DIR%\public"
echo.
echo   Option B - Shared Hosting (using .htaccess):
echo     Deploy to www\ as-is. Root .htaccess redirects to public\
echo     No additional configuration needed.
echo.
echo   Deployed files:
echo   - public\     ^<- WEB ROOT
echo   - private\    ^<- PHP modules
echo   - views\      ^<- Templates
echo   - config\     ^<- Configuration
echo   - vendor\     ^<- Dependencies
echo.
echo ============================================================================
echo.
