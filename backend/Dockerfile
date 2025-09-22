# Imagem base com Apache
FROM php:8.2-apache

# Instala dependências do sistema e extensões PHP necessárias
RUN apt-get update -qq \
    && apt-get install --no-install-recommends -y \
       libpq-dev unzip git curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos de dependências primeiro para cache (composer)
COPY composer.json composer.lock artisan ./

# Copia Composer da imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instala dependências PHP sem dev e otimiza autoloader
RUN composer install --no-dev --optimize-autoloader

# Copia todo o restante do projeto
COPY . .

# Ajusta permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Porta que o Apache vai escutar
EXPOSE 80

# Comando final para iniciar o Apache
CMD ["apache2-foreground"]
