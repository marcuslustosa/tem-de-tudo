#!/bin/sh

# Garante que diretórios de cache e storage existem
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
chmod -R 777 /app/storage /app/bootstrap/cache

# Roda migrations em produção sem pedir confirmação
php artisan migrate --force

# Regera caches
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Cria link simbólico para que qualquer frontend estático seja servido
if [ ! -d /app/public ]; then
    mkdir -p /app/public
fi
cp -R /app/* /app/public/ 2>/dev/null || true

# Serve Laravel (backend + frontend juntos)
exec php artisan serve --host=0.0.0.0 --port=${PORT}
