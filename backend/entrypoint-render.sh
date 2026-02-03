#!/bin/bash
set -e

echo "========================================="
echo "🚀 TEM DE TUDO - Deploy Render.com"
echo "========================================="

# Criar banco SQLite
echo "📦 Criando banco SQLite..."
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

# Criar .env com SQLite
echo "🔧 Configurando .env com SQLite..."
cat > .env << 'EOF'
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_KEY=base64:4KqJxMzRlNTBiZWItNGY5OC00YzY3LWJhOTEtYmU5ZTc2MGE2YjA1
APP_DEBUG=false
APP_URL=https://aplicativo-tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=aplicativo-tem-de-tudo.onrender.com
EOF

echo "✅ .env criado com SQLite!"

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
sleep 10

# Executar migrations
echo "📦 Executando migrations..."
php artisan migrate --force --no-interaction

# Executar seeders SEMPRE (usa updateOrCreate, não duplica)
echo "🌱 Populando banco de dados..."
php artisan db:seed --force --class=DatabaseSeeder --no-interaction

# Limpar TODOS os caches (incluindo os gerados no build)
echo "🧹 Limpando TODOS os caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php

# NÃO gerar config:cache - Laravel vai ler variáveis em runtime
echo "⚠️ Rodando SEM cache de configuração (leitura direta de variáveis)"

echo "✅ Deploy concluído! Iniciando servidor..."
echo "========================================="

# Iniciar Apache
exec apache2-foreground
