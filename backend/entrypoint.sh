#!/bin/sh

# Garante que diretórios de storage e cache existem e têm permissão correta
mkdir -p /app/storage/framework/{sessions,views,cache}
mkdir -p /app/storage/logs
chmod -R 777 /app/storage /app/bootstrap/cache

# Roda migrations em produção (sem pedir confirmação)
php artisan migrate --force

# Regera caches de config, rota e view
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Inicia o servidor Laravel na porta do Render
exec php artisan serve --host=0.0.0.0 --port=${PORT}
