FROM php:8.2-apache

# Instalar dependências e extensões PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    dos2unix \
    git \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite

# Configurar PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar composer.json primeiro
COPY composer.json composer.lock ./

# Copiar o resto dos arquivos
COPY . .

# Configurar Apache
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Configurar permissões e diretórios
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x entrypoint.sh \
    && dos2unix entrypoint.sh

# Expor porta
EXPOSE 80

# Definir script de entrada
CMD ["/bin/bash", "/var/www/html/entrypoint.sh"]