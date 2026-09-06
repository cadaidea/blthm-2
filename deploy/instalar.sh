#!/usr/bin/env bash
# ============================================================
# BLETIA · Instalar actualización desde ZIP
# (el zip se sube por el File Manager de CloudPanel)
# Uso:  bash instalar.sh /ruta/del/archivo.zip
# ============================================================
set -euo pipefail

APP_BASE="${APP_BASE:-${HOME}/bletia}"
CONF="${APP_BASE}/deploy.conf"
if [ -f "${CONF}" ]; then
  # shellcheck disable=SC1090
  source "${CONF}"
fi
DOC_ROOT="${DOC_ROOT:-/home/blthm/htdocs/bletia.ec}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

ZIP="${1:-}"
if [ -z "${ZIP}" ]; then
  echo "Uso: bash instalar.sh /ruta/del/archivo.zip"
  echo "Ejemplo: bash instalar.sh /home/blthm/bletia-web.zip"
  exit 1
fi
if [ ! -f "${ZIP}" ]; then
  echo "❌ No encuentro el archivo: ${ZIP}"
  echo "   Verifica la ruta exacta en el File Manager de CloudPanel."
  exit 1
fi

command -v unzip >/dev/null 2>&1 || { echo "Instalando unzip…"; apt-get install -y -qq unzip; }

STAMP="$(date +%Y%m%d-%H%M%S)"
WORK="${APP_BASE}/work-${STAMP}"
RELEASE_DIR="${APP_BASE}/releases/${STAMP}"

echo "── BLETIA · Instalando $(basename "${ZIP}") ─────────────"

echo "[1/6] Respaldando la versión que está en línea…"
if [ -f "${DOC_ROOT}/index.html" ]; then
  BK="${APP_BASE}/backups/pre-${STAMP}"
  mkdir -p "${BK}"
  rsync -a --delete "${DOC_ROOT}/" "${BK}/"
  echo "      ok → ${BK}"
else
  echo "      (primer despliegue: no hay versión previa)"
fi

echo "[2/6] Descomprimiendo el zip…"
mkdir -p "${WORK}"
unzip -q -o "${ZIP}" -d "${WORK}"

PKG="$(find "${WORK}" -mindepth 1 -maxdepth 3 -name package.json -print -quit || true)"
if [ -z "${PKG}" ]; then
  echo "❌ El zip no contiene un proyecto (no encuentro package.json)."
  rm -rf "${WORK}"; exit 1
fi
PKG="$(dirname "${PKG}")"
echo "      proyecto localizado."

echo "[3/6] Instalando dependencias (npm ci)… la primera vez tarda 1–2 min"
( cd "${PKG}" && npm ci --silent )

echo "[4/6] Compilando (npm run build)…"
( cd "${PKG}" && npm run build --silent )

echo "[5/6] Preparando la versión ${STAMP}…"
mkdir -p "${RELEASE_DIR}"
rsync -a "${PKG}/dist/" "${RELEASE_DIR}/"
if [ -d "${APP_BASE}/shared/fonts" ] && [ -n "$(ls -A "${APP_BASE}/shared/fonts" 2>/dev/null)" ]; then
  mkdir -p "${RELEASE_DIR}/fonts"
  rsync -a "${APP_BASE}/shared/fonts/" "${RELEASE_DIR}/fonts/"
  echo "      fuentes Geomanist inyectadas."
fi

echo "[6/6] Publicando en ${DOC_ROOT}…"
mkdir -p "${DOC_ROOT}"
rsync -a --delete "${RELEASE_DIR}/" "${DOC_ROOT}/"
ln -sfn "${RELEASE_DIR}" "${APP_BASE}/current"
ls -1dt "${APP_BASE}"/releases/*/ 2>/dev/null | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf
rm -rf "${WORK}"

echo ""
echo "✅ LISTO · Publicado: $(basename "${ZIP}")"
echo "   Versión en línea: ${RELEASE_DIR}"
echo "   Si algo sale mal:  bash ${APP_BASE}/rollback.sh"
