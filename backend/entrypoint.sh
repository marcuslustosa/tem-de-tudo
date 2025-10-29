#!/bin/bash#!/bin/bash

set -e

echo "Starting Tem de Tudo Production..."

echo "=== Iniciando Tem de Tudo ==="

# Essential directories

# Configuração de permissõesmkdir -p /var/www/html/storage/framework/{sessions,views,cache}

chmod -R 755 /var/www/html/storagemkdir -p /var/www/html/storage/logs

chmod -R 755 /var/www/html/bootstrap/cachemkdir -p /var/www/html/bootstrap/cache

mkdir -p /var/www/html/database

# Configuração do banco

if [ "$DATABASE_URL" ]; then# Permissions  

    echo "Usando PostgreSQL"chown -R www-data:www-data /var/www/html/storage

    php artisan migrate --forcechown -R www-data:www-data /var/www/html/bootstrap/cache

elsechown -R www-data:www-data /var/www/html/database

    echo "Usando SQLite fallback"chmod -R 775 /var/www/html/storage

    touch /var/www/html/database/database.sqlitechmod -R 775 /var/www/html/bootstrap/cache

    chmod 664 /var/www/html/database/database.sqlite

    php artisan migrate --force# Clear caches

fiphp artisan config:clear || true

php artisan route:clear || true

# Seed inicialphp artisan view:clear || true

php artisan db:seed --forcephp artisan cache:clear || true



echo "=== Sistema pronto ==="# Generate key

if [ -z "$APP_KEY" ]; then

# Start Apache    php artisan key:generate --force --no-interaction || true

exec apache2-foregroundfi

# Database setup - PostgreSQL preferred
if [ -n "$DB_HOST" ] && [ -n "$DB_PASSWORD" ]; then
    echo "Using PostgreSQL..."
    export DB_CONNECTION=pgsql
    export DB_SSL_MODE=require
    php artisan migrate --force --no-interaction || true
else
    echo "Using SQLite fallback..."
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    php artisan migrate --force --no-interaction || true
fi

# Create test users
php artisan db:seed --force --no-interaction || true

# Cache config
php artisan config:cache || true

echo "Starting Apache..."
exec apache2-foreground