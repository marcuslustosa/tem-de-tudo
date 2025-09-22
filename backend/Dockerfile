# Imagem base PHP com Apache
FROM php:8.2-apache-bullseye

# Variáveis para evitar perguntas do apt
ARG DEBIAN_FRONTEND=noninteractive

# Instala dependências do sistema e extensões PHP
RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
       libpq-dev \
       unzip \
       git \
       curl \
       ca-certificates \
       gnupg \
       libzip-dev \
       zlib1g-dev \
       libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos de dependências primeiro
COPY composer.json composer.lock artisan ./

# Copia Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Copia todo o restante do projeto
COPY . .

# Permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Porta exposta
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
