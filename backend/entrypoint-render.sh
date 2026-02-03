#!/bin/bash
set -e

echo "========================================="
echo "🚀 TEM DE TUDO - Deploy Render.com"
echo "========================================="

# Criar .env dinâmico com variáveis do Render
echo "🔧 Configurando variáveis de ambiente..."
cat > .env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_KEY=base64:4KqJxMzRlNTBiZWItNGY5OC00YzY3LWJhOTEtYmU5ZTc2MGE2YjA1
APP_DEBUG=false
APP_URL=https://aplicativo-tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=$PGHOST
DB_PORT=$PGPORT
DB_DATABASE=$PGDATABASE
DB_USERNAME=$PGUSER
DB_PASSWORD=$PGPASSWORD

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=aplicativo-tem-de-tudo.onrender.com
EOF

echo "✅ .env criado com sucesso!"
echo "📊 Conexão: $PGUSER@$PGHOST:$PGPORT/$PGDATABASE"

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
sleep 10

# Executar migrations
echo "📦 Executando migrations..."
php artisan migrate --force --no-interaction

# Executar seeders SEMPRE (usa updateOrCreate, não duplica)
echo "🌱 Populando banco de dados..."
php artisan db:seed --force --class=DatabaseSeeder --no-interaction

# Limpar cache
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Otimizar para produção
echo "⚡ Otimizando aplicação..."
php artisan config:cache
php artisan route:cache

echo "✅ Deploy concluído! Iniciando servidor..."
echo "========================================="

# Iniciar Apache
exec apache2-foreground
