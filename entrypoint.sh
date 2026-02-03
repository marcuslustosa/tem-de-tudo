#!/bin/bash
set -e

echo "🚀 Iniciando aplicação Tem de Tudo..."

# Aguardar PostgreSQL ficar pronto
echo "⏳ Aguardando PostgreSQL..."
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" 2>/dev/null; do
  echo "PostgreSQL ainda não está pronto - aguardando..."
  sleep 2
done
echo "✅ PostgreSQL está pronto!"

# Criar arquivo .env com variáveis de ambiente
echo "📝 Criando arquivo .env..."
cat > /var/www/html/.env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

echo "✅ Arquivo .env criado!"

# Rodar migrations
echo "🗄️  Rodando migrations..."
php artisan migrate --force || echo "⚠️  Migrations falharam, mas continuando..."

echo "✅ Aplicação pronta!"

# Executar comando passado como argumento
exec "$@"
