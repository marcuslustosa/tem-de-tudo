FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy Apache configuration
COPY docker/apache-default.conf /etc/apache2/sites-available/000-default.conf

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/database

# Create directories
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/database

# Criar script de inicialização robusto com logs detalhados
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== TemDeTudo Iniciando $(date) ==="\n\
\n\
# Função para log
log() {\n\
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1"\n\
}\n\
\n\
# Verificar ambiente PHP\n\
log "Verificando PHP..."\n\
php -v\n\
\n\
# Verificar módulos do Apache (não falha)\n\
log "Módulos Apache carregados:"\n\
apache2ctl -M 2>&1 | grep -i "rewrite\\|headers" || true\n\
\n\
cd /var/www/html\n\
\n\
# Debug - listar arquivos\n\
log "Arquivos no diretório atual:"\n\
ls -la\n\
\n\
# Configuração do ambiente\n\
log "Procurando arquivo de configuração..."\n\
\n\
if [ -f ".env.render" ]; then\n\
    log "Encontrado .env.render - copiando para .env"\n\
    cp .env.render .env\n\
elif [ -f ".env.example" ]; then\n\
    log "Usando .env.example como fallback"\n\
    cp .env.example .env\n\
else\n\
    log "Criando .env básico"\n\
    echo "APP_NAME=TemDeTudo" > .env\n\
    echo "APP_ENV=production" >> .env\n\
    echo "APP_DEBUG=false" >> .env\n\
    echo "APP_URL=https://tem-de-tudo.onrender.com" >> .env\n\
    echo "DB_CONNECTION=sqlite" >> .env\n\
    echo "CACHE_DRIVER=file" >> .env\n\
    echo "SESSION_DRIVER=file" >> .env\n\
fi\n\
\n\
# Configuração do banco de dados\n\
log "Configurando banco de dados..."\n\
if [ ! -z "$DATABASE_URL" ]; then\n\
    log "Configurando PostgreSQL com DATABASE_URL"\n\
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env\n\
    \n\
    if [ ! -z "$DB_HOST" ]; then\n\
        sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" .env\n\
        sed -i "s/DB_PORT=.*/DB_PORT=${DB_PORT:-5432}/" .env\n\
        sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env\n\
        sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env\n\
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env\n\
    fi\n\
else\n\
    log "Usando SQLite (fallback)"\n\
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env || echo "DB_CONNECTION=sqlite" >> .env\n\
    mkdir -p database\n\
    touch database/database.sqlite\n\
    chmod 775 database/database.sqlite\n\
fi\n\
\n\
# Limpar caches (não falha se houver erro)\n\
log "Limpando caches..."\n\
php artisan config:clear 2>/dev/null || true\n\
php artisan cache:clear 2>/dev/null || true\n\
php artisan view:clear 2>/dev/null || true\n\
php artisan route:clear 2>/dev/null || true\n\
\n\
# Mostrar extensões PHP instaladas (apenas informativo)\n\
log "Extensões PHP instaladas:"\n\
php -m | grep -i "pdo\\|json\\|openssl\\|mbstring" || true\n\
\n\
# Gerar chave se necessário\n\
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=base64:$" .env; then\n\
    log "Gerando APP_KEY..."\n\
    php artisan key:generate --force 2>/dev/null || true\n\
fi\n\
\n\
# Migrations e Seeds (não falha se houver erro)\n\
log "Executando migrations..."\n\
php artisan migrate --force 2>/dev/null || log "AVISO: Migration pulada"\n\
\n\
log "Executando seeds..."\n\
php artisan db:seed --force 2>/dev/null || log "AVISO: Seeding pulado"\n\
\n\
# Verificar e ajustar permissões e diretórios\n\
log "Verificando diretórios e permissões..."\n\
\n\
DIRS_TO_CHECK=(\n\
    "/var/www/html/storage"\n\
    "/var/www/html/storage/framework"\n\
    "/var/www/html/storage/framework/views"\n\
    "/var/www/html/storage/framework/cache"\n\
    "/var/www/html/storage/framework/sessions"\n\
    "/var/www/html/storage/logs"\n\
    "/var/www/html/bootstrap/cache"\n\
    "/var/www/html/public"\n\
)\n\
\n\
for dir in "${DIRS_TO_CHECK[@]}"; do\n\
    if [ ! -d "$dir" ]; then\n\
        log "Criando diretório: $dir"\n\
        mkdir -p "$dir"\n\
    fi\n\
    log "Ajustando permissões: $dir"\n\
    chown -R www-data:www-data "$dir"\n\
    chmod -R 775 "$dir"\n\
done\n\
\n\
# Verificar permissões\n\
log "Verificando permissões finais..."\n\
ls -la /var/www/html/storage\n\
ls -la /var/www/html/bootstrap/cache\n\
\n\
# Ajustar permissões finais\n\
log "Ajustando permissões finais..."\n\
find /var/www/html/storage -type d -exec chmod 775 {} \\; 2>/dev/null || true\n\
find /var/www/html/storage -type f -exec chmod 664 {} \\; 2>/dev/null || true\n\
\n\
# Criar arquivo de status\n\
mkdir -p /var/www/html/storage/logs\n\
echo "$(date) - Ambiente pronto" > /var/www/html/storage/logs/startup.log 2>/dev/null || true\n\
\n\
log "=== Iniciando Apache ==="\n\
log "Aplicação disponível na porta 80"\n\
\n\
# Iniciar Apache\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]