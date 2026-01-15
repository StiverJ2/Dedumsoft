#!/usr/bin/env bash
set -euo pipefail

# Usage: scripts/deploy_back.sh [/opt/homebrew/var/www[/Back]]
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SRC_DIR="$REPO_ROOT/Back"

if [ ! -d "$SRC_DIR" ]; then
  echo "Back directory not found: $SRC_DIR" >&2
  exit 1
fi

DEST_INPUT="${1:-/opt/homebrew/var/www}"
DEST_INPUT="${DEST_INPUT%/}"

if [ "$(basename "$DEST_INPUT")" = "Back" ]; then
  COPY_SRC="$SRC_DIR/"
  DEST_DIR="$DEST_INPUT"
else
  COPY_SRC="$SRC_DIR"
  DEST_DIR="$DEST_INPUT"
fi

SUDO=""
if ! mkdir -p "$DEST_DIR" 2>/dev/null; then
  SUDO="sudo"
  $SUDO mkdir -p "$DEST_DIR"
fi

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
