#!/usr/bin/env bash
# ============================================================================
# SCRIPT DE DESPLIEGUE: BACKEND (macOS/Linux)
# ============================================================================
#
# Despliega el directorio Back/ al servidor web local.
# Soporta rsync (preferido) o cp como fallback.
#
# USO:
#   ./scripts/deploy_back.sh [destino]
#
# EJEMPLOS:
#   ./scripts/deploy_back.sh                           # Usa /opt/homebrew/var/www
#   ./scripts/deploy_back.sh /var/www/html             # Copia a /var/www/html/Back
#   ./scripts/deploy_back.sh /var/www/html/Back        # Copia contenido directamente
#
# NOTAS:
#   - Si el destino termina en "Back", copia el contenido directamente
#   - Si no, crea el subdirectorio Back/ en el destino
#   - Usa sudo automáticamente si no tiene permisos de escritura
#   - Preferencia: rsync > cp
#
# REQUISITOS:
#   - bash 4.0+
#   - rsync (opcional, usa cp si no está disponible)
#
# ============================================================================
set -euo pipefail

# Determinar rutas del script y repositorio
# Usage: scripts/deploy_back.sh [/opt/homebrew/var/www[/Back]]
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SRC_DIR="$REPO_ROOT/Back"

# Verificar que existe el directorio fuente
if [ ! -d "$SRC_DIR" ]; then
  echo "Back directory not found: $SRC_DIR" >&2
  exit 1
fi

# Procesar destino (argumento o valor por defecto)
DEST_INPUT="${1:-/opt/homebrew/var/www}"
DEST_INPUT="${DEST_INPUT%/}"  # Eliminar barra final

# Determinar si copiar contenido o directorio completo
if [ "$(basename "$DEST_INPUT")" = "Back" ]; then
  COPY_SRC="$SRC_DIR/"   # Copiar contenido (nota la barra final)
  DEST_DIR="$DEST_INPUT"
else
  COPY_SRC="$SRC_DIR"    # Copiar directorio completo
  DEST_DIR="$DEST_INPUT"
fi

# Usar sudo si es necesario
SUDO=""
if ! mkdir -p "$DEST_DIR" 2>/dev/null; then
  SUDO="sudo"
  $SUDO mkdir -p "$DEST_DIR"
fi

# Copiar archivos (rsync preferido, cp como fallback)
if command -v rsync >/dev/null 2>&1; then
  $SUDO rsync -a "$COPY_SRC" "$DEST_DIR/"
else
  if [ "$COPY_SRC" = "$SRC_DIR/" ]; then
    $SUDO cp -R "$SRC_DIR"/. "$DEST_DIR"/
  else
    $SUDO cp -R "$SRC_DIR" "$DEST_DIR"/
  fi
fi

echo "Back deployed to $DEST_DIR"
