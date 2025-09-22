FROM php:8.2-apache

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    unzip git curl libpng-dev libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Habilita o módulo rewrite do Apache
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia composer.json primeiro (para cache de dependências)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copia todo o projeto
COPY . .

# Define permissões corretas
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expõe a porta
EXPOSE 80

# Usa Apache como CMD
CMD ["apache2-foreground"]
