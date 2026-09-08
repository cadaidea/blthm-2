#!/bin/bash

# ============================================================
# BLETIA - Script para subir archivos al VPS
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

VPS_IP="54.39.21.79"
VPS_USER="root"
DEST_DIR="/opt/bletia"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         BLETIA - Subir archivos al VPS                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "package.json" ]; then
    echo -e "${RED}Error: Este script debe ejecutarse desde el directorio del proyecto${NC}"
    exit 1
fi

echo -e "${YELLOW}Subiendo archivos a $VPS_USER@$VPS_IP:$DEST_DIR${NC}"
echo ""

# Crear directorio en el VPS si no existe
ssh $VPS_USER@$VPS_IP "mkdir -p $DEST_DIR"

# Subir archivos excluyendo node_modules y dist
rsync -avz --progress \
    --exclude 'node_modules' \
    --exclude 'dist' \
    --exclude '.git' \
    --exclude '*.log' \
    --exclude '.env' \
    . $VPS_USER@$VPS_IP:$DEST_DIR/

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ARCHIVOS SUBIDOS CORRECTAMENTE               ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "  1. Conéctate al VPS: ssh $VPS_USER@$VPS_IP"
echo "  2. Ve al directorio: cd $DEST_DIR"
echo "  3. Ejecuta la instalación: bash install-vps.sh"
echo ""
