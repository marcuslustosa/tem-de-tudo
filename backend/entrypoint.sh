#!/bin/bash
set -e

echo "=== Tem de Tudo - Starting ==="

# Create directories
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Set permissions (more permissive for Render)
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache

# Clear any cached config that might cause issues
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

# Copy production environment if exists
if [ -f ".env.production" ]; then
    echo "Using production environment"
    cp .env.production .env
fi

# Generate application key if not exists
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key"
    php artisan key:generate --force
fi

# Database setup
if [ "$DATABASE_URL" ]; then
    echo "Using PostgreSQL production database"
    php artisan migrate --force
else
    echo "Using SQLite fallback"
    touch /var/www/html/database/database.sqlite
    chmod 777 /var/www/html/database/database.sqlite
    php artisan migrate --force
fi

# Seed database (ignore errors if already exists)
php artisan db:seed --force || echo "Seed completed or already exists"

echo "=== Sistema Pronto ==="

# Start Apache
exec apache2-foreground
