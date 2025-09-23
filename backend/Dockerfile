# Imagem base PHP
FROM php:8.2-cli

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    unzip git curl libpng-dev libpq-dev libsqlite3-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /app

# Copia composer.json primeiro (para cache de dependências)
COPY composer.json ./

# Instala dependências do PHP (ignora composer.lock se não existir)
RUN if [ -f composer.lock ]; then \
        cp composer.lock composer.lock.backup; \
    fi && \
    composer install --no-dev --optimize-autoloader --no-scripts || \
    (echo "Composer install falhou, tentando sem lock..." && \
     rm -f composer.lock && \
     composer install --no-dev --optimize-autoloader --no-scripts)

# Copia todo o projeto
COPY . .

# Cria diretórios necessários
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database

# Define permissões
RUN chmod -R 755 storage bootstrap/cache database

# Copia script de inicialização
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expõe a porta
EXPOSE 10000

# Usa entrypoint
ENTRYPOINT ["/entrypoint.sh"]
