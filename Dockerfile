# Dockerfile para Render.com - Tem de Tudo
FROM php:8.2-apache

# Instalar extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do backend
COPY backend /var/www/html

# Instalar dependências
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar .env.render para .env
RUN cp .env.render .env || true

# Gerar chave da aplicação
RUN php artisan key:generate --force || true

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expor porta
EXPOSE 8080

# Comando de inicialização
CMD php artisan config:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
