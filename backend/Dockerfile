FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean

# Set document root
COPY . /var/www/html/

# Configure Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap

CMD ["apache2-foreground"]
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