#!/bin/sh

# Define porta padrão se não estiver definida
PORT=${PORT:-8000}

# Garante que diretórios de cache e storage existem
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
chmod -R 775 /app/storage /app/bootstrap/cache

# Gera chave da aplicação se não existir
if [ ! -f /app/.env ]; then
    echo "Criando arquivo .env..."
    cp /app/.env.example /app/.env 2>/dev/null || echo "APP_KEY=base64:$(openssl rand -base64 32)" > /app/.env
    php artisan key:generate --force
fi

# Roda migrations em produção sem pedir confirmação (ignora erros se DB não estiver disponível)
php artisan migrate --force || echo "Migrations falharam - continuando..."

# Regera caches
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Serve Laravel
echo "Iniciando servidor na porta $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
