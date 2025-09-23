#!/bin/sh

# Garante que diretórios de cache e storage existem
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Gera chave da aplicação se não existir
if [ ! -f /var/www/html/.env ]; then
    cd /var/www/html && cp .env.example .env
    php artisan key:generate --force
fi

# Roda package:discover manualmente (se necessário)
cd /var/www/html && php artisan package:discover --ansi

# Roda migrations em produção sem pedir confirmação
cd /var/www/html && php artisan migrate --force

# Regera caches
cd /var/www/html && php artisan config:clear
cd /var/www/html && php artisan config:cache
cd /var/www/html && php artisan route:cache
cd /var/www/html && php artisan view:cache

# Inicia o Apache
exec apache2-foreground
