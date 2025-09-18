# Imagem base
FROM php:8.2-cli

# Instala dependências
RUN apt-get update && apt-get install -y unzip git curl libpng-dev libpq-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite gd

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar backend
WORKDIR /app
COPY . .

# Instalar dependências Laravel
RUN composer install --no-dev --optimize-autoloader

# Expõe porta para Render
EXPOSE 10000

# Comando de inicialização
CMD php artisan serve --host=0.0.0.0 --port=10000
