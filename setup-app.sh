#!/bin/bash

# ============================================================
# BLETIA - Configuración de la Aplicación
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/opt/bletia"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         BLETIA - Configuración de la Aplicación           ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

cd $APP_DIR

echo -e "${YELLOW}[1/6] Instalando dependencias del backend...${NC}"
cd backend
npm install

echo -e "${YELLOW}[2/6] Generando cliente Prisma...${NC}"
npx prisma generate

echo -e "${YELLOW}[3/6] Aplicando migraciones de base de datos...${NC}"
npx prisma db push

echo -e "${YELLOW}[4/6] Creando datos iniciales...${NC}"
npx tsx src/seed.ts

echo -e "${YELLOW}[5/6] Instalando dependencias del frontend...${NC}"
cd ..
npm install

echo -e "${YELLOW}[6/6] Compilando frontend...${NC}"
npm run build

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         APLICACIÓN CONFIGURADA CORRECTAMENTE              ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "  1. Iniciar el backend: cd $APP_DIR/backend && pm2 start npm --name bletia-backend -- start"
echo "  2. Configurar Nginx: bash $APP_DIR/setup-nginx.sh"
echo ""
