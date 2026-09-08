# 🎉 BLETIA - Sistema ERP Completo

## ✅ Estado Actual del Proyecto

### Fase 1: Frontend Completo ✅ (100%)
- ✅ 17 módulos funcionales
- ✅ Diseño responsive
- ✅ Sistema de roles y permisos
- ✅ Integración simulada con PayPhone
- ✅ Cuentas de cliente con seguimiento de pedidos

### Fase 2: Backend + API ✅ (100%)
- ✅ **Backend API completo** (Fastify + PostgreSQL)
- ✅ **Esquema de base de datos** (20+ tablas)
- ✅ **Autenticación JWT** segura
- ✅ **Sistema de auditoría** completo
- ✅ **Docker** para despliegue fácil
- ✅ **Cliente API** en frontend
- ✅ **Store Zustand** para estado global
- ✅ **CRM migrado** a API (ejemplo funcional)

### Fase 3: Integraciones ⏳ (Pendiente)
- ⏳ PayPhone (cobros reales)
- ⏳ SRI (facturación electrónica)
- ⏳ SMTP (correos transaccionales)

---

## 🚀 Instalación Rápida

### Opción 1: Instalador Automático (Recomendado)

```bash
# Clonar repositorio
git clone https://github.com/cadaidea/blthm.git
cd blthm

# Ejecutar instalador
chmod +x install.sh
./install.sh
```

El instalador automáticamente:
1. ✅ Verifica requisitos (Docker, Node.js)
2. ✅ Configura variables de entorno
3. ✅ Instala dependencias
4. ✅ Levanta PostgreSQL y Backend
5. ✅ Aplica migraciones
6. ✅ Crea usuario administrador
7. ✅ Inicia el frontend

**Accede a**: http://localhost:5173  
**Credenciales**: admin@bletia.ec / admin123

### Opción 2: Instalación Manual

#### Backend
```bash
cd backend
npm install
cp .env.example .env
# Editar .env con tus credenciales

npx prisma generate
npx prisma db push
npx tsx src/seed.ts
npm run dev
```

#### Frontend
```bash
npm install
npm run dev
```

---

## 🧪 Cómo Probar la Migración a API

### 1. Levantar Backend
```bash
cd backend
npm run dev
```

### 2. Levantar Frontend
```bash
npm run dev
```

### 3. Probar Login con API
- Abre: **http://localhost:5173/#/login-api**
- Login: `admin@bletia.ec` / `admin123`
- Deberías ser redirigido al dashboard

### 4. Probar CRM con API
- Abre: **http://localhost:5173/#/crm-api**
- Verás la lista de clientes cargada desde PostgreSQL
- Prueba crear, editar y eliminar clientes
- Verifica en PostgreSQL: `SELECT * FROM "Customer";`

### 5. Alternar entre localStorage y API
- En el panel, busca el **botón de servidor** (arriba a la derecha)
- Clic para alternar entre localStorage y API
- Cuando está activo (verde), usa PostgreSQL
- Cuando está inactivo, usa localStorage

---

## 📊 Módulos del Sistema

### ✅ Implementados (Frontend)
1. ✅ **Dashboard** → Panel de control
2. ✅ **CRM** → Gestión de clientes (migrado a API)
3. ✅ **PIM** → Catálogo de productos
4. ✅ **DAM** → Fototeca
5. ✅ **OMS** → Órdenes de venta
6. ✅ **Logística** → Guías de remisión
7. ✅ **Taller** → Órdenes de fabricación
8. ✅ **BOM** → Lista de materiales
9. ✅ **Stock** → Inventarios
10. ✅ **Compras** → Órdenes de compra
11. ✅ **Contabilidad** → Facturación SRI
12. ✅ **Marketing** → Email marketing
13. ✅ **CMS** → Blog y páginas
14. ✅ **Sitio Público** → Configuración
15. ✅ **RRHH** → Nómina
16. ✅ **Seguridad** → LOPDP
17. ✅ **Infraestructura** → Despliegue

### ✅ Implementados (Backend API)
- ✅ Autenticación (login, logout, me)
- ✅ Usuarios (CRUD)
- ✅ Clientes (CRUD)
- ✅ Productos (CRUD + imágenes)

