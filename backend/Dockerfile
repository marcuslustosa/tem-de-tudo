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
# Configuração do ambiente
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
    # Usar pgsql por padrão, será sobrescrito se necessário\n\
    echo "DB_CONNECTION=pgsql" >> .env\n\
    echo "CACHE_DRIVER=file" >> .env\n\
    echo "SESSION_DRIVER=file" >> .env\n\
fi\n\
\n\
# PASSO 1: Garantir APP_KEY existe e está correta\n\
log "Configurando APP_KEY..."\n\
\n\
# Se APP_KEY vem da variável de ambiente do Render\n\
if [ ! -z "$APP_KEY" ]; then\n\
    log "APP_KEY fornecida via variável de ambiente: ${APP_KEY:0:20}..."\n\
    # Garantir que está no formato correto\n\
    if [[ "$APP_KEY" == base64:* ]]; then\n\
        log "APP_KEY já está em formato base64"\n\
    else\n\
        log "Adicionando prefixo base64: à APP_KEY"\n\
        APP_KEY="base64:$APP_KEY"\n\
    fi\n\
    # Atualizar ou adicionar no .env\n\
    if grep -q "^APP_KEY=" .env 2>/dev/null; then\n\
        sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env\n\
    else\n\
        echo "APP_KEY=$APP_KEY" >> .env\n\
    fi\n\
else\n\
    # Gerar nova chave usando openssl\n\
    log "Gerando nova APP_KEY com openssl..."\n\
    NEW_KEY="base64:$(openssl rand -base64 32)"\n\
    if grep -q "^APP_KEY=" .env 2>/dev/null; then\n\
        sed -i "s|^APP_KEY=.*|APP_KEY=$NEW_KEY|" .env\n\
    else\n\
        echo "APP_KEY=$NEW_KEY" >> .env\n\
    fi\n\
    log "APP_KEY gerada: ${NEW_KEY:0:20}..."\n\
fi\n\
\n\
# PASSO 2: Configurar APP_URL\n\
if [ ! -z "$APP_URL" ]; then\n\
    sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env 2>/dev/null || echo "APP_URL=$APP_URL" >> .env\n\
fi\n\
\n\
# PASSO 3: Configurar banco de dados\n\
log "Configurando banco de dados..."\n\
log "Variáveis recebidas: DB_HOST=$DB_HOST DB_DATABASE=$DB_DATABASE"\n\
\n\
# Se temos credenciais do PostgreSQL, usar elas\n\
if [ ! -z "$DB_HOST" ] && [ ! -z "$DB_DATABASE" ]; then\n\
    log "✓ Configurando PostgreSQL com credenciais fornecidas"\n\
    \n\
    # FORÇAR PostgreSQL - remover linhas antigas e adicionar novas\n\
    sed -i "/^DB_CONNECTION=/d" .env\n\
    sed -i "/^DB_HOST=/d" .env\n\
    sed -i "/^DB_PORT=/d" .env\n\
    sed -i "/^DB_DATABASE=/d" .env\n\
    sed -i "/^DB_USERNAME=/d" .env\n\
    sed -i "/^DB_PASSWORD=/d" .env\n\
    \n\
    echo "DB_CONNECTION=pgsql" >> .env\n\
    echo "DB_HOST=$DB_HOST" >> .env\n\
    echo "DB_PORT=${DB_PORT:-5432}" >> .env\n\
    echo "DB_DATABASE=$DB_DATABASE" >> .env\n\
    echo "DB_USERNAME=$DB_USERNAME" >> .env\n\
    echo "DB_PASSWORD=$DB_PASSWORD" >> .env\n\
    \n\
    log "PostgreSQL configurado: $DB_USERNAME@$DB_HOST:${DB_PORT:-5432}/$DB_DATABASE"\n\
else\n\
    log "✗ Credenciais PostgreSQL não fornecidas, usando SQLite"\n\
    sed -i "/^DB_CONNECTION=/d" .env\n\
    echo "DB_CONNECTION=sqlite" >> .env\n\
    mkdir -p database\n\
    touch database/database.sqlite\n\
    chmod 775 database/database.sqlite\n\
fi\n\
\n\
# Verificação e exibição da configuração\n\
log "========================================"\n\
log "VERIFICAÇÃO FINAL DO .ENV"\n\
log "========================================"\n\
log "Caminho: $(pwd)/.env"\n\
log "Arquivo existe: $([ -f .env ] && echo SIM || echo NAO)"\n\
log "Tamanho: $([ -f .env ] && wc -c < .env || echo 0) bytes"\n\
log "Permissões: $([ -f .env ] && ls -l .env | awk {print \\$1} || echo N/A)"\n\
log ""\n\
log "Conteúdo das chaves principais:"\n\
APP_KEY_LINE=$(grep "^APP_KEY=" .env 2>/dev/null || echo "APP_KEY=NOT_FOUND")\n\
log "APP_KEY: ${APP_KEY_LINE:0:30}..."\n\
log "APP_URL: $(grep ^APP_URL= .env 2>/dev/null || echo NOT_SET)"\n\
log "APP_ENV: $(grep ^APP_ENV= .env 2>/dev/null || echo NOT_SET)"\n\
log "DB_CONNECTION: $(grep ^DB_CONNECTION= .env 2>/dev/null || echo NOT_SET)"\n\
log "========================================"\n\
\n\
# Copiar .env para public para debug (será removido depois)\n\
log "Criando arquivo de diagnóstico..."\n\
cat .env > public/.env-copy.txt 2>/dev/null || true\n\
chmod 644 public/.env-copy.txt 2>/dev/null || true\n\
\n\
# PASSO 4: Limpar caches e recarregar configuração\n\
log "Limpando todos os caches..."\n\
php artisan config:clear || log "Falha ao limpar config"\n\
php artisan cache:clear || log "Falha ao limpar cache"\n\
php artisan view:clear || log "Falha ao limpar views"\n\
php artisan route:clear || log "Falha ao limpar routes"\n\
\n\
# Forçar reload da configuração\n\
log "Recarregando configuração do Laravel..."\n\
php artisan config:cache || log "Falha ao cachear config"\n\
\n\
# PASSO 5: Executar Migrations (CRÍTICO)\n\
log "========================================"\n\
log "EXECUTANDO MIGRATIONS"\n\
log "========================================"\n\
\n\
# Verificar conexão com banco\n\
log "Testando conexão com banco de dados..."\n\
php artisan db:show 2>&1 || log "Falha ao conectar no banco"\n\
\n\
# Executar migrations com output completo\n\
log "Rodando migrations..."\n\
if php artisan migrate --force 2>&1; then\n\
    log "✓ Migrations executadas com sucesso"\n\
else\n\
    log "✗ ERRO nas migrations - Tentando criar apenas tabelas essenciais"\n\
    # Se falhar, tentar criar pelo menos a tabela de sessions\n\
    php artisan migrate:install --force 2>&1 || true\n\
    php artisan migrate --path=database/migrations/0001_01_01_000001_create_cache_table.php --force 2>&1 || true\n\
fi\n\
\n\
log "Listando tabelas criadas:"\n\
php artisan db:table --database=pgsql 2>&1 | head -20 || log "Não foi possível listar tabelas"\n\
\n\
# Seeds (opcional, não crítico)\n\
log "Executando seeds..."\n\
php artisan db:seed --force 2>&1 || log "⚠ Seeding pulado (não crítico)"\n\
\n\
log "========================================"\n\
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