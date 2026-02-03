#!/bin/bash
set -e

echo "========================================="
echo "🚀 TEM DE TUDO - Deploy Render.com"
echo "========================================="

# Criar .env com DATABASE_URL
echo "🔧 Configurando .env com PostgreSQL..."
cat > .env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_KEY=base64:4KqJxMzRlNTBiZWItNGY5OC00YzY3LWJhOTEtYmU5ZTc2MGE2YjA1
APP_DEBUG=false
APP_URL=https://aplicativo-tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d6145d94tr6s73e18v90-a
DB_PORT=5432
DB_DATABASE=aplicativo_tem_de_tudo_qbvf
DB_USERNAME=temdetudo
DB_PASSWORD=Iq1zsYYCvzIHWSXCVCTIFPVofuNoZPuj

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=aplicativo-tem-de-tudo.onrender.com
EOF

echo "✅ .env criado com PostgreSQL!"

# Executar migrations (SQLite não precisa aguardar)
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