### 🚧 Pendientes (Backend API)
- 🚧 Órdenes de venta
- 🚧 Pagos
- 🚧 Inventarios
- 🚧 Compras
- 🚧 Producción
- 🚧 Contabilidad
- 🚧 RRHH

---

## 🔐 Seguridad

### Implementada
- ✅ Contraseñas hasheadas (bcrypt, 10 rondas)
- ✅ Tokens JWT con expiración (7 días)
- ✅ Auditoría de todas las acciones
- ✅ Soft delete (datos nunca se borran)
- ✅ Validación estricta con Zod
- ✅ CORS configurado
- ✅ Roles y permisos (6 roles)

### Pendiente
- 🚧 Rate limiting
- 🚧 2FA (autenticación de dos factores)
- 🚧 Encriptación de datos sensibles

---

## 💰 Costos

**TODO GRATIS:**
- ✅ PostgreSQL (open source)
- ✅ Fastify (open source)
- ✅ Prisma (open source)
- ✅ React (open source)
- ✅ Docker (open source)
- ✅ Sin límites de usuarios
- ✅ Sin límites de transacciones
- ✅ Sin costos de licencia

**Solo pagas:**
- VPS OVH (ya lo tienes)
- Dominio bletia.ec (ya lo tienes)

---

## 📚 Documentación

- **README.md** → Este archivo (instalación y uso)
- **PROGRESS.md** → Progreso detallado del proyecto
- **MIGRATION.md** → Guía de migración localStorage → API
- **TESTING.md** → Guía completa de pruebas
- **backend/.env.example** → Variables de entorno del backend
- **.env.example** → Variables de entorno del frontend

---

## 🛠️ Comandos Útiles

### Backend
```bash
cd backend
npm run dev          # Modo desarrollo
npm run build        # Compilar para producción
npm run start        # Ejecutar versión compilada

# Base de datos
npx prisma studio    # Interfaz visual
npx prisma generate  # Regenerar Prisma Client
npx prisma db push   # Aplicar cambios del schema
```

### Frontend
```bash
npm run dev          # Modo desarrollo
npm run build        # Compilar para producción
npm run preview      # Preview de producción
```

### Docker
```bash
docker-compose up -d          # Levantar servicios
docker-compose down           # Detener servicios
docker-compose logs backend   # Ver logs del backend
docker-compose ps             # Ver estado de servicios
```

---

## 📈 Próximos Pasos

### Inmediatos (esta semana)
1. **Migrar PIM** (productos) a API
   - Reemplazar `PRODUCTS` por `useProductsStore`
   - Conectar galería de imágenes con API
   - Tiempo: 1 hora

2. **Completar endpoints API**
   - Órdenes de venta (CRUD)
   - Pagos (CRUD)
   - Inventarios (CRUD)
   - Tiempo: 4 horas

3. **Migrar OMS** (órdenes de venta)
   - Crear endpoints en backend
   - Crear store en frontend
   - Migrar componente
   - Tiempo: 3 horas

### Corto Plazo (1-2 meses)
- Integración PayPhone
- Facturación electrónica SRI
- Módulo de inventarios completo
- Reportes básicos

### Mediano Plazo (3-6 meses)
- Módulo de producción (MRP)
- Contabilidad partida doble
- RRHH y nómina
- Dashboards ejecutivos

### Largo Plazo (6-12 meses)
- App móvil (React Native)
- Integración WhatsApp Business
- API pública
- Marketplace de plugins

---

## 🆘 Troubleshooting

### PostgreSQL no inicia
```bash
docker-compose logs postgres
docker-compose restart postgres
```

### Backend no conecta a la BD
```bash
docker-compose ps
cat backend/.env | grep DATABASE_URL
```

### Puerto ocupado
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "3001:3000"  # Cambiar 3000 por otro puerto
```

### Error CORS
```bash
# Verificar CORS_ORIGIN en backend/.env
# Reiniciar backend
docker-compose restart backend
```

---

## 📞 Soporte

- **Documentación**: Wiki del proyecto
- **Issues**: GitHub Issues
- **Email**: soporte@bletia.ec

---

## 📄 Licencia

MIT License - Libre para uso comercial y personal.

---

**Hecho con ❤️ en Ecuador**

**Versión**: 1.3.0 (frontend) + 1.0.0 (backend)  
**Última actualización**: 2026-02-20
