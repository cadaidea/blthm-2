#!/bin/bash

# ============================================================
# BLETIA - Iniciar/Detener Aplicación
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_DIR="/opt/bletia"

case "$1" in
    start)
        echo -e "${GREEN}Iniciando BLETIA...${NC}"
        cd $APP_DIR/backend
        pm2 start npm --name bletia-backend -- start
        pm2 save
        echo -e "${GREEN}✓ Backend iniciado${NC}"
        ;;
    stop)
        echo -e "${YELLOW}Deteniendo BLETIA...${NC}"
        pm2 stop bletia-backend
        pm2 save
        echo -e "${GREEN}✓ Backend detenido${NC}"
        ;;
    restart)
        echo -e "${YELLOW}Reiniciando BLETIA...${NC}"
        pm2 restart bletia-backend
        echo -e "${GREEN}✓ Backend reiniciado${NC}"
        ;;
    status)
        pm2 status
        ;;
    logs)
        pm2 logs bletia-backend
        ;;
    *)
        echo "Uso: $0 {start|stop|restart|status|logs}"
        exit 1
        ;;
esac
