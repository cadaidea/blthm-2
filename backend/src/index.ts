import Fastify from 'fastify';
import cors from '@fastify/cors';
import jwt from '@fastify/jwt';
import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcrypt';
import { z } from 'zod';

// ============================================
// CONFIGURACIÓN
// ============================================

const prisma = new PrismaClient();

const fastify = Fastify({
  logger: {
    level: 'info',
    transport: {
      target: 'pino-pretty',
      options: {
        translateTime: 'HH:MM:ss Z',
        ignore: 'pid,hostname',
      },
    },
  },
});

// ============================================
// PLUGINS
// ============================================

await fastify.register(cors, {
  origin: process.env.CORS_ORIGIN || 'http://localhost:5173',
  credentials: true,
});

await fastify.register(jwt, {
  secret: process.env.JWT_SECRET || 'cambia-este-secret-en-produccion',
});

// ============================================
// DECORATORS (TypeScript)
// ============================================

declare module 'fastify' {
  interface FastifyRequest {
    user?: {
      id: string;
      email: string;
      role: string;
    };
  }
}

// ============================================
// MIDDLEWARE: AUTENTICACIÓN
// ============================================

async function authenticate(request: any, reply: any) {
  try {
    await request.jwtVerify();
    request.user = request.user;
  } catch (err) {
    reply.status(401).send({ error: 'No autorizado' });
  }
}

// ============================================
// SCHEMAS DE VALIDACIÓN (Zod)
// ============================================

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
});

const createUserSchema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
  name: z.string().min(2),
  role: z.enum(['GERENCIA', 'VENTAS', 'TALLER', 'LOGISTICA', 'CONTABILIDAD', 'ADMIN']),
});

const createCustomerSchema = z.object({
  name: z.string().min(2),
  email: z.string().email().optional(),
  phone: z.string().optional(),
  taxId: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
});

const createProductSchema = z.object({
  sku: z.string().min(1),
  name: z.string().min(2),
  description: z.string().optional(),
  category: z.string().min(1),
  price: z.number().positive(),
  cost: z.number().positive(),
  stock: z.number().int().min(0),
  minStock: z.number().int().min(0).default(0),
});

// ============================================
// RUTAS: AUTENTICACIÓN
// ============================================

fastify.post('/api/auth/login', async (request, reply) => {
  try {
    const { email, password } = loginSchema.parse(request.body);

    const user = await prisma.user.findUnique({
      where: { email },
    });

    if (!user || !user.active) {
      return reply.status(401).send({ error: 'Credenciales inválidas' });
    }

    const validPassword = await bcrypt.compare(password, user.passwordHash);
    if (!validPassword) {
      return reply.status(401).send({ error: 'Credenciales inválidas' });
    }

    // Actualizar último login
    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() },
    });

    // Generar token JWT
    const token = fastify.jwt.sign({
      id: user.id,
      email: user.email,
      role: user.role,
    });

    // Crear sesión
    await prisma.session.create({
      data: {
        userId: user.id,
        token,
        expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 días
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: user.id,
        action: 'LOGIN',
        entity: 'User',
        entityId: user.id,
        ipAddress: request.ip,
        userAgent: request.headers['user-agent'],
      },
    });

    return {
      token,
      user: {
        id: user.id,
        email: user.email,
        name: user.name,
        role: user.role,
      },
    };
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.post('/api/auth/logout', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const token = request.headers.authorization?.replace('Bearer ', '');
    if (token) {
      await prisma.session.deleteMany({
        where: { token },
      });
    }
    return { success: true };
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.get('/api/auth/me', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const user = await prisma.user.findUnique({
      where: { id: request.user!.id },
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        lastLoginAt: true,
      },
    });
    return user;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTAS: USUARIOS (solo ADMIN y GERENCIA)
// ============================================

fastify.get('/api/users', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    if (!['ADMIN', 'GERENCIA'].includes(request.user!.role)) {
      return reply.status(403).send({ error: 'Sin permisos' });
    }

    const users = await prisma.user.findMany({
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        active: true,
        lastLoginAt: true,
        createdAt: true,
      },
      orderBy: { createdAt: 'desc' },
    });
    return users;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.post('/api/users', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    if (!['ADMIN', 'GERENCIA'].includes(request.user!.role)) {
      return reply.status(403).send({ error: 'Sin permisos' });
    }

    const data = createUserSchema.parse(request.body);

    // Verificar si el email ya existe
    const existing = await prisma.user.findUnique({
      where: { email: data.email },
    });
    if (existing) {
      return reply.status(400).send({ error: 'El email ya está registrado' });
    }

    // Hashear contraseña
    const passwordHash = await bcrypt.hash(data.password, 10);

    const user = await prisma.user.create({
      data: {
        email: data.email,
        passwordHash,
        name: data.name,
        role: data.role,
      },
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        active: true,
        createdAt: true,
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'CREATE',
        entity: 'User',
        entityId: user.id,
        newValue: { email: user.email, name: user.name, role: user.role },
      },
    });

    return reply.status(201).send(user);
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTAS: CLIENTES
// ============================================

fastify.get('/api/customers', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const customers = await prisma.customer.findMany({
      where: { deletedAt: null },
      orderBy: { createdAt: 'desc' },
    });
    return customers;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.get('/api/customers/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const customer = await prisma.customer.findUnique({
      where: { id, deletedAt: null },
      include: {
        orders: {
          orderBy: { createdAt: 'desc' },
          take: 10,
        },
      },
    });
    if (!customer) {
      return reply.status(404).send({ error: 'Cliente no encontrado' });
    }
    return customer;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.post('/api/customers', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const data = createCustomerSchema.parse(request.body);

    // Generar código único
    const count = await prisma.customer.count();
    const code = `CLI-${String(count + 1).padStart(5, '0')}`;

    const customer = await prisma.customer.create({
      data: {
        code,
        ...data,
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'CREATE',
        entity: 'Customer',
        entityId: customer.id,
        newValue: data,
      },
    });

    return reply.status(201).send(customer);
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.put('/api/customers/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const data = createCustomerSchema.partial().parse(request.body);

    const existing = await prisma.customer.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Cliente no encontrado' });
    }

    const customer = await prisma.customer.update({
      where: { id },
      data,
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'UPDATE',
        entity: 'Customer',
        entityId: customer.id,
        oldValue: existing,
        newValue: data,
      },
    });

    return customer;
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.delete('/api/customers/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };

    const existing = await prisma.customer.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Cliente no encontrado' });
    }

    // Soft delete
    await prisma.customer.update({
      where: { id },
      data: { deletedAt: new Date() },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'DELETE',
        entity: 'Customer',
        entityId: id,
        oldValue: existing,
      },
    });

    return { success: true };
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTAS: PRODUCTOS
// ============================================

