#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# 1. Preparar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 777 storage bootstrap/cache

# 2. Configurar .env para produção
echo "Configurando ambiente..."
cat > .env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_DEBUG=false
APP_KEY=

DB_CONNECTION=pgsql
DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user
DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
QUEUE_CONNECTION=database

JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
JWT_TTL=60
JWT_REFRESH_TTL=20160

LOG_CHANNEL=error
LOG_LEVEL=error

MAIL_MAILER=log
EOF

# 3. Gerar APP_KEY
echo "Gerando APP_KEY..."
php artisan key:generate --force

# 4. Testar conexão com banco
echo "Testando conexão com PostgreSQL..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\l" || {
    echo "❌ Erro ao conectar no banco"
    exit 1
}
echo "✓ Conexão PostgreSQL estabelecida"

# 5. Executar migrations na ordem correta
echo "Executando migrations..."

# Primeiro, garantir que não há tabelas órfãs
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "DROP TABLE IF EXISTS sessions CASCADE;" || true

# Executar migrations
php artisan migrate --force

# 6. Verificar tabelas criadas
echo "Verificando tabelas criadas..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\dt"

# 7. Executar seeders
echo "Executando seeders..."
php artisan db:seed --force

# 8. Limpar caches
echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 9. Criar link simbólico do storage (se necessário)
echo "Configurando storage link..."
php artisan storage:link || true

# 10. Verificar status final
echo "Verificação final..."
php artisan migrate:status

echo "=== Sistema Pronto ==="
echo "Starting Apache..."

# Start Apache
exec apache2-foreground
