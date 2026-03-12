const bcrypt = require('bcryptjs');
const prisma = require('./prisma');
const config = require('./config');

async function main() {
  console.log('Iniciando seed...');

  // Admin
  const adminEmail = 'admin@temdetudo.com';
  const admin = await prisma.user.upsert({
    where: { email: adminEmail },
    update: {},
    create: {
      name: 'Admin',
      email: adminEmail,
      role: 'admin',
      passwordHash: await bcrypt.hash('admin123', 10),
    },
  });

  // Company owner
  const ownerEmail = 'empresa@temdetudo.com';
  const owner = await prisma.user.upsert({
    where: { email: ownerEmail },
    update: {},
    create: {
      name: 'Dono Empresa',
      email: ownerEmail,
      role: 'company',
      passwordHash: await bcrypt.hash('empresa123', 10),
    },
  });

  // Customer
  const customerEmail = 'cliente@temdetudo.com';
  const customer = await prisma.user.upsert({
    where: { email: customerEmail },
    update: {},
    create: {
      name: 'Cliente Demo',
      email: customerEmail,
      role: 'customer',
      passwordHash: await bcrypt.hash('cliente123', 10),
    },
  });

  // Company
  const company = await prisma.company.upsert({
    where: { id: 'demo-company' },
    update: {},
    create: {
      id: 'demo-company',
      name: 'Vipus Demo',
      themeColor: '#9b59b6',
      ownerUserId: owner.id,
    },
  });

  // Account
  const account = await prisma.loyaltyAccount.upsert({
    where: { id: 'demo-account' },
    update: {},
    create: {
      id: 'demo-account',
      customerUserId: customer.id,
      companyId: company.id,
      pointsBalance: 250,
      tier: 'gold',
    },
  });

  // Coupon
  await prisma.coupon.upsert({
    where: { id: 'demo-coupon' },
    update: {},
    create: {
      id: 'demo-coupon',
      title: 'Desconto 10%',
      description: 'Cupom demo para testes',
      pointsCost: 100,
      companyId: company.id,
      stock: 100,
    },
  });

  // Transaction seed
  await prisma.transaction.create({
    data: {
      accountId: account.id,
      type: 'earn',
      points: 250,
      description: 'Seed pontos iniciais',
    },
  });

  console.log('Seed concluído.');
  await prisma.$disconnect();
}

main().catch(async (err) => {
  console.error(err);
  await prisma.$disconnect();
  process.exit(1);
});
