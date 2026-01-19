@echo off
rem ============================================================================
rem SCRIPT DE DESPLIEGUE: BACKEND (Windows)
rem ============================================================================
rem
rem Despliega el directorio Back\ al servidor web local (Apache/IIS).
rem Usa robocopy para copiar archivos de forma eficiente.
rem
rem USO:
rem   scripts\deploy_back.bat [destino]
rem
rem EJEMPLOS:
rem   scripts\deploy_back.bat                          REM Usa C:\Apache24\htdocs
rem   scripts\deploy_back.bat C:\inetpub\wwwroot       REM Copia a ...\Back
rem   scripts\deploy_back.bat C:\Apache24\htdocs\Back  REM Copia contenido directamente
rem
rem NOTAS:
rem   - Si el destino termina en "Back", copia el contenido directamente
rem   - Si no, crea el subdirectorio Back\ en el destino
rem   - Robocopy retorna codigos de error especiales (0-7 = exito)
rem
rem REQUISITOS:
rem   - Windows con robocopy (incluido desde Vista)
rem
rem ============================================================================
setlocal enabledelayedexpansion

rem Determinar rutas del script y repositorio
rem Usage: scripts\deploy_back.bat [C:\Apache24\htdocs[\Back]]
set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..") do set "REPO_ROOT=%%~fI"
set "SRC_DIR=%REPO_ROOT%\Back"

rem Verificar que existe el directorio fuente
if not exist "%SRC_DIR%" (
  echo Back directory not found: "%SRC_DIR%" 1>&2
  exit /b 1
)

rem Procesar destino (argumento o valor por defecto)
set "DEST_INPUT=%~1"
if "%DEST_INPUT%"=="" set "DEST_INPUT=C:\Apache24\htdocs"

rem Normalizar barra final
rem Normalize trailing backslash
if "%DEST_INPUT:~-1%"=="\" set "DEST_INPUT=%DEST_INPUT:~0,-1%"

rem Determinar si copiar contenido o crear subdirectorio
for %%I in ("%DEST_INPUT%") do set "DEST_BASENAME=%%~nxI"
if /I "%DEST_BASENAME%"=="Back" (
  set "DEST_DIR=%DEST_INPUT%"
) else (
  set "DEST_DIR=%DEST_INPUT%\Back"
)

rem Crear directorio destino si no existe
if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

rem Copiar archivos con robocopy (/E = recursivo incluyendo vacios)
robocopy "%SRC_DIR%" "%DEST_DIR%" /E

rem Robocopy: codigos 0-7 son exito, 8+ son errores
if %ERRORLEVEL% GEQ 8 (
  exit /b %ERRORLEVEL%
)

echo Back deployed to %DEST_DIR%
