const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { randomUUID } = require('crypto');

const config = require('./config');
const prisma = require('./prisma');
const { requireAuth } = require('./middleware/auth');

const app = express();

app.use(cors({
  origin: config.corsOrigin === '*' ? true : config.corsOrigin.split(','),
  credentials: true,
}));
app.use(express.json());
app.use(cookieParser());

// Helpers
function signToken(user) {
  return jwt.sign(
    { sub: user.id, role: user.role },
    config.jwtSecret,
    { expiresIn: '7d' },
  );
}

function sanitizeUser(user) {
  const { passwordHash, ...safe } = user;
  return safe;
}

// Health
app.get('/health', async (_req, res) => {
  try {
    await prisma.$queryRaw`SELECT 1 as ok`;
    res.json({ status: 'ok', db: true });
  } catch (err) {
    res.status(500).json({ status: 'error', db: false, message: err.message });
  }
});

// Auth
app.post('/auth/register', async (req, res) => {
  try {
    const { name, email, password } = req.body;
    if (!name || !email || !password) return res.status(400).json({ error: 'Campos obrigatórios faltando' });

    const exists = await prisma.user.findUnique({ where: { email } });
    if (exists) return res.status(409).json({ error: 'Email já cadastrado' });

    const passwordHash = await bcrypt.hash(password, 10);
    const user = await prisma.user.create({
      data: { name, email, passwordHash, role: 'customer' },
    });

    const token = signToken(user);
    res
      .cookie(config.cookieName, token, { httpOnly: true, sameSite: 'lax', secure: false })
      .json({ user: sanitizeUser(user), token });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao registrar' });
  }
});

app.post('/auth/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    const user = await prisma.user.findUnique({ where: { email } });
    if (!user) return res.status(401).json({ error: 'Credenciais inválidas' });

    const ok = await bcrypt.compare(password, user.passwordHash);
    if (!ok) return res.status(401).json({ error: 'Credenciais inválidas' });

    const token = signToken(user);
    res
      .cookie(config.cookieName, token, { httpOnly: true, sameSite: 'lax', secure: false })
      .json({ user: sanitizeUser(user), token });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao autenticar' });
  }
});

app.post('/auth/logout', (_req, res) => {
  res.clearCookie(config.cookieName).json({ ok: true });
});

app.get('/auth/me', requireAuth(), async (req, res) => {
  res.json({ user: sanitizeUser(req.user) });
});

// Companies
app.get('/companies', requireAuth(), async (req, res) => {
  const companies = await prisma.company.findMany({ include: { ownerUser: true } });
  res.json(companies);
});

app.post('/companies', requireAuth({ roles: ['admin'] }), async (req, res) => {
  try {
    const { name, ownerUserId, themeColor } = req.body;
    if (!name || !ownerUserId) return res.status(400).json({ error: 'name e ownerUserId são obrigatórios' });

    const company = await prisma.company.create({
      data: { name, ownerUserId, themeColor },
    });
    res.status(201).json(company);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao criar empresa' });
  }
});

// Accounts
app.get('/accounts', requireAuth(), async (req, res) => {
  const { role, id } = req.user;
  let where = {};
  if (role === 'customer') where = { customerUserId: id };
  const accounts = await prisma.loyaltyAccount.findMany({
    where,
    include: { company: true, customer: true },
  });
  res.json(accounts);
});

app.post('/accounts/:id/earn', requireAuth({ roles: ['admin', 'company'] }), async (req, res) => {
  try {
    const { id } = req.params;
    const { points, description } = req.body;
    if (!points || Number.isNaN(points)) return res.status(400).json({ error: 'points inválido' });

    const account = await prisma.loyaltyAccount.update({
      where: { id },
      data: {
        pointsBalance: { increment: points },
        transactions: {
          create: { type: 'earn', points, description },
        },
      },
      include: { customer: true, company: true },
    });
    res.json(account);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao creditar pontos' });
  }
});

