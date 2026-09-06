#!/usr/bin/env bash
# ============================================================
# BLETIA · Rollback instantáneo (volver a la versión anterior)
# Uso:  bash rollback.sh          → release inmediatamente anterior
#       bash rollback.sh 20260220-1030   → un release específico
# ============================================================
set -euo pipefail

APP_BASE="/home/ubuntu/bletia"
CONF="${APP_BASE}/deploy.conf"

if [ ! -f "${CONF}" ]; then
  echo "❌ Falta ${CONF}. Ejecuta primero: bash provision.sh"
  exit 1
fi
# shellcheck disable=SC1090
source "${CONF}"

echo "── BLETIA · Rollback ──────────────────────────────────────"

# Release objetivo: el indicado, o el anterior al 'current'
if [ -n "${1:-}" ]; then
  TARGET="${APP_BASE}/releases/${1}"
else
  CURRENT="$(readlink -f "${APP_BASE}/current" 2>/dev/null || true)"
  TARGET="$(ls -1dt "${APP_BASE}"/releases/*/ 2>/dev/null | grep -v "^${CURRENT}/$" | head -n1)"
fi

if [ -z "${TARGET}" ] || [ ! -d "${TARGET}" ]; then
  # No hay releases: intentar el último respaldo pre-despliegue
  LAST_BK="$(ls -1dt "${APP_BASE}"/backups/pre-*/ 2>/dev/null | head -n1)"
  if [ -n "${LAST_BK}" ]; then
    echo "Sin releases disponibles. Restaurando respaldo ${LAST_BK}…"
    rsync -a --delete "${LAST_BK}" "${DOC_ROOT}/"
    echo "✅ Restaurado desde respaldo."
    exit 0
  fi
  echo "❌ No hay releases ni respaldos a los que volver."
  exit 1
fi

TARGET="${TARGET%/}"
echo "Volviendo a: ${TARGET}"
rsync -a --delete "${TARGET}/" "${DOC_ROOT}/"
ln -sfn "${TARGET}" "${APP_BASE}/current"
echo ""
echo "✅ Rollback completado. El sitio ya sirve la versión anterior."
