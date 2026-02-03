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
    postgresql-client \
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

# Configurar Apache para porta 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Criar diretórios necessários
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expor porta
EXPOSE 8080

# Usar Apache em foreground
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
