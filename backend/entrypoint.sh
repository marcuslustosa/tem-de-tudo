#!/bin/bash
set -e

echo "=== Tem de Tudo - Starting ==="
echo "PHP Version: $(php -v | head -n 1)"
echo "Current directory: $(pwd)"
echo "Files: $(ls -la)"

# Create directories
echo "Creating storage directories..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database
echo "Directories created!"

# Set permissions (more permissive for Render)
echo "Setting permissions..."
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache
echo "Permissions set!"

# Clear any cached config that might cause issues
echo "Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
echo "Caches cleared!"

# Copy production environment if exists
if [ -f ".env.production" ]; then
    echo "Using production environment"
    cp .env.production .env
else
    echo "WARNING: .env.production not found, using default .env"
fi

echo "Checking .env file..."
if [ -f ".env" ]; then
    echo ".env exists"
    echo "APP_KEY status: $(grep APP_KEY .env | head -n 1)"
else
    echo "ERROR: .env file not found!"
    exit 1
fi

# Generate application key if not exists
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key"
    php artisan key:generate --force
else
    echo "APP_KEY already set"
fi

# Database setup
echo "Setting up database..."
if [ "$DATABASE_URL" ]; then
    echo "Using PostgreSQL production database: $DB_HOST"
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed but continuing"
else
    echo "Using SQLite fallback"
    touch /var/www/html/database/database.sqlite
    chmod 777 /var/www/html/database/database.sqlite
    echo "Running migrations..."
    php artisan migrate --force || echo "Migration failed but continuing"
fi

# Seed database (ignore errors if already exists)
echo "Seeding database..."
php artisan db:seed --force || echo "Seed completed or already exists"

echo "=== Sistema Pronto ==="
echo "Starting Apache..."

# Start Apache
exec apache2-foreground
