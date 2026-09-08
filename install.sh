#!/bin/bash

# ============================================
# BLETIA - Script de Instalación Rápida
# ============================================

set -e

echo "🚀 BLETIA - Instalación Rápida"
echo "================================"
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ============================================
# VERIFICAR REQUISITOS
# ============================================

echo "📋 Verificando requisitos..."

# Verificar Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker no está instalado${NC}"
    echo "Instala Docker desde: https://docs.docker.com/get-docker/"
    exit 1
fi
echo -e "${GREEN}✅ Docker instalado${NC}"

# Verificar Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose no está instalado${NC}"
    echo "Instala Docker Compose desde: https://docs.docker.com/compose/install/"
    exit 1
fi
echo -e "${GREEN}✅ Docker Compose instalado${NC}"

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js no está instalado${NC}"
    echo "Instala Node.js 20+ desde: https://nodejs.org/"
    exit 1
fi

NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 20 ]; then
    echo -e "${RED}❌ Node.js versión $NODE_VERSION detectada (se requiere 20+)${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Node.js $NODE_VERSION instalado${NC}"

echo ""

# ============================================
# CONFIGURAR VARIABLES DE ENTORNO
# ============================================

echo "⚙️  Configurando variables de entorno..."

# Backend
if [ ! -f backend/.env ]; then
    cp backend/.env.example backend/.env
    
    # Generar JWT_SECRET aleatorio
    JWT_SECRET=$(openssl rand -base64 32)
    sed -i "s/cambia-este-secret-en-produccion-usa-openssl-rand-base64-32/$JWT_SECRET/" backend/.env
    
    echo -e "${GREEN}✅ backend/.env creado${NC}"
else
    echo -e "${YELLOW}⚠️  backend/.env ya existe${NC}"
fi

# Frontend
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ .env creado${NC}"
else
    echo -e "${YELLOW}⚠️  .env ya existe${NC}"
fi

echo ""

# ============================================
# INSTALAR DEPENDENCIAS
# ============================================

echo "📦 Instalando dependencias..."

# Frontend
echo "Instalando dependencias del frontend..."
npm install
echo -e "${GREEN}✅ Frontend instalado${NC}"

# Backend
echo "Instalando dependencias del backend..."
cd backend
npm install
cd ..
echo -e "${GREEN}✅ Backend instalado${NC}"

echo ""

# ============================================
# LEVANTAR SERVICIOS
# ============================================

echo "🐳 Levantando servicios con Docker..."

# Levantar PostgreSQL y Backend
docker-compose up -d postgres backend

echo "Esperando a que PostgreSQL esté listo..."
sleep 15

# Verificar que PostgreSQL esté corriendo
if ! docker-compose ps postgres | grep -q "Up"; then
    echo -e "${RED}❌ PostgreSQL no inició correctamente${NC}"
    docker-compose logs postgres
    exit 1
fi
echo -e "${GREEN}✅ PostgreSQL corriendo${NC}"

# Verificar que Backend esté corriendo
if ! docker-compose ps backend | grep -q "Up"; then
    echo -e "${RED}❌ Backend no inició correctamente${NC}"
    docker-compose logs backend
    exit 1
fi
echo -e "${GREEN}✅ Backend corriendo${NC}"

echo ""

# ============================================
# APLICAR MIGRACIONES
# ============================================

echo "🗄️  Aplicando migraciones de base de datos..."

docker-compose exec -T backend npx prisma db push

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones aplicadas${NC}"
else
    echo -e "${RED}❌ Error al aplicar migraciones${NC}"
    exit 1
fi

echo ""

# ============================================
# CREAR DATOS INICIALES
# ============================================

echo "🌱 Creando datos iniciales..."

docker-compose exec -T backend npx tsx src/seed.ts

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Datos iniciales creados${NC}"
else
    echo -e "${RED}❌ Error al crear datos iniciales${NC}"
    exit 1
fi

echo ""

# ============================================
# LEVANTAR FRONTEND (DESARROLLO)
# ============================================

echo "🎨 Levantando frontend en modo desarrollo..."
echo -e "${YELLOW}⚠️  El frontend se está iniciando en segundo plano${NC}"
echo -e "${YELLOW}   Presiona Ctrl+C para detenerlo${NC}"
echo ""

npm run dev &

sleep 5

echo ""
echo "================================"
echo -e "${GREEN}✅ INSTALACIÓN COMPLETADA${NC}"
echo "================================"
echo ""
echo "📍 Accesos:"
echo "   Frontend:  http://localhost:5173"
echo "   Backend:   http://localhost:3000"
echo "   API Docs:  http://localhost:3000/health"
echo ""
echo "🔐 Credenciales:"
echo "   Email:    admin@bletia.ec"
echo "   Password: admin123"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "   1. Cambia la contraseña del administrador inmediatamente"
echo "   2. Configura tu propio JWT_SECRET en backend/.env"
echo "   3. Para producción, configura Nginx y SSL"
echo ""
echo "📖 Documentación:"
echo "   - README.md (instalación y uso)"
echo "   - PROGRESS.md (progreso del proyecto)"
echo ""
echo "🛑 Para detener todos los servicios:"
echo "   docker-compose down"
echo ""
