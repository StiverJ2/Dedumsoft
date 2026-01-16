@echo off
setlocal enabledelayedexpansion

rem Usage: scripts\deploy_back.bat [C:\Apache24\htdocs[\Back]]
set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..") do set "REPO_ROOT=%%~fI"
set "SRC_DIR=%REPO_ROOT%\Back"

if not exist "%SRC_DIR%" (
  echo Back directory not found: "%SRC_DIR%" 1>&2
  exit /b 1
)

set "DEST_INPUT=%~1"
if "%DEST_INPUT%"=="" set "DEST_INPUT=C:\Apache24\htdocs"

rem Normalize trailing backslash
if "%DEST_INPUT:~-1%"=="\" set "DEST_INPUT=%DEST_INPUT:~0,-1%"

for %%I in ("%DEST_INPUT%") do set "DEST_BASENAME=%%~nxI"
if /I "%DEST_BASENAME%"=="Back" (
  set "DEST_DIR=%DEST_INPUT%"
) else (
  set "DEST_DIR=%DEST_INPUT%\Back"
)

if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

robocopy "%SRC_DIR%" "%DEST_DIR%" /E
if %ERRORLEVEL% GEQ 8 (
  exit /b %ERRORLEVEL%
)

echo Back deployed to %DEST_DIR%
