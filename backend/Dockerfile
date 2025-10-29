FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Configure Apache
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create directories and set permissions
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/database

# Create entrypoint script directly in container (Unix encoding guaranteed)
RUN cat > /usr/local/bin/entrypoint.sh << 'EOF'
#!/bin/bash
set -e

echo "=== Tem de Tudo - Starting ==="

# Copy env file
if [ -f "/var/www/html/.env.render" ]; then
    echo "Using .env.render"
    cp /var/www/html/.env.render /var/www/html/.env
fi

# Essential directories
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Permissions - Very permissive for debugging
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/database

# Clear caches
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

# ALWAYS use SQLite for now
echo "Using SQLite database"
touch /var/www/html/database/database.sqlite
chmod 777 /var/www/html/database/database.sqlite

# Update .env to use SQLite
if [ -f "/var/www/html/.env" ]; then
    sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' /var/www/html/.env
    sed -i 's|DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/database.sqlite|' /var/www/html/.env
fi

# Run migrations
php artisan migrate --force || echo "Migration failed"

# Seed database
php artisan db:seed --force || echo "Seed failed"

echo "=== Starting Apache ==="
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
