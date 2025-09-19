# Imagem base PHP
FROM php:8.2-cli

# Instala dependências do Laravel
RUN apt-get update && apt-get install -y \
    unzip git curl libpng-dev libpq-dev libsqlite3-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /app

# Copia composer.json primeiro (para cache de dependências)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copia todo o projeto (backend + frontend)
COPY . .

# Copia script de inicialização
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expõe a porta
EXPOSE 8080

# Usa entrypoint
ENTRYPOINT ["/entrypoint.sh"]
