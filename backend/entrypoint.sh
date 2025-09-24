#!/bin/sh

# Script de Deploy para Render - Tem de Tudo
echo "🚀 Iniciando deploy do Tem de Tudo..."

# Define porta padrão se não estiver definida
PORT=${PORT:-10000}
echo "📡 Configurando porta: $PORT"

# Garante que diretórios de cache e storage existem
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
mkdir -p /app/database
chmod -R 775 /app/storage /app/bootstrap/cache /app/database
echo "📁 Diretórios configurados"

# Cria .env se não existir com configurações para Render
if [ ! -f /app/.env ]; then
    echo "⚙️ Criando arquivo .env para Render..."
    cat > /app/.env << 'EOF'
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_KEY=base64:Hoqt3hw6TLDwbXIwtj0BEPTdaFJBVey1uK8avZpGYD4=
APP_DEBUG=false
APP_URL=https://tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="contato@temdetudo.com"
MAIL_FROM_NAME="Tem de Tudo"

SANCTUM_STATEFUL_DOMAINS="tem-de-tudo.onrender.com,localhost"
SESSION_DOMAIN=".temdetudo.com"

VITE_APP_NAME="Tem de Tudo"
EOF
else
    echo "✅ Arquivo .env já existe"
fi

# Criar banco SQLite se não existir para fallback
if [ ! -f /app/database/database.sqlite ]; then
    echo "🗃️ Criando banco SQLite de fallback..."
    touch /app/database/database.sqlite
    chmod 664 /app/database/database.sqlite
fi

# Cache de configuração otimizado
echo "⚡ Otimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Rodar migrations
echo "🔄 Executando migrations..."
php artisan migrate --force || echo "⚠️ Erro nas migrations (pode ser ignorado em primeira execução)"

# Seeder de usuários de demonstração
echo "👥 Criando usuários de demonstração..."
php artisan db:seed --force || echo "⚠️ Erro no seeder (pode ser ignorado)"

# Criar link simbólico para storage
echo "🔗 Criando link do storage..."
php artisan storage:link || echo "⚠️ Erro no storage link (pode ser ignorado)"

# Limpar caches antigos
echo "🧹 Limpando caches..."
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Cache final
echo "💫 Cache final..."
php artisan config:cache
php artisan route:cache

echo "✅ Deploy concluído! Iniciando servidor na porta $PORT"

# Iniciar o servidor
exec php -S 0.0.0.0:$PORT -t public