fastify.get('/api/products', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const products = await prisma.product.findMany({
      where: { deletedAt: null },
      include: {
        images: {
          orderBy: { order: 'asc' },
        },
        variants: true,
      },
      orderBy: { createdAt: 'desc' },
    });
    return products;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.get('/api/products/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const product = await prisma.product.findUnique({
      where: { id, deletedAt: null },
      include: {
        images: {
          orderBy: { order: 'asc' },
        },
        variants: true,
      },
    });
    if (!product) {
      return reply.status(404).send({ error: 'Producto no encontrado' });
    }
    return product;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.post('/api/products', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const data = createProductSchema.parse(request.body);

    const product = await prisma.product.create({
      data,
      include: {
        images: true,
        variants: true,
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'CREATE',
        entity: 'Product',
        entityId: product.id,
        newValue: data,
      },
    });

    return reply.status(201).send(product);
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.put('/api/products/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const data = createProductSchema.partial().parse(request.body);

    const existing = await prisma.product.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Producto no encontrado' });
    }

    const product = await prisma.product.update({
      where: { id },
      data,
      include: {
        images: true,
        variants: true,
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'UPDATE',
        entity: 'Product',
        entityId: product.id,
        oldValue: existing,
        newValue: data,
      },
    });

    return product;
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.delete('/api/products/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };

    const existing = await prisma.product.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Producto no encontrado' });
    }

    // Soft delete
    await prisma.product.update({
      where: { id },
      data: { deletedAt: new Date() },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'DELETE',
        entity: 'Product',
        entityId: id,
        oldValue: existing,
      },
    });

    return { success: true };
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTAS: IMÁGENES DE PRODUCTOS
// ============================================

