# Guía de Migración: localStorage → API

## 📋 Resumen

El sistema está migrando de localStorage (Fase 1) a PostgreSQL con API REST (Fase 2). Esta guía explica cómo completar la migración.

## ✅ Estado Actual

### Lo que YA está listo
- ✅ Backend API completo (Fastify + PostgreSQL)
- ✅ Cliente API en frontend (`src/api/client.ts`)
- ✅ Store Zustand (`src/store/index.ts`)
- ✅ Autenticación JWT
- ✅ Endpoints para: usuarios, clientes, productos

### Lo que FALTA
- 🚧 Reemplazar llamadas a localStorage por llamadas a API
- 🚧 Migrar datos existentes de localStorage a PostgreSQL
- 🚧 Completar endpoints para: órdenes, pagos, inventarios, etc.

---

## 🔧 Cómo Migrar un Módulo

### Ejemplo: Migrar Clientes (CRM)

#### Antes (localStorage)
```typescript
// src/components/panel/Modules.tsx
import { CUSTOMERS, saveCustomers } from "../../data";

export function CRM() {
  const [list, setList] = useState<Customer[]>(CUSTOMERS);
  
  const addCustomer = (data: Customer) => {
    const newList = [...list, data];
    setList(newList);
    saveCustomers(newList); // Guarda en localStorage
  };
}
```

#### Después (API)
```typescript
// src/components/panel/Modules.tsx
import { useCustomersStore } from "../../store";

export function CRM() {
  const { customers, fetchCustomers, addCustomer } = useCustomersStore();
  
  useEffect(() => {
    fetchCustomers(); // Carga desde API
  }, []);
  
  const handleAdd = async (data: Omit<Customer, 'id' | 'code' | 'createdAt' | 'updatedAt'>) => {
    await addCustomer(data); // Guarda en PostgreSQL vía API
  };
}
```

---

## 📝 Pasos para Migrar Cada Módulo

### 1. Autenticación ✅ (YA MIGRADO)
- **Store**: `useAuthStore`
- **API**: `auth.login()`, `auth.logout()`, `auth.me()`
- **Componente**: `src/components/panel/auth.tsx`

### 2. Usuarios ✅ (YA MIGRADO)
- **Store**: `useUsersStore`
- **API**: `users.getAll()`, `users.create()`
- **Componente**: `src/components/panel/Modules8.tsx` (RRHH)

### 3. Clientes 🚧 (PENDIENTE)
- **Store**: `useCustomersStore` (YA CREADO)
- **API**: `customers.getAll()`, `customers.create()`, etc. (YA CREADO)
- **Componente**: `src/components/panel/Modules.tsx` (CRM)
- **Acción**: Reemplazar `CUSTOMERS` y `saveCustomers()` por `useCustomersStore()`

### 4. Productos 🚧 (PENDIENTE)
- **Store**: `useProductsStore` (YA CREADO)
- **API**: `products.getAll()`, `products.create()`, etc. (YA CREADO)
- **Componente**: `src/components/panel/Modules.tsx` (PIM)
- **Acción**: Reemplazar `PRODUCTS` y `saveCustomProduct()` por `useProductsStore()`

### 5. Órdenes de Venta 🚧 (FALTA API)
- **Acción**: Crear endpoints en backend
- **Acción**: Crear store en frontend
- **Componente**: `src/components/panel/Modules5.tsx` (OMS)

### 6. Inventarios 🚧 (FALTA API)
- **Acción**: Crear endpoints en backend
- **Acción**: Crear store en frontend
- **Componente**: `src/components/panel/Modules7.tsx` (Stock)

### 7. Compras 🚧 (FALTA API)
- **Acción**: Crear endpoints en backend
- **Acción**: Crear store en frontend
- **Componente**: `src/components/panel/Modules8.tsx` (Compras)

---

## 🔄 Script de Migración de Datos

Para migrar datos existentes de localStorage a PostgreSQL:

```typescript
// src/migrate.ts
import { customers, products } from './api/client';

export async function migrateData() {
  // Migrar clientes
  const localCustomers = JSON.parse(localStorage.getItem('bletia-customers') || '[]');
  for (const customer of localCustomers) {
    await customers.create(customer);
  }
  
  // Migrar productos
  const localProducts = JSON.parse(localStorage.getItem('bletia-pim-custom') || '[]');
  for (const product of localProducts) {
    await products.create(product);
  }
  
  console.log('✅ Migración completada');
}
```

---

## 🧪 Testing de Migración

### 1. Probar Autenticación
```bash
# Iniciar backend
cd backend && npm run dev

# Iniciar frontend
npm run dev

# Acceder a http://localhost:5173
# Login con: admin@bletia.ec / admin123
```

### 2. Probar Clientes
```bash
# Verificar que se cargan desde API
# Crear nuevo cliente
# Verificar que se guarda en PostgreSQL
docker-compose exec postgres psql -U bletia -d bletia_db -c "SELECT * FROM \"Customer\";"
```

### 3. Probar Productos
```bash
# Similar a clientes
docker-compose exec postgres psql -U bletia -d bletia_db -c "SELECT * FROM \"Product\";"
```

---

## 📊 Checklist de Migración

### Backend
- [x] Esquema PostgreSQL definido
- [x] API REST con Fastify
- [x] Autenticación JWT
- [x] Endpoints: usuarios, clientes, productos
- [ ] Endpoints: órdenes de venta
- [ ] Endpoints: pagos
- [ ] Endpoints: inventarios
- [ ] Endpoints: compras
- [ ] Endpoints: producción
- [ ] Endpoints: contabilidad
- [ ] Testing de endpoints

### Frontend
- [x] Cliente API creado
- [x] Store Zustand configurado
- [x] Migración: autenticación
- [x] Migración: usuarios
- [ ] Migración: clientes (CRM)
- [ ] Migración: productos (PIM)
- [ ] Migración: órdenes (OMS)
- [ ] Migración: inventarios
- [ ] Migración: compras
- [ ] Migración: producción
- [ ] Migración: contabilidad
- [ ] Testing de frontend

### Datos
- [ ] Script de migración
- [ ] Backup de datos localStorage
- [ ] Migración de datos reales
- [ ] Validación de datos migrados

---

## 🚀 Orden de Migración Recomendado

1. **Autenticación** ✅ (ya hecho)
2. **Usuarios** ✅ (ya hecho)
3. **Clientes** (siguiente)
4. **Productos**
5. **Órdenes de venta**
6. **Pagos**
7. **Inventarios**
8. **Compras**
9. **Producción**
10. **Contabilidad**

---

## 🆘 Problemas Comunes

### Error 401 al hacer peticiones
- Verificar que el token JWT esté en localStorage
- Verificar que el header `Authorization: Bearer <token>` se envíe
- Verificar que el token no haya expirado

### Error de CORS
- Verificar que `CORS_ORIGIN` en backend/.env coincida con la URL del frontend
- Reiniciar backend después de cambiar variables de entorno

### Datos no se cargan
- Verificar que el backend esté corriendo
- Verificar que PostgreSQL esté corriendo
- Verificar logs del backend: `docker-compose logs backend`

---

## 📞 Soporte

- **Documentación**: README.md, PROGRESS.md
- **Issues**: GitHub Issues
- **Email**: soporte@bletia.ec

---

**Última actualización**: 2026-02-20
