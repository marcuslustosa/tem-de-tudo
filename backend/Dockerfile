FROM php:8.2-apache

# Instalar dependências e extensões PHP
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpq-dev \
    postgresql-client \
    git \
    unzip \
    curl \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    dos2unix \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires \
    && rm -rf /var/lib/apt/lists/*

# Configurar PHP para produção
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos de dependências primeiro (melhor cache)
COPY composer.json composer.lock ./

# Instalar dependências do Composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar todos os arquivos do projeto
COPY . .

# Otimizar autoloader
RUN composer dump-autoload --optimize --no-dev

# Copiar configuração do Apache
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Criar diretórios necessários
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# Configurar permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Limpar qualquer cache de configuração que possa existir
RUN rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php \
    && rm -f .env .env.* || true

# Criar .env VAZIO para evitar erros no build
RUN touch .env && echo "APP_KEY=base64:4KqJxMzRlNTBiZWItNGY5OC00YzY3LWJhOTEtYmU5ZTc2MGE2YjA1" > .env

# Copiar e configurar entrypoint
COPY entrypoint-render.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && dos2unix /entrypoint.sh

# Expor porta 80
EXPOSE 80

# Usar entrypoint
CMD ["/entrypoint.sh"]