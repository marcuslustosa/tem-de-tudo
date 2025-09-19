# Imagem base PHP
FROM php:8.2-cli

# Instala dependências do sistema necessárias para Laravel
RUN apt-get update && apt-get install -y \
    unzip git curl libpng-dev libpq-dev libsqlite3-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite gd mbstring

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /app

# Copia composer.json e composer.lock primeiro para aproveitar cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copia o restante do projeto
COPY . .

# Copia o script de inicialização
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expõe porta (Render ignora, mas útil localmente)
EXPOSE 8080

# Usa o entrypoint para rodar tudo antes do servidor
ENTRYPOINT ["/entrypoint.sh"]
