#!/usr/bin/env node
/**
 * Garante que DATABASE_URL está definido antes de rodar Prisma ou seeds.
 * Tenta montar a URL a partir de variáveis padrão da Railway/PG (PGUSER, POSTGRES_PASSWORD, RAILWAY_PRIVATE_DOMAIN, etc).
 */
const { spawn } = require('child_process');

function resolveUrl() {
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

const args = process.argv.slice(2);
if (args.length === 0) {
  console.error('Uso: node scripts/run-with-db-url.js <comando> [...args]');
  process.exit(1);
}

const url = resolveUrl();
if (!url) {
  console.error('Não foi possível montar DATABASE_URL. Defina DATABASE_URL ou PG* / RAILWAY_* no ambiente.');
  process.exit(1);
}

const child = spawn(args[0], args.slice(1), {
  stdio: 'inherit',
  env: { ...process.env, DATABASE_URL: url },
});

child.on('exit', (code) => process.exit(code));