fastify.post('/api/products/:id/images', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const { url, isPrimary } = request.body as { url: string; isPrimary?: boolean };

    const product = await prisma.product.findUnique({
      where: { id, deletedAt: null },
    });
    if (!product) {
      return reply.status(404).send({ error: 'Producto no encontrado' });
    }

    // Si es la imagen principal, desmarcar las demás
    if (isPrimary) {
      await prisma.productImage.updateMany({
        where: { productId: id },
        data: { isPrimary: false },
      });
    }

    // Obtener el siguiente orden
    const count = await prisma.productImage.count({
      where: { productId: id },
    });

    const image = await prisma.productImage.create({
      data: {
        productId: id,
        url,
        order: count,
        isPrimary: isPrimary || count === 0,
      },
    });

    return reply.status(201).send(image);
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.delete('/api/products/:id/images/:imageId', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id, imageId } = request.params as { id: string; imageId: string };

    await prisma.productImage.delete({
      where: { id: imageId, productId: id },
    });

    return { success: true };
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTAS: ÓRDENES DE VENTA
// ============================================

const createOrderSchema = z.object({
  customerId: z.string(),
  items: z.array(z.object({
    productId: z.string(),
    quantity: z.number().int().positive(),
    unitPrice: z.number().positive(),
  })),
  notes: z.string().optional(),
});

fastify.get('/api/orders', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const orders = await prisma.salesOrder.findMany({
      where: { deletedAt: null },
      include: {
        customer: true,
        items: {
          include: {
            product: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
    return orders;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.get('/api/orders/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const order = await prisma.salesOrder.findUnique({
      where: { id, deletedAt: null },
      include: {
        customer: true,
        items: {
          include: {
            product: true,
          },
        },
        payments: true,
        invoices: true,
      },
    });
    if (!order) {
      return reply.status(404).send({ error: 'Orden no encontrada' });
    }
    return order;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.post('/api/orders', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const data = createOrderSchema.parse(request.body);

    // Calcular totales
    const subtotal = data.items.reduce((sum, item) => sum + (item.quantity * item.unitPrice), 0);
    const tax = subtotal * 0.15; // IVA 15%
    const total = subtotal + tax;

    // Generar código único
    const count = await prisma.salesOrder.count();
    const code = `ORD-${String(count + 1).padStart(5, '0')}`;

    const order = await prisma.salesOrder.create({
      data: {
        code,
        customerId: data.customerId,
        status: 'PENDIENTE',
        subtotal,
        tax,
        total,
        notes: data.notes,
        items: {
          create: data.items.map(item => ({
            productId: item.productId,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            subtotal: item.quantity * item.unitPrice,
          })),
        },
      },
      include: {
        customer: true,
        items: {
          include: {
            product: true,
          },
        },
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'CREATE',
        entity: 'SalesOrder',
        entityId: order.id,
        newValue: { code, total, customerId: data.customerId },
      },
    });

    return reply.status(201).send(order);
  } catch (err) {
    if (err instanceof z.ZodError) {
      return reply.status(400).send({ error: 'Datos inválidos', details: err.errors });
    }
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.put('/api/orders/:id/status', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const { status } = request.body as { status: string };

    const validStatuses = ['PENDIENTE', 'CONFIRMADO', 'EN_PRODUCCION', 'LISTO', 'ENVIADO', 'ENTREGADO', 'CANCELADO'];
    if (!validStatuses.includes(status)) {
      return reply.status(400).send({ error: 'Estado inválido' });
    }

    const existing = await prisma.salesOrder.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Orden no encontrada' });
    }

    const order = await prisma.salesOrder.update({
      where: { id },
      data: { status: status as any },
      include: {
        customer: true,
        items: {
          include: {
            product: true,
          },
        },
      },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'UPDATE',
        entity: 'SalesOrder',
        entityId: id,
        oldValue: { status: existing.status },
        newValue: { status },
      },
    });

    return order;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

fastify.delete('/api/orders/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };

    const existing = await prisma.salesOrder.findUnique({
      where: { id, deletedAt: null },
    });
    if (!existing) {
      return reply.status(404).send({ error: 'Orden no encontrada' });
    }

    // Soft delete
    await prisma.salesOrder.update({
      where: { id },
      data: { deletedAt: new Date() },
    });

    // Registrar en auditoría
    await prisma.auditLog.create({
      data: {
        userId: request.user!.id,
        action: 'DELETE',
        entity: 'SalesOrder',
        entityId: id,
        oldValue: existing,
      },
    });

    return { success: true };
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// ============================================
// HEALTH CHECK
// ============================================

fastify.get('/health', async () => {
  return { status: 'ok', timestamp: new Date().toISOString() };
});

// ============================================
// INICIAR SERVIDOR
// ============================================

const start = async () => {
  try {
    const port = Number(process.env.PORT) || 3000;
    const host = process.env.HOST || '0.0.0.0';

    await fastify.listen({ port, host });
    fastify.log.info(`Servidor corriendo en http://${host}:${port}`);
  } catch (err) {
    fastify.log.error(err);
    process.exit(1);
  }
};

start();

// ============================================
// SHUTDOWN GRACEFUL
// ============================================

const signals: NodeJS.Signals[] = ['SIGINT', 'SIGTERM'];
signals.forEach((signal) => {
  process.on(signal, async () => {
    fastify.log.info(`Recibida señal ${signal}, cerrando servidor...`);
    await fastify.close();
    await prisma.$disconnect();
    process.exit(0);
  });
});
