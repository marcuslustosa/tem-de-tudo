FROM php:8.2-apache

# Instalar dependências e extensões PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    dos2unix \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar composer.json primeiro
COPY composer.json composer.lock ./

# Instalar dependências
RUN composer install --no-scripts --no-autoloader

# Copiar o resto dos arquivos
COPY . .

# Gerar autoloader otimizado
RUN composer dump-autoload --optimize

# Configurar Apache
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Configurar permissões e diretórios
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && chmod -R 777 storage \
    && chmod -R 777 bootstrap/cache \
    && chmod +x entrypoint.sh \
    && dos2unix entrypoint.sh

# Expor porta
EXPOSE 80

# Definir script de entrada
CMD ["/bin/bash", "/var/www/html/entrypoint.sh"]