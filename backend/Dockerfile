# Imagem base com PHP 8.2, Composer e SQLite
FROM php:8.2-cli

# Instala extensões necessárias
RUN apt-get update && apt-get install -y \
    unzip git curl sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia código
WORKDIR /app
COPY . .

# Instala dependências do Laravel
RUN composer install --no-dev --optimize-autoloader

# Expõe a porta 10000 (necessária no Render)
EXPOSE 10000

# Comando de inicialização
CMD php artisan serve --host=0.0.0.0 --port=10000
