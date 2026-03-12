const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

const config = {
  port: process.env.PORT || 3001,
  jwtSecret: process.env.JWT_SECRET || 'changeme',
  databaseUrl: process.env.DATABASE_URL,
  cookieName: 'tdt_token',
  corsOrigin: process.env.CORS_ORIGIN || '*',
};

if (!config.databaseUrl) {
  console.warn('⚠️  DATABASE_URL não definido. As migrations precisarão ser aplicadas manualmente.');
}

module.exports = config;
