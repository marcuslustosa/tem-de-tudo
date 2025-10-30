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
# Verificar ambiente PHP
log "Verificando PHP..."\n\
php -v || { log "ERRO: PHP não está instalado corretamente"; exit 1; }\n\
\n\
# Verificar módulos do Apache
log "Verificando módulos Apache..."\n\
apache2ctl -M || { log "ERRO: Apache não está configurado corretamente"; exit 1; }\n\
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
# Limpar e otimizar\n\
log "Limpando e otimizando Laravel..."\n\
\n\
# Limpar todos os caches\n\
php artisan config:clear || log "AVISO: Falha ao limpar config cache"\n\
php artisan cache:clear || log "AVISO: Falha ao limpar cache"\n\
php artisan view:clear || log "AVISO: Falha ao limpar view cache"\n\
php artisan route:clear || log "AVISO: Falha ao limpar route cache"\n\
\n\
# Verificar configuração do Laravel\n\
log "Verificando configuração do Laravel..."\n\
php artisan config:check || log "ERRO: Problemas na configuração do Laravel"\n\
\n\
# Validar rotas\n\
log "Verificando rotas..."\n\
php artisan route:list --json > /tmp/routes.json || log "ERRO: Problemas com as rotas"\n\
\n\
# Verificar extensões PHP necessárias\n\
for ext in pdo pdo_pgsql json openssl mbstring tokenizer xml; do\n\
    if ! php -m | grep -q "^$ext$"; then\n\
        log "ERRO: Extensão PHP requerida não encontrada: $ext"\n\
        exit 1\n\
    fi\n\
done\n\
\n\
# Otimizar\n\
log "Otimizando aplicação..."\n\
php artisan optimize || log "AVISO: Falha na otimização"\n\
\n\
# Gerar chave se necessário\n\
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=base64:$" .env; then\n\
    log "Gerando APP_KEY..."\n\
    php artisan key:generate --force\n\
fi\n\
\n\
# Testar conexão com banco de dados\n\
log "Testando conexão com banco de dados..."\n\
php artisan db:monitor || {\n\
    log "AVISO: Falha na conexão com banco de dados. Detalhes:"\n\
    php artisan db:show --json || true\n\
}\n\
\n\
# Verificar status do Laravel\n\
log "Verificando status do Laravel..."\n\
php artisan about || true\n\
\n\
# Migrations e Seeds\n\
echo "Executando migrations..."\n\
php artisan migrate --force || echo "Migration falhou, mas continuando..."\n\
\n\
echo "Executando seeds..."\n\
php artisan db:seed --force || echo "Seeding falhou, mas continuando..."\n\
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
# Verificação final do ambiente\n\
log "Realizando verificação final..."\n\
\n\
# Verificar arquivos críticos\n\
CRITICAL_FILES=(\n\
    "/var/www/html/public/index.php"\n\
    "/var/www/html/bootstrap/app.php"\n\
    "/var/www/html/routes/web.php"\n\
    "/var/www/html/routes/api.php"\n\
)\n\
\n\
for file in "${CRITICAL_FILES[@]}"; do\n\
    if [ ! -f "$file" ]; then\n\
        log "ERRO CRÍTICO: Arquivo não encontrado: $file"\n\
        exit 1\n\
    fi\n\
done\n\
\n\
# Testar conexão com serviços externos\n\
if [ ! -z "$MAIL_HOST" ]; then\n\
    log "Verificando conexão SMTP..."\n\
    nc -zv $MAIL_HOST $MAIL_PORT || log "AVISO: Não foi possível conectar ao servidor SMTP"\n\
fi\n\
\n\
# Verificar diretório público\n\
if [ ! -d "/var/www/html/public" ]; then\n\
    log "ERRO CRÍTICO: Diretório public/ não encontrado"\n\
    exit 1\n\
fi\n\
\n\
# Verificar permissões finais\n\
find /var/www/html/storage -type d -exec chmod 775 {} \;\n\
find /var/www/html/storage -type f -exec chmod 664 {} \;\n\
\n\
# Criar arquivo de status\n\
echo "$(date) - Ambiente verificado e pronto" > /var/www/html/storage/logs/startup.log\n\
\n\
log "=== Iniciando Apache ==="\n\
\n\
# Iniciar Apache em modo debug\n\
apache2ctl -e debug -D FOREGROUND' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]