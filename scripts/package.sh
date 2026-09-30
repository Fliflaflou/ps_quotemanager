#!/usr/bin/env bash
# Reproducible packaging for the myquotemanager PrestaShop module.
# Usage: scripts/package.sh [--with-tests]
#   --with-tests   Include the tests/ directory in the shipped zip (excluded by default).
set -euo pipefail

MODULE_NAME="myquotemanager"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$MODULE_DIR"

WITH_TESTS=0
for arg in "$@"; do
  case "$arg" in
    --with-tests) WITH_TESTS=1 ;;
    *) echo "Option inconnue : $arg" >&2; exit 1 ;;
  esac
done

VERSION=$(grep -oP "\\\$this->version\s*=\s*'\K[^']+" myquotemanager.php || true)
if [ -z "$VERSION" ]; then
  echo "Impossible de lire la version depuis myquotemanager.php" >&2
  exit 1
fi

echo "==> Packaging ${MODULE_NAME} v${VERSION}"

echo "==> Lint PHP..."
lint_failed=0
while IFS= read -r -d '' file; do
  if ! lint_output=$(php -l "$file" 2>&1); then
    echo "  ERREUR: $file"
    echo "$lint_output"
    lint_failed=1
  fi
done < <(find . -path ./dist -prune -o -name "*.php" -print0)

if [ "$lint_failed" -ne 0 ]; then
  echo "Lint PHP en echec, packaging annule." >&2
  exit 1
fi
echo "  OK"

DIST_DIR="dist"
STAGE_DIR="${DIST_DIR}/${MODULE_NAME}"
ZIP_NAME="${MODULE_NAME}-${VERSION}.zip"

rm -rf "$DIST_DIR"
mkdir -p "$STAGE_DIR"

echo "==> Copie des fichiers..."
TAR_EXCLUDES=(
  --exclude='.git'
  --exclude='.gitignore'
  --exclude='.github'
  --exclude='*.bak'
  --exclude='dist'
  --exclude='node_modules'
  --exclude='scripts'
)
if [ "$WITH_TESTS" -eq 0 ]; then
  TAR_EXCLUDES+=(--exclude='tests')
fi

tar -c "${TAR_EXCLUDES[@]}" -f - . | (cd "$STAGE_DIR" && tar -xf -)

echo "==> Creation du zip..."
rm -f "${DIST_DIR}/${ZIP_NAME}"
(cd "$DIST_DIR" && zip -rq "${ZIP_NAME}" "${MODULE_NAME}")

SHA256=$(sha256sum "${DIST_DIR}/${ZIP_NAME}" | awk '{print $1}')
echo "${SHA256}  ${ZIP_NAME}" > "${DIST_DIR}/${ZIP_NAME}.sha256"

echo "==> Termine : ${DIST_DIR}/${ZIP_NAME}"
echo "SHA-256: ${SHA256}"
