# Imagem base
FROM php:8.2-apache

# Instala extensões necessárias do Laravel + Postgres
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia composer.json, composer.lock e artisan antes (para cache + evitar erro do artisan)
COPY composer.json composer.lock artisan ./

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependências do PHP
RUN composer install --no-dev --optimize-autoloader

# Agora copia o restante do projeto
COPY . .

# Ajusta permissões (storage e bootstrap/cache precisam ser graváveis)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Porta exposta
EXPOSE 80

# Comando de inicialização do Apache
CMD ["apache2-foreground"]
