#!/bin/sh

# Garante que diretórios de cache e storage existem
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Roda migrations em produção sem pedir confirmação
cd /var/www/html && php artisan migrate --force

# Regera caches
cd /var/www/html && php artisan config:clear
cd /var/www/html && php artisan config:cache
cd /var/www/html && php artisan route:cache
cd /var/www/html && php artisan view:cache

# Inicia o Apache
exec apache2-foreground
