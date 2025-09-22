FROM php:8.2-apache-bullseye

ARG DEBIAN_FRONTEND=noninteractive

# Dependências do sistema + extensões PHP
RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
       libpq-dev unzip git curl ca-certificates gnupg libzip-dev zlib1g-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copia todo o projeto para o container (exceto vendor)
COPY . .

# Copia Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]
