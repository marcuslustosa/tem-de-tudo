# Base
FROM php:8.2-apache-bullseye

ARG DEBIAN_FRONTEND=noninteractive

# Dependências do sistema + extensões PHP necessárias
RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
       libpq-dev unzip git curl ca-certificates gnupg libzip-dev zlib1g-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Cria usuário não-root para evitar problemas com Composer
RUN useradd -m -s /bin/bash laravel \
    && usermod -aG www-data laravel

# Copia Composer primeiro
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia apenas arquivos necessários para composer install
COPY composer.json composer.lock ./

# Instala dependências como usuário não-root
RUN chown -R laravel:laravel /var/www/html \
    && su - laravel -c "COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction"

# Copia o resto do projeto
COPY --chown=laravel:laravel . .

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ajusta DocumentRoot do Apache
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Porta
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
