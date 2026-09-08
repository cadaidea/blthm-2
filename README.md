# BLETIA - Sistema ERP Completo

Sistema ERP 100% open source para mueblerías. Sin límites de facturación, usuarios o transacciones. Seguridad bancaria, interfaz amigable tipo NetSuite.

## 🎉 ¡NUEVO! Fase 2: Backend con PostgreSQL

Ahora BLETIA cuenta con un **backend real** usando PostgreSQL, Fastify y autenticación JWT. Todo el sistema está listo para producción con seguridad bancaria.

### ¿Qué hay de nuevo?
- ✅ **Backend API REST** completo con Fastify
- ✅ **PostgreSQL 16** como base de datos
- ✅ **Autenticación JWT** segura
- ✅ **Sistema de auditoría** completo
- ✅ **Docker** para despliegue fácil
- ✅ **Cliente API** en el frontend
- ✅ **Store Zustand** para estado global

### Instalación rápida (2 minutos)
```bash
# Clonar repositorio
git clone https://github.com/cadaidea/blthm.git
cd blthm

# Ejecutar instalador automático
chmod +x install.sh
./install.sh
```

El instalador automáticamente:
1. Verifica requisitos (Docker, Node.js)
2. Configura variables de entorno
3. Instala dependencias
4. Levanta PostgreSQL y Backend
5. Aplica migraciones
6. Crea usuario administrador
7. Inicia el frontend

**Accede a**: http://localhost:5173  
**Credenciales**: admin@bletia.ec / admin123

## 🎯 Stack Tecnológico

### Backend
- **Fastify** → API REST ultra rápida
- **PostgreSQL 16** → Base de datos (seguridad bancaria)
- **Prisma** → ORM type-safe
- **JWT + bcrypt** → Autenticación segura
- **Docker** → Deploy portable

### Frontend
- **React 18 + TypeScript** → UI moderna
- **Vite** → Build ultra rápido
- **Tailwind CSS 4** → Estilos
- **Zustand** → Estado global

### Todo 100% Open Source
- Sin licencias
- Sin límites de usuarios
- Sin límites de transacciones
- Sin costos ocultos

## 🚀 Instalación Rápida

### Requisitos
- Docker y Docker Compose
- Node.js 20+ (solo para desarrollo)

### 1. Clonar el repositorio
```bash
git clone https://github.com/cadaidea/blthm.git
cd blthm
```

### 2. Configurar variables de entorno
```bash
# Backend
cp backend/.env.example backend/.env
# Editar backend/.env y cambiar:
# - JWT_SECRET (generar con: openssl rand -base64 32)
# - DATABASE_URL (si usas PostgreSQL externo)

# Frontend
cp .env.example .env
# Editar .env y cambiar VITE_API_URL si es necesario
```

### 3. Levantar con Docker (recomendado)
```bash
# Levantar PostgreSQL + Backend
docker-compose up -d postgres backend

# Esperar a que PostgreSQL esté listo (10-20 segundos)
sleep 15

# Aplicar migraciones de base de datos
docker-compose exec backend npx prisma db push

# Crear usuario administrador inicial
docker-compose exec backend npx tsx src/seed.ts
```

### 4. Levantar Frontend (desarrollo)
```bash
# Instalar dependencias
npm install

# Levantar en modo desarrollo
npm run dev
```

### 5. Acceder
- **Frontend**: http://localhost:5173
- **API**: http://localhost:3000
- **Health Check**: http://localhost:3000/health

### Credenciales iniciales
- **Email**: admin@bletia.ec
- **Password**: admin123
- **Rol**: ADMIN

⚠️ **IMPORTANTE**: Cambia la contraseña inmediatamente después del primer login.

## 📦 Instalación Manual (sin Docker)

### 1. PostgreSQL
```bash
# Instalar PostgreSQL 16
# Ubuntu/Debian:
sudo apt update
sudo apt install postgresql-16

# Crear base de datos y usuario
sudo -u postgres psql
CREATE USER bletia WITH PASSWORD 'bletia_password';
CREATE DATABASE bletia_db OWNER bletia;
\q
```

### 2. Backend
```bash
cd backend

# Instalar dependencias
npm install

# Configurar variables de entorno
cp .env.example .env
# Editar .env con tus valores

# Generar Prisma Client
npx prisma generate

# Aplicar migraciones
npx prisma db push

# Crear usuario administrador
npx tsx src/seed.ts

# Iniciar servidor
npm run dev
```

### 3. Frontend
```bash
# En la raíz del proyecto
npm install
npm run dev
```

## 🔒 Seguridad

### Características de seguridad bancaria
- ✅ Contraseñas hasheadas con bcrypt (10 rondas)
- ✅ Tokens JWT con expiración
- ✅ Auditoría completa de todas las acciones
- ✅ Soft delete (datos nunca se borran físicamente)
- ✅ Validación estricta con Zod
- ✅ CORS configurado
- ✅ Rate limiting (próximamente)
- ✅ HTTPS obligatorio en producción

### Roles y permisos
- **ADMIN**: Acceso total (crear usuarios, ver todo)
- **GERENCIA**: Acceso a reportes, clientes, ventas
- **VENTAS**: Clientes, cotizaciones, pedidos
- **TALLER**: Órdenes de producción, inventarios
- **LOGISTICA**: Inventarios, movimientos, envíos
- **CONTABILIDAD**: Facturación, pagos, reportes financieros

## 📊 Módulos del Sistema

