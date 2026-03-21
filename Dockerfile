FROM php:8.2-apache

# Instala dependências do Laravel + extensões comuns
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip unzip git curl \
    libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring exif pcntl bcmath gd \
    # Garante apenas um MPM (prefork) para mod_php
    && a2dismod mpm_event && a2enmod mpm_prefork \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Ajustes PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências PHP com cache de layers
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copia todo o backend Laravel
COPY backend ./

# Autoload otimizado
RUN composer dump-autoload --optimize --no-dev

# Configuração do Apache
COPY backend/docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Diretórios obrigatórios
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Usa envs fornecidos pela Railway; migrations devem rodar em hook ou manualmente
CMD ["apache2-foreground"]
