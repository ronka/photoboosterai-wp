#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# zip-plugin.sh — Build a WordPress.org-ready zip of photobooster-ai
#
# Usage:
#   ./bin/zip-plugin.sh              # outputs to project root
#   ./bin/zip-plugin.sh ~/Desktop    # outputs to a custom directory
#
# The script always rebuilds the React app first so the dist is fresh.
# ---------------------------------------------------------------------------

set -euo pipefail

PLUGIN_SLUG="ecommerce-product-photo-booster-ai"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
OUT_DIR="${1:-"$PLUGIN_DIR/.."}"
OUT_DIR="$(cd "$OUT_DIR" && pwd)"
ZIP_PATH="$OUT_DIR/${PLUGIN_SLUG}.zip"

echo "==> Plugin directory : $PLUGIN_DIR"
echo "==> Output zip       : $ZIP_PATH"
echo ""

# ---------------------------------------------------------------------------
# 1. Build the React app
# ---------------------------------------------------------------------------
REACT_APP_DIR="$PLUGIN_DIR/admin/react-app"

if [ ! -d "$REACT_APP_DIR" ]; then
    echo "ERROR: React app not found at $REACT_APP_DIR" >&2
    exit 1
fi

echo "==> Installing npm dependencies…"
npm --prefix "$REACT_APP_DIR" ci --silent

echo "==> Building React app…"
npm --prefix "$REACT_APP_DIR" run build

echo ""

# ---------------------------------------------------------------------------
# 2. Create the zip (from the plugins directory so the zip contains
#    photobooster-ai/ as the top-level folder — required by WordPress)
# ---------------------------------------------------------------------------
PLUGINS_DIR="$(dirname "$PLUGIN_DIR")"

rm -f "$ZIP_PATH"

echo "==> Creating zip…"
cd "$PLUGINS_DIR"

zip -r "$ZIP_PATH" "$PLUGIN_SLUG" \
    --exclude "$PLUGIN_SLUG/.git/*" \
    --exclude "$PLUGIN_SLUG/.gitignore" \
    --exclude "$PLUGIN_SLUG/.cursor/*" \
    --exclude "$PLUGIN_SLUG/bin/*" \
    --exclude "$PLUGIN_SLUG/review.md" \
    --exclude "$PLUGIN_SLUG/admin/react-app/*" \
    --exclude "*/.DS_Store" \
    --exclude "*/__MACOSX/*" \
    --exclude "*/node_modules/*" \
    --exclude "*/Thumbs.db"

echo ""
echo "==> Done!"
echo "    $(du -sh "$ZIP_PATH" | cut -f1)  $ZIP_PATH"