### ✅ Implementados
1. **Autenticación** → Login, logout, roles
2. **Usuarios** → CRUD completo
3. **Clientes (CRM)** → Gestión de clientes
4. **Productos (PIM)** → Catálogo con galería y variantes
5. **Fototeca (DAM)** → Gestión de imágenes

### 🚧 En desarrollo
6. **Ventas (OMS)** → Órdenes de venta
7. **Pagos** → Integración con PayPhone
8. **Facturación** → Integración SRI Ecuador
9. **Inventarios** → Multi-bodega
10. **Compras** → Órdenes de compra
11. **Producción (MRP)** → Órdenes de fabricación
12. **Contabilidad** → Partida doble
13. **Reportes** → Dashboards ejecutivos
14. **RRHH** → Nómina, contratos
15. **Auditoría** → Logs completos

## 🌐 Producción

### Deploy en VPS (OVH, AWS, etc.)

```bash
# 1. Clonar en el servidor
git clone https://github.com/cadaidea/blthm.git
cd blthm

# 2. Configurar variables de entorno
cp backend/.env.example backend/.env
nano backend/.env
# Cambiar:
# - JWT_SECRET (generar uno fuerte)
# - CORS_ORIGIN (tu dominio)
# - DATABASE_URL (si usas PostgreSQL externo)

# 3. Levantar servicios
docker-compose up -d postgres backend

# 4. Esperar a que PostgreSQL esté listo
sleep 15

# 5. Aplicar migraciones
docker-compose exec backend npx prisma db push

# 6. Crear usuario administrador
docker-compose exec backend npx tsx src/seed.ts

# 7. Configurar Nginx como reverse proxy
# Ver sección "Configuración Nginx" abajo

# 8. Configurar SSL con Let's Encrypt
sudo certbot --nginx -d bletia.ec -d www.bletia.ec
```

### Configuración Nginx
```nginx
server {
    listen 80;
    server_name bletia.ec www.bletia.ec;

    # Frontend (React)
    location / {
        proxy_pass http://localhost:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Backend API
    location /api {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Backups automáticos
```bash
# Crear script de backup
nano /usr/local/bin/bletia-backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/bletia"
mkdir -p $BACKUP_DIR

# Backup de PostgreSQL
docker-compose exec -T postgres pg_dump -U bletia bletia_db | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Mantener solo últimos 30 días
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

echo "Backup completado: $BACKUP_DIR/db_$DATE.sql.gz"
```

```bash
# Dar permisos
chmod +x /usr/local/bin/bletia-backup.sh

# Agregar a crontab (backup diario a las 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/bletia-backup.sh") | crontab -
```

## 🛠️ Desarrollo

### Comandos útiles
```bash
# Backend
cd backend
npm run dev          # Modo desarrollo con hot reload
npm run build        # Compilar para producción
npm run start        # Ejecutar versión compilada

# Base de datos
npx prisma studio    # Interfaz visual de la BD
npx prisma generate  # Regenerar Prisma Client
npx prisma db push   # Aplicar cambios del schema

# Frontend
npm run dev          # Modo desarrollo
npm run build        # Compilar para producción
npm run preview      # Preview de producción
```

### Estructura del proyecto
```
blthm/
├── backend/              # API REST (Fastify)
│   ├── src/
│   │   └── index.ts     # Servidor principal
│   ├── prisma/
│   │   └── schema.prisma # Esquema de BD
│   ├── Dockerfile
│   └── package.json
├── src/                  # Frontend (React)
│   ├── components/
│   ├── data.ts
│   └── App.tsx
├── docker-compose.yml    # Orquestación
├── Dockerfile.frontend   # Frontend en Docker
└── package.json
```

## 📝 Migración desde localStorage

El sistema actual usa localStorage para persistir datos. La Fase 2 migrará todo a PostgreSQL:

1. ✅ Backend API creado
2. ✅ Esquema de base de datos definido
3. ✅ Autenticación JWT implementada
4. 🚧 Conectar frontend a API (reemplazar localStorage)
5. 🚧 Script de migración de datos
6. 🚧 Testing completo

## 🔧 Troubleshooting

### PostgreSQL no inicia
```bash
# Ver logs
docker-compose logs postgres

# Reiniciar
docker-compose restart postgres
```

### Backend no conecta a la BD
```bash
# Verificar que PostgreSQL esté corriendo
docker-compose ps

# Verificar credenciales en backend/.env
cat backend/.env | grep DATABASE_URL
```

### Puerto ocupado
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "3001:3000"  # Cambiar 3000 por otro puerto
```

## 📞 Soporte

- **Documentación**: [Wiki del proyecto](https://github.com/cadaidea/blthm/wiki)
- **Issues**: [GitHub Issues](https://github.com/cadaidea/blthm/issues)
- **Email**: soporte@bletia.ec

## 📄 Licencia

MIT License - Libre para uso comercial y personal.

## 🎓 Roadmap

### Q1 2026
- [ ] Conectar frontend a API
- [ ] Migración de datos localStorage → PostgreSQL
- [ ] Módulo de ventas completo
- [ ] Integración PayPhone

### Q2 2026
- [ ] Facturación electrónica SRI
- [ ] Módulo de inventarios multi-bodega
- [ ] Órdenes de compra
- [ ] Reportes básicos

### Q3 2026
- [ ] Módulo de producción (MRP)
- [ ] Contabilidad partida doble
- [ ] RRHH y nómina
- [ ] Dashboards ejecutivos

### Q4 2026
- [ ] App móvil (React Native)
- [ ] Integración WhatsApp Business
- [ ] API pública para integraciones
- [ ] Marketplace de plugins

---

**Hecho con ❤️ en Ecuador**
