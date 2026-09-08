#!/bin/bash

# ============================================================
# BLETIA - Configuración de Nginx
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

DOMAIN="bletia.ec"
APP_DIR="/opt/bletia"
HTDOCS_DIR="/home/bletiaec/htdocs/www.bletia.ec"

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              BLETIA - Configuración Nginx                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}[1/4] Creando configuración de Nginx...${NC}"

# Crear configuración de Nginx
cat > /etc/nginx/sites-available/bletia.ec <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;

    # Frontend (archivos estáticos)
    root $APP_DIR/dist;
    index index.html;

    # Backend API
    location /api {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }

    # Health check
    location /health {
        proxy_pass http://localhost:3000/health;
    }

    # Frontend - todas las demás rutas
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Cache para assets estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

echo -e "${GREEN}✓ Configuración de Nginx creada${NC}"

echo -e "${YELLOW}[2/4] Activando sitio...${NC}"
ln -sf /etc/nginx/sites-available/bletia.ec /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

echo -e "${YELLOW}[3/4] Verificando configuración...${NC}"
nginx -t

echo -e "${YELLOW}[4/4] Reiniciando Nginx...${NC}"
systemctl restart nginx

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              NGINX CONFIGURADO                             ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Para activar SSL (HTTPS):${NC}"
echo "  certbot --nginx -d $DOMAIN -d www.$DOMAIN"
echo ""
echo -e "${GREEN}¡Configuración completada!${NC}"
