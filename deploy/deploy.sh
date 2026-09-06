#!/usr/bin/env bash
# ============================================================
# BLETIA · Despliegue a producción (repetible, sin caída)
# Uso:  bash deploy.sh            → despliega la rama configurada
#       bash deploy.sh v1.2.0     → despliega un tag específico
# ============================================================
set -euo pipefail

APP_BASE="${APP_BASE:-${HOME}/bletia}"
CONF="${APP_BASE}/deploy.conf"
REPO="${APP_BASE}/repo"

# Cargar configuración
if [ ! -f "${CONF}" ]; then
  echo "❌ Falta ${CONF}. Ejecuta primero: bash provision.sh"
  exit 1
fi
# shellcheck disable=SC1090
source "${CONF}"

TARGET="${1:-${BRANCH}}"
STAMP="$(date +%Y%m%d-%H%M%S)"
RELEASE_DIR="${APP_BASE}/releases/${STAMP}"

echo "── BLETIA · Despliegue → ${TARGET} ────────────────────────"

# 1) Respaldo automático de lo que está en línea AHORA (antes de tocar nada)
echo "[1/6] Respaldando la versión en línea…"
if [ -d "${DOC_ROOT}" ] && [ -f "${DOC_ROOT}/index.html" ]; then
  BK="${APP_BASE}/backups/pre-${STAMP}"
  mkdir -p "${BK}"
  rsync -a --delete "${DOC_ROOT}/" "${BK}/"
  echo "      ok → ${BK}"
else
  echo "      (primer despliegue: no hay versión previa que respaldar)"
fi

# 2) Traer el código
echo "[2/6] Actualizando código (${TARGET})…"
# --prune y el refspec de PRs permiten desplegar también un PR sin merge:
#   bash deploy.sh origin/pr/1/head   → publica el PR #1 tal cual está
git -C "${REPO}" fetch origin --prune --tags \
  "+refs/pull/*/head:refs/remotes/origin/pr/*"
git -C "${REPO}" checkout --detach "${TARGET}"
git -C "${REPO}" clean -fd
echo "      en $(git -C "${REPO}" rev-parse --short HEAD)"

# 3) Compilar (detecta solo si el código está en la raíz o dentro de una subcarpeta)
echo "[3/6] Compilando (npm ci + build)…"
if [ -f "${REPO}/package.json" ]; then
  APP_DIR="${REPO}"
else
  APP_DIR="$(dirname "$(find "${REPO}" -mindepth 2 -maxdepth 2 -name package.json -print -quit)")"
  if [ -z "${APP_DIR}" ] || [ "${APP_DIR}" = "." ]; then
    echo "❌ No encuentro package.json en el repo. Revisa la estructura en GitHub."
    exit 1
  fi
  echo "      código detectado en subcarpeta: $(basename "${APP_DIR}")/"
fi
( cd "${APP_DIR}" && npm ci --silent && npm run build --silent )
echo "      ok."

# 4) Armar el release (dist + fuentes licenciadas)
echo "[4/6] Preparando release ${STAMP}…"
mkdir -p "${RELEASE_DIR}"
rsync -a "${APP_DIR}/dist/" "${RELEASE_DIR}/"
# Las fuentes Geomanest viven fuera del repo (licencia) y se inyectan aquí.
if [ -d "${APP_BASE}/shared/fonts" ] && [ "$(ls -A "${APP_BASE}/shared/fonts" 2>/dev/null)" ]; then
  mkdir -p "${RELEASE_DIR}/fonts"
  rsync -a "${APP_BASE}/shared/fonts/" "${RELEASE_DIR}/fonts/"
  echo "      fuentes Geomanist inyectadas."
else
  echo "      ⚠️  sin fuentes en shared/fonts (se usará la fuente de respaldo)."
fi

# 5) Publicar (rsync atómico hacia el doc root de CloudPanel)
echo "[5/6] Publicando en ${DOC_ROOT}…"
mkdir -p "${DOC_ROOT}"
rsync -a --delete "${RELEASE_DIR}/" "${DOC_ROOT}/"
# Sello verificable: abrir bletia.ec/version.txt muestra qué hay publicado y cuándo
printf 'BLETIA · %s · commit %s · publicado %s\n' \
  "${TARGET}" "$(git -C "${REPO}" rev-parse --short HEAD)" "$(date '+%Y-%m-%d %H:%M')" \
  > "${DOC_ROOT}/version.txt"
echo "      ok. (verifica en bletia.ec/version.txt)"

# 6) Marcar versión actual + limpiar releases viejos
echo "[6/6] Limpiando…"
ln -sfn "${RELEASE_DIR}" "${APP_BASE}/current"
ls -1dt "${APP_BASE}"/releases/*/ 2>/dev/null | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf
echo "      se conservan los últimos ${KEEP_RELEASES} releases."

echo ""
echo "✅ En producción: ${TARGET} @ $(git -C "${REPO}" rev-parse --short HEAD)"
echo "   Release: ${RELEASE_DIR}"
echo "   Si algo sale mal:  bash rollback.sh"
