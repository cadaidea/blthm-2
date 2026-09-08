#!/bin/bash

# ============================================================
# BLETIA - Script de Instalación Completa para VPS OVH Cloud
# Instalación directa sin GitHub
# ============================================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         BLETIA - Instalación en VPS OVH Cloud             ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: Este script debe ejecutarse como root${NC}"
    echo "Ejecuta: sudo bash install-vps.sh"
    exit 1
fi

# Variables de configuración
DB_NAME="bletia_db"
DB_USER="bletia"
DB_PASS="Bletia2026Secure!"
JWT_SECRET=$(openssl rand -base64 32)
DOMAIN="bletia.ec"
APP_DIR="/opt/bletia"

echo -e "${YELLOW}[1/10] Actualizando sistema...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}[2/10] Instalando dependencias básicas...${NC}"
apt install -y curl wget git nano ufw fail2ban postgresql postgresql-contrib

echo -e "${YELLOW}[3/10] Instalando Node.js 20 LTS...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

echo -e "${YELLOW}[4/10] Instalando PM2 (gestor de procesos)...${NC}"
npm install -g pm2

echo -e "${YELLOW}[5/10] Configurando PostgreSQL...${NC}"
systemctl start postgresql
systemctl enable postgresql

# Crear usuario y base de datos
sudo -u postgres psql <<EOF
CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';
CREATE DATABASE $DB_NAME OWNER $DB_USER;
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\q
EOF

echo -e "${GREEN}✓ PostgreSQL configurado${NC}"

echo -e "${YELLOW}[6/10] Creando directorio de la aplicación...${NC}"
mkdir -p $APP_DIR
cd $APP_DIR

echo -e "${YELLOW}[7/10] Configurando firewall...${NC}"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 3000/tcp
ufw --force enable

echo -e "${YELLOW}[8/10] Instalando Certbot para SSL...${NC}"
apt install -y certbot python3-certbot-nginx

echo -e "${YELLOW}[9/10] Creando archivos de configuración...${NC}"

# Crear archivo .env para el backend
cat > $APP_DIR/backend/.env <<EOF
DATABASE_URL="postgresql://$DB_USER:$DB_PASS@localhost:5432/$DB_NAME"
JWT_SECRET="$JWT_SECRET"
CORS_ORIGIN="https://$DOMAIN"
PORT=3000
HOST=0.0.0.0
NODE_ENV=production
EOF

echo -e "${GREEN}✓ Archivos de configuración creados${NC}"

echo -e "${YELLOW}[10/10] Instalación completada${NC}"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              INSTALACIÓN COMPLETADA                        ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Información importante:${NC}"
echo "  • Base de datos: $DB_NAME"
echo "  • Usuario DB: $DB_USER"
echo "  • Contraseña DB: $DB_PASS"
echo "  • Directorio app: $APP_DIR"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "  1. Sube los archivos del proyecto a $APP_DIR"
echo "  2. Ejecuta: bash $APP_DIR/setup-app.sh"
echo "  3. Configura Nginx: bash $APP_DIR/setup-nginx.sh"
echo ""
echo -e "${GREEN}¡Listo para continuar!${NC}"
