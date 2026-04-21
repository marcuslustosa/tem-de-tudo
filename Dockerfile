FROM php:8.2-apache

# Instala dependências do Laravel + extensões comuns
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip unzip git curl \
    libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring exif pcntl bcmath gd \
    # Forca apenas um MPM (prefork) para mod_php
    && rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite headers expires \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && apache2ctl -M 2>/dev/null | grep -q 'mpm_prefork_module' \
    && [ "$(apache2ctl -M 2>/dev/null | grep -c 'mpm_')" -eq 1 ] \
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
RUN cat <<'EOF' > /usr/local/bin/start-railway.sh \
 && chmod +x /usr/local/bin/start-railway.sh
#!/bin/bash
set -euo pipefail

PORT=${PORT:-8080}
# Ajusta Apache para porta dinamica
printf "Listen ${PORT}\n" > /etc/apache2/ports.conf
sed -i "s#<VirtualHost .*:.*>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# Se so temos DATABASE_URL, derivar DB_* para o Laravel
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
  eval "$(php /var/www/html/docker/export-db-env.php)"
fi

# APP_KEY temporario se nao vier do ambiente (evita crash de boot)
if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "APP_KEY nao definido; usando chave temporaria nesta instancia"
fi

# Permissoes
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Migrations automatizadas (controlaveis via env)
if [ "${RUN_MIGRATIONS_ON_START:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
  if [ "${SEED_ON_START:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
  fi
fi

# Padrao de execucao no Railway: servidor PHP embutido (evita crash por MPM do Apache)
if [ "${WEB_SERVER:-artisan}" = "artisan" ]; then
  echo "Iniciando com php artisan serve na porta ${PORT} (WEB_SERVER=artisan)"
  exec php artisan serve --host=0.0.0.0 --port="${PORT}"
fi

# Safety net no runtime: garante um unico MPM carregado
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

if ! apache2ctl -M 2>/dev/null | grep -q 'mpm_prefork_module'; then
  echo "ERRO: mpm_prefork nao carregado"
  apache2ctl -M || true
  exit 1
fi

if [ "$(apache2ctl -M 2>/dev/null | grep -c 'mpm_')" -ne 1 ]; then
  echo "ERRO: mais de um MPM carregado"
  apache2ctl -M || true
  exit 1
fi

exec apache2-foreground
EOF

# Garante que o script de boot sempre rode (mesmo com command override)
ENTRYPOINT ["start-railway.sh"]
