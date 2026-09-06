#!/usr/bin/env bash
# ============================================================
# BLETIA · Provisión inicial del VPS (se ejecuta UNA SOLA VEZ)
# VPS: OVH Cloud · Ubuntu · 2 núcleos / 4 GB · CloudPanel
#
# Uso:
#   bash provision.sh
#   (te pedirá 3 datos: repo de GitHub, rama y carpeta htdocs)
# ============================================================
set -euo pipefail

APP_BASE="${APP_BASE:-${HOME}/bletia}"
CONF="${APP_BASE}/deploy.conf"

echo "── BLETIA · Provisión del VPS ─────────────────────────────"
echo ""
echo "Necesito 3 datos. Si no los tienes a mano, pulsa Enter para"
echo "usar el valor sugerido entre corchetes [ ]."
echo ""
read -rp "1) URL del repo en GitHub [https://github.com/cadaidea/bletia-web.git]: " REPO_URL
REPO_URL="${REPO_URL:-https://github.com/cadaidea/bletia-web.git}"
read -rp "2) Rama que tiene el código [main]: " BRANCH
BRANCH="${BRANCH:-main}"
read -rp "3) Carpeta htdocs del sitio (CloudPanel → sitio → Settings → Document Root) [${HOME}/htdocs]: " DOC_ROOT
DOC_ROOT="${DOC_ROOT:-${HOME}/htdocs}"
echo ""
echo "NOTA: Si ya clonaste el repo en el paso anterior (con tu token en"
echo "la dirección), pulsa Enter en las preguntas 4 y 5 para saltarlas."
echo "Solo respóndelas si este script debe descargar el repo por primera"
echo "vez. El token se crea en GitHub → Settings → Developer settings →"
echo "Personal access tokens → Tokens (classic) → Generate → marca 'repo'."
echo ""
read -rp "4) Tu usuario de GitHub (Enter = saltar) [cadaidea]: " GH_USER
GH_USER="${GH_USER:-cadaidea}"
read -rsp "5) Pega tu Personal Access Token (Enter = saltar): " GH_TOKEN
echo ""
if [ -n "${GH_TOKEN}" ]; then
  # URL autenticada: permite clonar y que los futuros 'git fetch' funcionen
  AUTH_URL="${REPO_URL/https:\/\/github.com\//https://${GH_USER}:${GH_TOKEN}@github.com/}"
else
  AUTH_URL="${REPO_URL}"
fi

# 1) Herramientas base
echo ""
echo "[1/5] Instalando git, curl, rsync…"
sudo apt-get update -qq
sudo apt-get install -y -qq git curl rsync >/dev/null
echo "      ok."

# 2) Node.js 22 (LTS) — solo si no existe o es viejo
echo "[2/5] Verificando Node.js 22…"
if ! command -v node >/dev/null 2>&1 || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 22 ]]; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - >/dev/null
  sudo apt-get install -y -qq nodejs >/dev/null
fi
echo "      Node $(node -v) · npm $(npm -v)"

# 3) Estructura de directorios
echo "[3/5] Creando estructura en ${APP_BASE}…"
mkdir -p "${APP_BASE}"/{releases,shared/fonts,backups}
echo "      ok."

# 4) Clonar el repositorio (con la llave, sin mostrarla en pantalla)
echo "[4/5] Clonando ${REPO_URL} (rama ${BRANCH})…"
if [ -d "${APP_BASE}/repo/.git" ]; then
  echo "      El repo ya existe, actualizando…"
  # Solo se toca la dirección si se entregó un token nuevo;
  # si no, se conserva la que ya tiene la llave incrustada.
  if [ -n "${GH_TOKEN}" ]; then
    git -C "${APP_BASE}/repo" remote set-url origin "${AUTH_URL}"
  fi
  git -C "${APP_BASE}/repo" fetch --all --prune
  git -C "${APP_BASE}/repo" checkout "${BRANCH}"
  git -C "${APP_BASE}/repo" pull origin "${BRANCH}"
else
  git clone --branch "${BRANCH}" "${AUTH_URL}" "${APP_BASE}/repo"
fi
echo "      ok."

# 5) Configuración de despliegue
echo "[5/5] Guardando configuración…"
cat > "${CONF}" <<EOF
# BLETIA · configuración de despliegue (generada por provision.sh)
DOC_ROOT="${DOC_ROOT}"
BRANCH="${BRANCH}"
KEEP_RELEASES=5
EOF
echo "      ${CONF}"

echo ""
echo "✅ Provisión completa."
echo ""
echo "Siguiente paso:"
echo "  1) Copia tus 3 fuentes Geomanest a ${APP_BASE}/shared/fonts/"
echo "     (Geomanist-Regular.woff2, Geomanist-Medium.woff2, Geomanist-Bold.woff2)"
echo "  2) Ejecuta el primer despliegue: bash deploy.sh"
