#!/bin/bash

# ============================================================
# BLETIA - Script de Actualización
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/opt/bletia"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              BLETIA - Actualización                       ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Backup de la base de datos
echo -e "${YELLOW}[1/5] Creando backup de la base de datos...${NC}"
BACKUP_DIR="/root/backups"
mkdir -p $BACKUP_DIR
BACKUP_FILE="$BACKUP_DIR/bletia_db_$(date +%Y%m%d_%H%M%S).sql"
pg_dump -U bletia bletia_db > $BACKUP_FILE
echo -e "${GREEN}✓ Backup creado: $BACKUP_FILE${NC}"

# Detener aplicación
echo -e "${YELLOW}[2/5] Deteniendo aplicación...${NC}"
pm2 stop bletia-backend || true

cd $APP_DIR

# Actualizar backend
echo -e "${YELLOW}[3/5] Actualizando backend...${NC}"
cd backend
npm install
npx prisma generate
npx prisma db push

# Actualizar frontend
echo -e "${YELLOW}[4/5] Actualizando frontend...${NC}"
cd ..
npm install
npm run build

# Reiniciar aplicación
echo -e "${YELLOW}[5/5] Reiniciando aplicación...${NC}"
pm2 restart bletia-backend

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              ACTUALIZACIÓN COMPLETADA                      ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✓ Aplicación actualizada correctamente${NC}"
echo -e "${YELLOW}Backup de base de datos: $BACKUP_FILE${NC}"
