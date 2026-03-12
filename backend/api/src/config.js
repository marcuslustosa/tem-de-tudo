const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

function buildDbUrl() {
  if (process.env.DATABASE_URL) return process.env.DATABASE_URL;
  const user = process.env.PGUSER || process.env.POSTGRES_USER;
  const pass = process.env.PGPASSWORD || process.env.POSTGRES_PASSWORD;
  const host = process.env.PGHOST || process.env.RAILWAY_PRIVATE_DOMAIN || process.env.RAILWAY_TCP_PROXY_DOMAIN;
  const port = process.env.PGPORT || process.env.RAILWAY_TCP_PROXY_PORT || '5432';
  const db   = process.env.PGDATABASE || process.env.POSTGRES_DB || 'railway';
  if (user && pass && host) {
    return `postgresql://${encodeURIComponent(user)}:${encodeURIComponent(pass)}@${host}:${port}/${db}`;
  }
  return null;
}

const config = {
  port: process.env.PORT || 3001,
  jwtSecret: process.env.JWT_SECRET || 'changeme',
  databaseUrl: process.env.DATABASE_URL || buildDbUrl(),
  cookieName: 'tdt_token',
  corsOrigin: process.env.CORS_ORIGIN || '*',
};

if (!config.databaseUrl) {
  console.warn('⚠️  DATABASE_URL não definido. As migrations precisarão ser aplicadas manualmente.');
}

module.exports = config;
