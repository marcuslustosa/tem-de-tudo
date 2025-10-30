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
    postgresql-client \
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
# PASSO 2: Configurar APP_URL e APP_ENV (modo debug)\n\
log "Configurando APP_URL e APP_ENV..."\n\
sed -i "/^APP_URL=/d" .env 2>/dev/null || true\n\
sed -i "/^APP_ENV=/d" .env 2>/dev/null || true\n\
sed -i "/^APP_DEBUG=/d" .env 2>/dev/null || true\n\
echo "APP_URL=https://app-tem-de-tudo.onrender.com" >> .env\n\
echo "APP_ENV=production" >> .env\n\
echo "APP_DEBUG=true" >> .env\n\
echo "LOG_LEVEL=debug" >> .env\n\
echo "DISPLAY_ERRORS=true" >> .env\n\
\n\
# PASSO 3: FORÇAR PostgreSQL SEMPRE\n\
log "========================================"\n\
log "CONFIGURANDO POSTGRESQL (FORÇADO)"\n\
log "========================================"\n\
\n\
# Remover qualquer configuração DB antiga\n\
sed -i "/^DB_CONNECTION=/d" .env 2>/dev/null || true\n\
sed -i "/^DB_HOST=/d" .env 2>/dev/null || true\n\
sed -i "/^DB_PORT=/d" .env 2>/dev/null || true\n\
sed -i "/^DB_DATABASE=/d" .env 2>/dev/null || true\n\
sed -i "/^DB_USERNAME=/d" .env 2>/dev/null || true\n\
sed -i "/^DB_PASSWORD=/d" .env 2>/dev/null || true\n\
\n\
# FORÇAR valores hardcoded do render.yaml\n\
echo "DB_CONNECTION=pgsql" >> .env\n\
echo "DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com" >> .env\n\
echo "DB_PORT=5432" >> .env\n\
echo "DB_DATABASE=tem_de_tudo_database" >> .env\n\
echo "DB_USERNAME=tem_de_tudo_database_user" >> .env\n\
echo "DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA" >> .env\n\
\n\
log "✓ PostgreSQL FORÇADO:"\n\
log "  Host: dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com"\n\
log "  Database: tem_de_tudo_database"\n\
log "  User: tem_de_tudo_database_user"\n\
log "  Port: 5432"\n\
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
# PASSO 4: Criar tabelas críticas ANTES do Apache\n\
log "========================================"\n\
log "CRIANDO TABELAS CRÍTICAS"\n\
log "========================================"\n\
\n\
log "Criando tabela sessions via SQL..."\n\
\n\
# Criar tabela sessions\n\
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE << 'SQL_SCRIPT'\n\
CREATE TABLE IF NOT EXISTS sessions (\n\
    id VARCHAR(255) PRIMARY KEY,\n\
    user_id BIGINT,\n\
    ip_address VARCHAR(45),\n\
    user_agent TEXT,\n\
    payload TEXT NOT NULL,\n\
    last_activity INTEGER NOT NULL\n\
);\n\
\n\
CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions(user_id);\n\
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions(last_activity);\n\
ALTER TABLE sessions OWNER TO tem_de_tudo_database_user;\n\
SQL_SCRIPT\n\
\n\
log "✓ Tabela sessions criada com sucesso!"\n\
\n\
# Listar tabelas\n\
log "Verificando tabelas:"\n\
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "\dt"\n\
\n\
# Verificar ambiente\n\
echo "=== Verificando Ambiente ==="\n\
if [ ! -f ".env" ]; then\n\
    echo "ERRO CRÍTICO: .env não encontrado!"\n\
    ls -la\n\
    exit 1\n\
fi\n\
\n\
echo "Conteúdo do .env:"\n\
cat .env | grep -v "PASSWORD"\n\
\n\
# Garantir storage está gravável\n\
chown -R www-data:www-data storage bootstrap/cache\n\
chmod -R 775 storage bootstrap/cache\n\
\n\
# Limpar caches\n\
echo "Limpando caches..."\n\
LARAVEL_ENV=production php artisan config:clear\n\
LARAVEL_ENV=production php artisan cache:clear\n\
\n\
# Forçar recarregar config\n\
echo "Recarregando configuração..."\n\
LARAVEL_ENV=production php artisan config:cache\n\
\n\
# Verificar conexão PostgreSQL\n\
echo "Testando conexão com PostgreSQL..."\n\
if LARAVEL_ENV=production php artisan db:show 2>&1; then\n\
    echo "✓ Conexão PostgreSQL OK"\n\
else\n\
    echo "✗ ERRO conectando ao PostgreSQL"\n\
    exit 1\n\
fi\n\
\n\
# Instalar tabela migrations\n\
echo "Instalando tabela migrations..."\n\
LARAVEL_ENV=production php artisan migrate:install --force\n\
\n\
# Executar migrations\n\
echo "Executando migrations..."\n\
LARAVEL_ENV=production php artisan migrate --force --verbose\n\
\n\
# Seeds essenciais\n\
echo "Executando seeds essenciais..."\n\
LARAVEL_ENV=production php artisan db:seed --force --class=AdminUserSeeder\n\
\n\
# Verificar tabelas\n\
echo "=== Verificando Tabelas Criadas ==="\n\
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "\dt" || echo "Erro listando tabelas"\n\
\n\
echo "Script concluído em $(date)"\n\
MIGRATION_SCRIPT\n\
\n\
chmod +x /var/www/html/run-migrations.sh\n\
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
log "=== Iniciando Apache e Migrations em Background ==="\n\
log "Aplicação disponível na porta 80"\n\
\n\
# Rodar migrations em background\n\
/var/www/html/run-migrations.sh > /var/www/html/storage/logs/migrations.log 2>&1 &\n\
\n\
# Iniciar Apache\n\
exec apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]