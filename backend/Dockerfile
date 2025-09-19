# Imagem base
FROM php:8.2-cli

# Instala dependências do sistema
RUN apt-get update && apt-get install -y unzip git curl libpng-dev libpq-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite gd

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /app

# Copia composer.json primeiro para aproveitar cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copia o restante do projeto
COPY . .

# Cache de config/rota/view (agora vai rodar só em runtime)
RUN php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expõe porta (opcional no Render)
EXPOSE 10000

# Comando de inicialização (Render usa a variável $PORT)
CMD php artisan serve --host=0.0.0.0 --port=${PORT}
