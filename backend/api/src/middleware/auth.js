const jwt = require('jsonwebtoken');
const config = require('../config');
const prisma = require('../prisma');

function extractToken(req) {
  const fromHeader = req.headers.authorization?.split(' ')[1];
  const fromCookie = req.cookies[config.cookieName];
  return fromHeader || fromCookie || null;
}

function requireAuth(options = {}) {
  const { roles } = options;

  return async (req, res, next) => {
    try {
      const token = extractToken(req);
      if (!token) return res.status(401).json({ error: 'Não autenticado' });

      const payload = jwt.verify(token, config.jwtSecret);
      const user = await prisma.user.findUnique({ where: { id: payload.sub } });
      if (!user) return res.status(401).json({ error: 'Usuário não encontrado' });

      if (roles && !roles.includes(user.role)) {
        return res.status(403).json({ error: 'Sem permissão' });
      }

      req.user = user;
      next();
    } catch (err) {
      console.error('Auth error', err.message);
      return res.status(401).json({ error: 'Token inválido ou expirado' });
    }
  };
}

module.exports = { requireAuth, extractToken };
