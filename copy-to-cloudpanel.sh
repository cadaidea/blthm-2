#!/bin/bash

# ============================================================
# BLETIA - Copiar archivos a CloudPanel
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/opt/bletia"
HTDOCS_DIR="/home/bletiaec/htdocs/www.bletia.ec"
SITE_USER="bletiaec"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         BLETIA - Copiar a CloudPanel                      ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar que el frontend está compilado
if [ ! -d "$APP_DIR/dist" ]; then
    echo -e "${RED}Error: El frontend no está compilado${NC}"
    echo -e "${YELLOW}Ejecuta primero: cd $APP_DIR && npm run build${NC}"
    exit 1
fi

echo -e "${YELLOW}[1/3] Copiando archivos a $HTDOCS_DIR...${NC}"
cp -r $APP_DIR/dist/* $HTDOCS_DIR/

echo -e "${YELLOW}[2/3] Estableciendo permisos...${NC}"
chown -R $SITE_USER:$SITE_USER $HTDOCS_DIR/
chmod -R 755 $HTDOCS_DIR/

echo -e "${YELLOW}[3/3] Verificando...${NC}"
if [ -f "$HTDOCS_DIR/index.html" ]; then
    echo -e "${GREEN}✓ index.html encontrado${NC}"
else
    echo -e "${RED}✗ index.html NO encontrado${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ARCHIVOS COPIADOS                            ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✓ Frontend copiado a $HTDOCS_DIR${NC}"
echo -e "${GREEN}✓ Permisos establecidos para $SITE_USER${NC}"
echo ""
echo -e "${YELLOW}Ahora configura el proxy para /api en CloudPanel:${NC}"
echo "  1. Ve a CloudPanel → Sites → bletia.ec → Config"
echo "  2. Agrega la configuración del proxy (ver INSTALACION-VPS.md)"
echo ""
