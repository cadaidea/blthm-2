#!/usr/bin/env bash
# ============================================================
# BLETIA · Provisión inicial del VPS (se ejecuta UNA SOLA VEZ)
# VPS: OVH Cloud · Ubuntu · 2 núcleos / 4 GB
# Uso:  bash provision.sh
# ============================================================
set -euo pipefail

APP_BASE="/home/ubuntu/bletia"
REPO_URL="https://github.com/cadaidea/blthm.git"
BRANCH="web"

echo "── BLETIA · Provisión del VPS ─────────────────────────────"

# 1) Herramientas base
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

# 4) Clonar el repositorio (rama 'web')
echo "[4/5] Clonando ${REPO_URL} (rama ${BRANCH})…"
if [ -d "${APP_BASE}/repo/.git" ]; then
  echo "      El repo ya existe, actualizando…"
  git -C "${APP_BASE}/repo" fetch --all --prune
  git -C "${APP_BASE}/repo" checkout "${BRANCH}"
  git -C "${APP_BASE}/repo" pull origin "${BRANCH}"
else
  git clone --branch "${BRANCH}" "${REPO_URL}" "${APP_BASE}/repo"
fi
echo "      ok."

# 5) Configuración de despliegue (document root de CloudPanel)
echo "[5/5] Creando deploy.conf…"
CONF="${APP_BASE}/deploy.conf"
if [ ! -f "${CONF}" ]; then
  cat > "${CONF}" <<'EOF'
# BLETIA · configuración de despliegue
# DOC_ROOT: la carpeta htdocs que CloudPanel muestra en
#           tu sitio → Settings → Document Root.
#           Ejemplos: /home/ubuntu/htdocs  ó  /home/bletia_ec-x1y2/htdocs
DOC_ROOT="/home/ubuntu/htdocs"
BRANCH="web"
KEEP_RELEASES=5
EOF
  echo "      Creado ${CONF}"
  echo ""
  echo "  ⚠️  IMPORTANTE: edita ${CONF}"
  echo "     y pon en DOC_ROOT la ruta exacta que CloudPanel muestra"
  echo "     en: tu sitio → Settings → Document Root"
else
  echo "      Ya existe ${CONF} (se conserva)."
fi

echo ""
echo "✅ Provisión completa."
echo ""
echo "Siguiente paso:"
echo "  1) Edita ${CONF} (DOC_ROOT)"
echo "  2) Copia tus 3 fuentes Geomanest a ${APP_BASE}/shared/fonts/"
echo "     (Geomanist-Regular.woff2, Geomanist-Medium.woff2, Geomanist-Bold.woff2)"
echo "  3) Ejecuta el primer despliegue: bash deploy.sh"
