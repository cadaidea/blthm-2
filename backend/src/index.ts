import Fastify from 'fastify';
import cors from '@fastify/cors';
import jwt from '@fastify/jwt';
import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcrypt';
import { z } from 'zod';

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

await fastify.register(cors, {
  origin: process.env.CORS_ORIGIN || 'http://localhost:5173',
  credentials: true,
});

await fastify.register(jwt, {
  secret: process.env.JWT_SECRET || 'cambia-este-secret-en-produccion',
});

async function authenticate(request: any, reply: any) {
  try {
    await request.jwtVerify();
  } catch (err) {
    reply.status(401).send({ error: 'No autorizado' });
  }
}

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

// RUTAS DE AUTENTICACIÓN
fastify.post('/api/auth/login', async (request, reply) => {
  try {
    const { email, password } = loginSchema.parse(request.body);
    const user = await prisma.user.findUnique({ where: { email } });

    if (!user || !user.active) {
      return reply.status(401).send({ error: 'Credenciales inválidas' });
    }

    const validPassword = await bcrypt.compare(password, user.passwordHash);
    if (!validPassword) {
      return reply.status(401).send({ error: 'Credenciales inválidas' });
    }

    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() },
    });

    const token = fastify.jwt.sign({
      id: user.id,
      email: user.email,
      role: user.role,
    });

    await prisma.session.create({
      data: {
        userId: user.id,
        token,
        expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
      },
    });

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
      await prisma.session.deleteMany({ where: { token } });
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
      where: { id: request.user.id },
      select: { id: true, email: true, name: true, role: true, lastLoginAt: true },
    });
    return user;
  } catch (err) {
    request.log.error(err);
    return reply.status(500).send({ error: 'Error interno del servidor' });
  }
});

// RUTAS DE USUARIOS
fastify.get('/api/users', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    if (!['ADMIN', 'GERENCIA'].includes(request.user.role)) {
      return reply.status(403).send({ error: 'Sin permisos' });
    }
    const users = await prisma.user.findMany({
      select: { id: true, email: true, name: true, role: true, active: true, lastLoginAt: true, createdAt: true },
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
    if (!['ADMIN', 'GERENCIA'].includes(request.user.role)) {
      return reply.status(403).send({ error: 'Sin permisos' });
    }
    const data = createUserSchema.parse(request.body);
    const existing = await prisma.user.findUnique({ where: { email: data.email } });
    if (existing) {
      return reply.status(400).send({ error: 'El email ya está registrado' });
    }
    const passwordHash = await bcrypt.hash(data.password, 10);
    const user = await prisma.user.create({
      data: { email: data.email, passwordHash, name: data.name, role: data.role },
      select: { id: true, email: true, name: true, role: true, active: true, createdAt: true },
    });
    await prisma.auditLog.create({
      data: {
        userId: request.user.id,
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

// RUTAS DE CLIENTES
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

fastify.post('/api/customers', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const data = createCustomerSchema.parse(request.body);
    const count = await prisma.customer.count();
    const code = `CLI-${String(count + 1).padStart(5, '0')}`;
    const customer = await prisma.customer.create({
      data: { code, ...data },
    });
    await prisma.auditLog.create({
      data: {
        userId: request.user.id,
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

fastify.delete('/api/customers/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const existing = await prisma.customer.findUnique({ where: { id, deletedAt: null } });
    if (!existing) {
      return reply.status(404).send({ error: 'Cliente no encontrado' });
    }
    await prisma.customer.update({
      where: { id },
      data: { deletedAt: new Date() },
    });
    await prisma.auditLog.create({
      data: {
        userId: request.user.id,
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

// RUTAS DE PRODUCTOS
fastify.get('/api/products', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const products = await prisma.product.findMany({
      where: { deletedAt: null },
      include: { images: { orderBy: { order: 'asc' } }, variants: true },
      orderBy: { createdAt: 'desc' },
    });
    return products;
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
      include: { images: true, variants: true },
    });
    await prisma.auditLog.create({
      data: {
        userId: request.user.id,
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

fastify.delete('/api/products/:id', { preHandler: [authenticate] }, async (request, reply) => {
  try {
    const { id } = request.params as { id: string };
    const existing = await prisma.product.findUnique({ where: { id, deletedAt: null } });
    if (!existing) {
      return reply.status(404).send({ error: 'Producto no encontrado' });
    }
    await prisma.product.update({
      where: { id },
      data: { deletedAt: new Date() },
    });
    await prisma.auditLog.create({
      data: {
        userId: request.user.id,
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

// HEALTH CHECK
fastify.get('/health', async () => {
  return { status: 'ok', timestamp: new Date().toISOString() };
});

// INICIAR SERVIDOR
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

// SHUTDOWN GRACEFUL
const signals: NodeJS.Signals[] = ['SIGINT', 'SIGTERM'];
signals.forEach((signal) => {
  process.on(signal, async () => {
    fastify.log.info(`Recibida señal ${signal}, cerrando servidor...`);
    await fastify.close();
    await prisma.$disconnect();
    process.exit(0);
  });
});
