FROM php:8.2-apache

# Instala dependências do Laravel + extensões comuns
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip unzip git curl \
    libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring exif pcntl bcmath gd \
    # Garante apenas um MPM (prefork) para mod_php
    && a2dismod mpm_event mpm_worker && a2enmod mpm_prefork \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Ajustes PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências PHP com cache de layers
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copia todo o backend Laravel
COPY backend ./

# Autoload otimizado
RUN composer dump-autoload --optimize --no-dev

# Configuração do Apache
COPY backend/docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Diretórios obrigatórios
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Script de inicializacao: adapta porta dinamica da Railway, exporta DB_* a partir de DATABASE_URL e sobe Apache
RUN printf '#!/bin/bash\nset -euo pipefail\n\nPORT=${PORT:-8080}\n# Ajusta Apache para porta dinamica\nprintf \"Listen ${PORT}\\n\" > /etc/apache2/ports.conf\nsed -i \"s#<VirtualHost .*:.*>#<VirtualHost *:${PORT}>#\" /etc/apache2/sites-available/000-default.conf\n\ncd /var/www/html\n\n# Se so temos DATABASE_URL, derivar DB_* para o Laravel\nif [ -n \"${DATABASE_URL:-}\" ] && [ -z \"${DB_HOST:-}\" ]; then\n  eval \"$(php /var/www/html/docker/export-db-env.php)\"\nfi\n\n# APP_KEY temporario se nao vier do ambiente (evita crash de boot)\nif [ -z \"${APP_KEY:-}\" ]; then\n  export APP_KEY=\"base64:$(php -r 'echo base64_encode(random_bytes(32));')\"\n  echo \"APP_KEY nao definido; usando chave temporaria nesta instancia\"\nfi\n\n# Permissoes\nmkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache\nchown -R www-data:www-data storage bootstrap/cache\nchmod -R 775 storage bootstrap/cache\n\n# Migrations automatizadas (controlaveis via env)\nif [ \"${RUN_MIGRATIONS_ON_START:-true}\" = \"true\" ]; then\n  php artisan migrate --force --no-interaction\n  if [ \"${SEED_ON_START:-false}\" = \"true\" ]; then\n    php artisan db:seed --force --no-interaction\n  fi\nfi\n\n# Garante MPM correto e inicia Apache\na2dismod mpm_event mpm_worker || true\na2enmod mpm_prefork || true\nexec apache2-foreground\n' > /usr/local/bin/start-railway.sh \
    && chmod +x /usr/local/bin/start-railway.sh

# Usa envs fornecidos pela Railway
CMD ["start-railway.sh"]
