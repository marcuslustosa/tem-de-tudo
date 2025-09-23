#!/bin/sh

# Define porta padrão se não estiver definida
PORT=${PORT:-10000}

# Garante que diretórios de cache e storage existem
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
mkdir -p /app/database
chmod -R 775 /app/storage /app/bootstrap/cache /app/database

# Cria .env se não existir com configurações mínimas para Render
if [ ! -f /app/.env ]; then
    echo "Criando arquivo .env para Render..."
    cat > /app/.env << 'EOF'
APP_NAME="TemDeTudo"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tem-de-tudo.onrender.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
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
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF

    # Gera chave da aplicação
    php artisan key:generate --force
    echo ".env criado com sucesso!"
fi

# Cria banco SQLite se não existir
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
    echo "Banco SQLite criado!"
fi

# Limpa caches antigos para evitar problemas
php artisan config:clear 2>/dev/null || echo "Config clear falhou - continuando..."
php artisan route:clear 2>/dev/null || echo "Route clear falhou - continuando..."
php artisan view:clear 2>/dev/null || echo "View clear falhou - continuando..."

# Roda migrations em produção sem pedir confirmação (ignora erros se DB não estiver disponível)
php artisan migrate --force 2>/dev/null || echo "Migrations falharam - continuando..."

# Serve Laravel
echo "Iniciando servidor Laravel na porta $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
