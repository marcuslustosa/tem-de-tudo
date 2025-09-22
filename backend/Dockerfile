FROM php:8.2-apache

# Evita travamentos e falhas do apt-get
RUN apt-get update -qq \
    && apt-get install -y --no-install-recommends \
       gnupg2 \
       libpq-dev \
       unzip \
       git \
       curl \
       ca-certificates \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos de dependências primeiro para cache
COPY composer.json composer.lock artisan ./

# Copia Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Copia todo o restante do projeto
COPY . .

# Permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
