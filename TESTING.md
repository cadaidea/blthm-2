# 🧪 Guía de Prueba: Backend + Frontend

## 📋 Requisitos

Antes de probar, asegúrate de tener:
- ✅ Docker y Docker Compose instalados
- ✅ Node.js 20+ instalado
- ✅ Puerto 3000 (backend) y 5173 (frontend) disponibles

---

## 🚀 Paso 1: Levantar el Backend

### Opción A: Con Docker (Recomendado)

```bash
# 1. Clonar el repositorio (si no lo has hecho)
git clone https://github.com/cadaidea/blthm.git
cd blthm

# 2. Configurar variables de entorno
cp backend/.env.example backend/.env
# Editar backend/.env y cambiar JWT_SECRET

# 3. Levantar PostgreSQL y Backend
docker-compose up -d postgres backend

# 4. Esperar a que PostgreSQL esté listo (15 segundos)
sleep 15

# 5. Aplicar migraciones
docker-compose exec backend npx prisma db push

# 6. Crear usuario administrador
docker-compose exec backend npx tsx src/seed.ts
```

### Opción B: Manual (sin Docker)

```bash
# 1. Instalar PostgreSQL 16
# Ubuntu/Debian:
sudo apt update
sudo apt install postgresql-16

# 2. Crear base de datos y usuario
sudo -u postgres psql
CREATE USER bletia WITH PASSWORD 'bletia_password';
CREATE DATABASE bletia_db OWNER bletia;
\q

# 3. Configurar backend
cd backend
cp .env.example .env
# Editar .env con tus credenciales de PostgreSQL

# 4. Instalar dependencias
npm install

# 5. Generar Prisma Client
npx prisma generate

# 6. Aplicar migraciones
npx prisma db push

# 7. Crear usuario administrador
npx tsx src/seed.ts

# 8. Iniciar servidor
npm run dev
```

---

## 🧪 Paso 2: Probar la API

### Health Check

```bash
curl http://localhost:3000/health
```

Deberías ver:
```json
{
  "status": "ok",
  "timestamp": "2026-02-20T..."
}
```

### Login

```bash
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@bletia.ec",
    "password": "admin123"
  }'
```

Deberías ver:
```json
{
  "token": "eyJhbGc...",
  "user": {
    "id": "...",
    "email": "admin@bletia.ec",
    "name": "Administrador BLETIA",
    "role": "ADMIN"
  }
}
```

**Copia el token** para las siguientes peticiones.

### Obtener Usuarios (requiere autenticación)

```bash
curl http://localhost:3000/api/users \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### Crear Cliente

```bash
curl -X POST http://localhost:3000/api/customers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "name": "Cliente Demo",
    "email": "demo@ejemplo.com",
    "phone": "+593 99 123 4567",
    "taxId": "0101010101",
    "address": "Av. Principal 123",
    "city": "Cuenca"
  }'
```

### Obtener Clientes

```bash
curl http://localhost:3000/api/customers \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### Crear Producto

```bash
curl -X POST http://localhost:3000/api/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "sku": "BLT-TEST-001",
    "name": "Producto de Prueba",
    "description": "Descripción del producto",
    "category": "Sofás",
    "price": 1500.00,
    "cost": 750.00,
    "stock": 10,
    "minStock": 2
  }'
```

---

## 🎨 Paso 3: Levantar el Frontend

```bash
# En la raíz del proyecto
npm install
npm run dev
```

Abre: **http://localhost:5173**

---

## 🔐 Paso 4: Probar el Login con API

1. Abre **http://localhost:5173**
2. Ve a **http://localhost:5173/#/login-api** (nueva ruta de prueba)
3. Inicia sesión con:
   - **Email**: admin@bletia.ec
   - **Password**: admin123
4. Deberías ser redirigido al dashboard

---

## 📊 Paso 5: Probar el CRM con API

1. Ve a **http://localhost:5173/#/crm-api** (nueva ruta de prueba)
2. Deberías ver la lista de clientes cargada desde PostgreSQL
3. Prueba:
   - ✅ Crear nuevo cliente
   - ✅ Editar cliente existente
   - ✅ Eliminar cliente
   - ✅ Verificar que los cambios se reflejen en PostgreSQL

### Verificar en PostgreSQL

```bash
# Con Docker
docker-compose exec postgres psql -U bletia -d bletia_db

# Sin Docker
psql -U bletia -d bletia_db

# Luego ejecuta:
SELECT * FROM "Customer";
SELECT * FROM "Product";
SELECT * FROM "User";
```

---

## 🐛 Troubleshooting

### Error: "Cannot connect to PostgreSQL"

**Solución:**
```bash
# Verificar que PostgreSQL esté corriendo
docker-compose ps postgres

# Si no está corriendo:
docker-compose up -d postgres

# Esperar 15 segundos y verificar logs
docker-compose logs postgres
```

### Error: "Unauthorized" en la API

**Solución:**
- Verificar que el token JWT no haya expirado (7 días)
- Hacer login nuevamente: `POST /api/auth/login`
- Verificar que el header sea: `Authorization: Bearer TU_TOKEN`

### Error: "CORS" en el frontend

**Solución:**
- Verificar que `CORS_ORIGIN` en `backend/.env` sea `http://localhost:5173`
- Reiniciar el backend: `docker-compose restart backend`

### Error: "Port already in use"

**Solución:**
```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "3001:3000"  # Cambiar 3000 por otro puerto

# O matar el proceso que usa el puerto
lsof -ti:3000 | xargs kill -9
```

---

## 📝 Endpoints Disponibles

### Autenticación
- `POST /api/auth/login` → Login
- `POST /api/auth/logout` → Logout
- `GET /api/auth/me` → Usuario actual

### Usuarios (requiere ADMIN o GERENCIA)
- `GET /api/users` → Listar usuarios
- `POST /api/users` → Crear usuario

### Clientes
- `GET /api/customers` → Listar clientes
- `GET /api/customers/:id` → Obtener cliente
- `POST /api/customers` → Crear cliente
- `PUT /api/customers/:id` → Actualizar cliente
- `DELETE /api/customers/:id` → Eliminar cliente (soft delete)

### Productos
- `GET /api/products` → Listar productos
- `GET /api/products/:id` → Obtener producto
- `POST /api/products` → Crear producto
- `PUT /api/products/:id` → Actualizar producto
- `DELETE /api/products/:id` → Eliminar producto (soft delete)
- `POST /api/products/:id/images` → Agregar imagen
- `DELETE /api/products/:id/images/:imageId` → Eliminar imagen

---

## 🎯 Próximos Pasos

Después de probar que todo funciona:

1. **Migrar el CRM real** (`src/components/panel/Modules.tsx`)
   - Reemplazar `CUSTOMERS` por `useCustomersStore`
   - Reemplazar `saveCustomers` por llamadas a API

2. **Migrar el PIM** (productos)
   - Reemplazar `PRODUCTS` por `useProductsStore`
   - Conectar galería de imágenes con API

3. **Crear endpoints faltantes**
   - Órdenes de venta
   - Pagos
   - Inventarios
   - Compras

4. **Migrar datos existentes**
   - Script para migrar localStorage → PostgreSQL
   - Validar datos migrados

---

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs: `docker-compose logs backend`
2. Verifica que PostgreSQL esté corriendo: `docker-compose ps`
3. Revisa la documentación: `README.md`, `MIGRATION.md`
4. Crea un issue en GitHub

---

**Última actualización**: 2026-02-20