app.post('/accounts/:id/redeem', requireAuth({ roles: ['customer', 'company', 'admin'] }), async (req, res) => {
  try {
    const { id } = req.params;
    const { couponId } = req.body;
    if (!couponId) return res.status(400).json({ error: 'couponId obrigatório' });

    const account = await prisma.loyaltyAccount.findUnique({ where: { id } });
    const coupon = await prisma.coupon.findUnique({ where: { id: couponId } });
    if (!account || !coupon) return res.status(404).json({ error: 'Conta ou cupom não encontrado' });

    if (account.pointsBalance < coupon.pointsCost) {
      return res.status(400).json({ error: 'Saldo insuficiente' });
    }

    const token = randomUUID();

    const redemption = await prisma.$transaction(async (tx) => {
      const updatedAccount = await tx.loyaltyAccount.update({
        where: { id },
        data: {
          pointsBalance: { decrement: coupon.pointsCost },
          transactions: {
            create: { type: 'redeem', points: coupon.pointsCost, description: `Resgate ${coupon.title}` },
          },
        },
      });

      const redemptionCreated = await tx.redemption.create({
        data: {
          status: 'reserved',
          qrcodeToken: token,
          accountId: id,
          couponId,
        },
        include: { coupon: true },
      });

      return { redemption: redemptionCreated, account: updatedAccount };
    });

    res.json({ ...redemption, token });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao resgatar' });
  }
});

app.get('/transactions', requireAuth(), async (req, res) => {
  const { accountId } = req.query;
  if (!accountId) return res.status(400).json({ error: 'accountId obrigatório' });

  const items = await prisma.transaction.findMany({
    where: { accountId },
    orderBy: { createdAt: 'desc' },
  });
  res.json(items);
});

// Coupons
app.get('/coupons', requireAuth(), async (req, res) => {
  const { companyId } = req.query;
  const where = companyId ? { companyId } : {};
  const coupons = await prisma.coupon.findMany({ where });
  res.json(coupons);
});

app.post('/companies/:companyId/coupons', requireAuth({ roles: ['admin', 'company'] }), async (req, res) => {
  try {
    const { companyId } = req.params;
    const { title, pointsCost, description, expiresAt, stock } = req.body;
    if (!title || !pointsCost) return res.status(400).json({ error: 'title e pointsCost são obrigatórios' });

    const coupon = await prisma.coupon.create({
      data: { companyId, title, pointsCost, description, expiresAt: expiresAt ? new Date(expiresAt) : null, stock },
    });
    res.status(201).json(coupon);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao criar cupom' });
  }
});

// QR scanner
app.post('/qrcode/scan', requireAuth({ roles: ['admin', 'company'] }), async (req, res) => {
  try {
    const { token } = req.body;
    if (!token) return res.status(400).json({ error: 'token obrigatório' });

    const redemption = await prisma.redemption.findUnique({
      where: { qrcodeToken: token },
      include: { coupon: true, account: true },
    });
    if (!redemption) return res.status(404).json({ error: 'Token não encontrado' });
    if (redemption.status === 'used') return res.status(400).json({ error: 'Token já utilizado' });

    const updated = await prisma.redemption.update({
      where: { id: redemption.id },
      data: { status: 'used', usedAt: new Date() },
      include: { coupon: true, account: true },
    });
    res.json(updated);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro ao validar token' });
  }
});

// Notifications
app.get('/notifications', requireAuth(), async (req, res) => {
  const notifications = await prisma.notification.findMany({
    where: { userId: req.user.id },
    orderBy: { createdAt: 'desc' },
  });
  res.json(notifications);
});

app.post('/notifications', requireAuth({ roles: ['admin', 'company'] }), async (req, res) => {
  const { userId, title, body } = req.body;
  if (!userId || !title || !body) return res.status(400).json({ error: 'userId, title, body são obrigatórios' });
  const notif = await prisma.notification.create({ data: { userId, title, body } });
  res.status(201).json(notif);
});

app.post('/notifications/:id/read', requireAuth(), async (req, res) => {
  const { id } = req.params;
  const notif = await prisma.notification.update({
    where: { id },
    data: { readAt: new Date() },
  });
  res.json(notif);
});

// Start server
app.listen(config.port, () => {
  console.log(`API rodando na porta ${config.port}`);
});
