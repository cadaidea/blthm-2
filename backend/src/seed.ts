import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcrypt';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Iniciando seed de base de datos...\n');

  // ============================================
  // CREAR USUARIO ADMINISTRADOR
  // ============================================
  const adminEmail = 'admin@bletia.ec';
  const adminPassword = 'admin123';
  
  const existingAdmin = await prisma.user.findUnique({
    where: { email: adminEmail },
  });

  if (existingAdmin) {
    console.log('⚠️  Usuario administrador ya existe:', adminEmail);
  } else {
    const passwordHash = await bcrypt.hash(adminPassword, 10);
    
    const admin = await prisma.user.create({
      data: {
        email: adminEmail,
        passwordHash,
        name: 'Administrador BLETIA',
        role: 'ADMIN',
        active: true,
      },
    });

    console.log('✅ Usuario administrador creado:');
    console.log('   Email:', admin.email);
    console.log('   Password:', adminPassword);
    console.log('   Rol:', admin.role);
    console.log('   ⚠️  CAMBIA LA CONTRASEÑA INMEDIATAMENTE\n');
  }

  // ============================================
  // CREAR USUARIOS DE EJEMPLO (OPCIONAL)
  // ============================================
  const sampleUsers = [
    { email: 'gerencia@bletia.ec', name: 'María García', role: 'GERENCIA' as const, password: 'gerencia123' },
    { email: 'ventas@bletia.ec', name: 'Juan Pérez', role: 'VENTAS' as const, password: 'ventas123' },
    { email: 'taller@bletia.ec', name: 'Carlos López', role: 'TALLER' as const, password: 'taller123' },
    { email: 'logistica@bletia.ec', name: 'Ana Martínez', role: 'LOGISTICA' as const, password: 'logistica123' },
    { email: 'contabilidad@bletia.ec', name: 'Pedro Sánchez', role: 'CONTABILIDAD' as const, password: 'contabilidad123' },
  ];

  console.log('👥 Creando usuarios de ejemplo...\n');
  
  for (const userData of sampleUsers) {
    const existing = await prisma.user.findUnique({
      where: { email: userData.email },
    });

    if (existing) {
      console.log('⚠️  Usuario ya existe:', userData.email);
    } else {
      const passwordHash = await bcrypt.hash(userData.password, 10);
      
      await prisma.user.create({
        data: {
          email: userData.email,
          passwordHash,
          name: userData.name,
          role: userData.role,
          active: true,
        },
      });

      console.log('✅ Usuario creado:', userData.email);
      console.log('   Password:', userData.password);
      console.log('   Rol:', userData.role, '\n');
    }
  }

  // ============================================
  // CREAR BODEGA PRINCIPAL
  // ============================================
  const existingWarehouse = await prisma.warehouse.findUnique({
    where: { code: 'BOD-001' },
  });

  if (!existingWarehouse) {
    await prisma.warehouse.create({
      data: {
        code: 'BOD-001',
        name: 'Bodega Principal',
        address: 'Cuenca, Ecuador',
        active: true,
      },
    });
    console.log('✅ Bodega principal creada: BOD-001\n');
  } else {
    console.log('⚠️  Bodega principal ya existe\n');
  }

  // ============================================
  // CREAR PRODUCTOS DE EJEMPLO
  // ============================================
  const existingProducts = await prisma.product.count();

  if (existingProducts === 0) {
    console.log('📦 Creando productos de ejemplo...\n');

    const products = [
      {
        sku: 'BLT-001',
        name: 'Sofá Nudo',
        description: 'Sofá de 3 plazas en nogal americano con tapiz de lino belga',
        category: 'Sofás',
        price: 2450.00,
        cost: 1200.00,
        stock: 5,
        minStock: 2,
      },
      {
        sku: 'BLT-002',
        name: 'Butaca Aura',
        description: 'Butaca individual en roble europeo con bouclé',
        category: 'Sillones',
        price: 1190.00,
        cost: 580.00,
        stock: 8,
        minStock: 3,
      },
      {
        sku: 'BLT-003',
        name: 'Mesa Raíz',
        description: 'Mesa de comedor en nogal macizo para 6 personas',
        category: 'Mesas',
        price: 1750.00,
        cost: 850.00,
        stock: 3,
        minStock: 1,
      },
      {
        sku: 'BLT-004',
        name: 'Aparador Bruma',
        description: 'Aparador con puertas ranuradas a mano',
        category: 'Almacenaje',
        price: 1980.00,
        cost: 960.00,
        stock: 2,
        minStock: 1,
      },
      {
        sku: 'BLT-005',
        name: 'Cama Duna',
        description: 'Cama king size con cabecero tapizado',
        category: 'Descanso',
        price: 2150.00,
        cost: 1050.00,
        stock: 4,
        minStock: 2,
      },
    ];

    for (const product of products) {
      await prisma.product.create({
        data: product,
      });
      console.log('✅ Producto creado:', product.sku, '-', product.name);
    }
    console.log('');
  } else {
    console.log('⚠️  Ya existen productos en la base de datos\n');
  }

  // ============================================
  // CREAR CLIENTE DE EJEMPLO
  // ============================================
  const existingCustomers = await prisma.customer.count();

  if (existingCustomers === 0) {
    console.log('👤 Creando cliente de ejemplo...\n');

    await prisma.customer.create({
      data: {
        code: 'CLI-00001',
        name: 'Cliente Demo',
        email: 'demo@bletia.ec',
        phone: '+593 99 123 4567',
        taxId: '0101010101',
        address: 'Av. Principal 123',
        city: 'Cuenca',
      },
    });

    console.log('✅ Cliente creado: CLI-00001\n');
  } else {
    console.log('⚠️  Ya existen clientes en la base de datos\n');
  }

  console.log('🎉 Seed completado exitosamente!\n');
  console.log('📝 Próximos pasos:');
  console.log('   1. Accede al frontend: http://localhost:5173');
  console.log('   2. Inicia sesión con: admin@bletia.ec / admin123');
  console.log('   3. ⚠️  CAMBIA LA CONTRASEÑA DEL ADMINISTRADOR\n');
}

main()
  .catch((e) => {
    console.error('❌ Error en seed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
