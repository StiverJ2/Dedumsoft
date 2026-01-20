#!/usr/bin/env bash
# ============================================================================
# SCRIPT DE DESPLIEGUE: DEDUMSOFT (macOS/Linux)
# ============================================================================
#
# Despliega la aplicación Dedumsoft al servidor web local.
# Copia public/, private/, views/, config/, vendor/ y otros archivos necesarios.
#
# USO:
#   ./scripts/deploy_back.sh [destino]
#
# EJEMPLOS:
#   ./scripts/deploy_back.sh                              # Usa /opt/homebrew/var/www
#   ./scripts/deploy_back.sh /var/www/html                # Copia a /var/www/html/dedumsoft
#   ./scripts/deploy_back.sh /var/www/html/dedumsoft      # Copia contenido directamente
#
# NOTAS:
#   - Si el destino termina en "dedumsoft", copia el contenido directamente
#   - Si no, crea el subdirectorio dedumsoft/ en el destino
#   - Usa sudo automáticamente si no tiene permisos de escritura
#   - Preferencia: rsync > cp
#   - IMPORTANTE: Configurar Apache DocumentRoot apuntando a public/
#
# REQUISITOS:
#   - bash 4.0+
#   - rsync (opcional, usa cp si no está disponible)
#
# ESTRUCTURA DESPLEGADA:
#   destino/
#     ├── public/        <- DocumentRoot debe apuntar aquí
#     ├── private/
#     ├── views/
#     ├── config/
#     ├── vendor/
#     └── composer.json
#
# ============================================================================
set -euo pipefail

# Determinar rutas del script y repositorio
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Verificar que existe la estructura necesaria
if [ ! -d "$REPO_ROOT/public" ] || [ ! -d "$REPO_ROOT/private" ]; then
  echo "Error: public/ or private/ directory not found in: $REPO_ROOT" >&2
  echo "Please run this script from the Dedumsoft repository root." >&2
  exit 1
fi

# Procesar destino (argumento o valor por defecto)
# Detectar sistema operativo para usar ruta apropiada
if [ "$(uname)" = "Darwin" ]; then
  DEFAULT_DEST="/opt/homebrew/var/www/dedumsoft"
else
  DEFAULT_DEST="/var/www/dedumsoft"
fi

DEST_DIR="${1:-$DEFAULT_DEST}"
DEST_DIR="${DEST_DIR%/}"  # Eliminar barra final

# Usar sudo si es necesario
SUDO=""
if ! mkdir -p "$DEST_DIR" 2>/dev/null; then
  SUDO="sudo"
  echo "Using sudo for deployment..."
  $SUDO mkdir -p "$DEST_DIR"
fi

# Copiar archivos (rsync preferido, cp como fallback)
echo "Deploying Dedumsoft to $DEST_DIR..."

if command -v rsync >/dev/null 2>&1; then
  # Copiar con rsync (más eficiente)
  $SUDO rsync -a --delete \
    --exclude='database/' \
    --exclude='scripts/' \
    --exclude='.git*' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    "$REPO_ROOT/public" \
    "$REPO_ROOT/private" \
    "$REPO_ROOT/views" \
    "$REPO_ROOT/config" \
    "$REPO_ROOT/vendor" \
    "$REPO_ROOT/composer.json" \
    "$REPO_ROOT/composer.lock" \
    "$REPO_ROOT/.htaccess" \
    "$DEST_DIR/"
else
  # Copiar con cp (fallback)
  $SUDO cp -R "$REPO_ROOT/public" "$DEST_DIR/"
  $SUDO cp -R "$REPO_ROOT/private" "$DEST_DIR/"
  $SUDO cp -R "$REPO_ROOT/views" "$DEST_DIR/"
  $SUDO cp -R "$REPO_ROOT/config" "$DEST_DIR/"
  $SUDO cp -R "$REPO_ROOT/vendor" "$DEST_DIR/"
  $SUDO cp "$REPO_ROOT/composer.json" "$DEST_DIR/"
  $SUDO cp "$REPO_ROOT/composer.lock" "$DEST_DIR/"
  $SUDO cp "$REPO_ROOT/.htaccess" "$DEST_DIR/"
fi

# Configurar permisos (644 para archivos, 755 para directorios)
echo "Setting permissions..."
$SUDO find "$DEST_DIR" -type d -exec chmod 755 {} \;
$SUDO find "$DEST_DIR" -type f -exec chmod 644 {} \;

echo ""
echo "✅ Deployment complete!"
echo ""
echo "📌 CONFIGURATION OPTIONS:"
echo ""
echo "   Option A - Apache Config (Recommended):"
echo "     DocumentRoot \"$DEST_DIR/public\""
echo ""
echo "   Option B - Shared Hosting (using .htaccess):"
echo "     Deploy to www/ as-is. Root .htaccess redirects to public/"
echo "     No additional configuration needed."
echo ""
echo "📌 Deployed files:"
echo "   - .htaccess   <- Routes to public/"
echo "   - public/     <- WEB ROOT"
echo "   - private/    <- PHP modules"
echo "   - views/      <- Templates"
echo "   - config/     <- Configuration"
echo "   - vendor/     <- Dependencies"
echo ""
