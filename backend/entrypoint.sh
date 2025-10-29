#!/bin/bash
set -e

echo "=== Tem de Tudo - Starting ==="

# Create directories
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Set permissions
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Database setup
if [ "$DATABASE_URL" ]; then
    echo "Using PostgreSQL production database"
    php artisan migrate --force
else
    echo "Using SQLite fallback"
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    php artisan migrate --force
fi

# Seed database
php artisan db:seed --force

echo "=== Sistema Pronto ==="

# Start Apache
exec apache2-foreground
