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

echo "Starting Tem de Tudo Production..."

# Essential directories
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Permissions  
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Clear caches
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# Generate key
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --no-interaction || true
fi

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
EOF

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
