#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# 1. Preparar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 777 storage bootstrap/cache

# 2. Configurar banco de dados
echo "Verificando conexão PostgreSQL..."
export PGPASSWORD="9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA"
if psql -h dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com -U tem_de_tudo_database_user -d tem_de_tudo_database -c '\l' >/dev/null 2>&1; then
    echo "✓ Conexão PostgreSQL OK"
else
    echo "❌ ERRO: Não foi possível conectar ao PostgreSQL"
    exit 1
fi

# 3. Criar e configurar .env
echo "Configurando ambiente..."
cat > .env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_DEBUG=false
APP_KEY=

LOG_CHANNEL=errorlog
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user
DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
EOF

# 4. Gerar APP_KEY
echo "Gerando APP_KEY..."
php artisan key:generate --force

# 5. Limpar caches
echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear

# 6. Executar migrations
echo "Executando migrations..."
php artisan migrate --force

# 7. Verificar tabelas
echo "Verificando tabelas..."
export PGPASSWORD="9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA"
psql -h dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com -U tem_de_tudo_database_user -d tem_de_tudo_database -c '\dt'

# 8. Iniciar Apache
echo "✓ Iniciando servidor Apache..."
exec apache2-foreground
sed -i 's/DB_PORT=.*/DB_PORT=5432/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=tem_de_tudo_database/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=tem_de_tudo_database_user/' .env

echo "=== DIAGNÓSTICO DO BANCO DE DADOS ==="
echo "1. Configuração:"
echo "Connection: $(grep DB_CONNECTION .env)"
echo "Host: $(grep DB_HOST .env)"
echo "Database: $(grep DB_DATABASE .env)"

echo "2. Testando conexão..."
php artisan db:monitor --verbose || {
    echo "❌ ERRO DE CONEXÃO"
    php artisan db:show
    echo "Logs do Laravel:"
    tail -n 50 storage/logs/laravel.log || true
    exit 1
}

echo "3. Status das migrations..."
php artisan migrate:status || {
    echo "❌ ERRO AO VERIFICAR MIGRATIONS"
    exit 1
}

echo "4. Executando migrations..."
php artisan migrate --force --no-interaction || {
    echo "❌ ERRO AO EXECUTAR MIGRATIONS"
    echo "Logs do Laravel:"
    tail -n 50 storage/logs/laravel.log || true
    exit 1
}

echo "5. Verificando tabelas criadas..."
php artisan db:table || {
    echo "❌ ERRO AO LISTAR TABELAS"
    exit 1
}

echo "6. Testando tabela sessions..."
php artisan db:table sessions || {
    echo "❌ TABELA SESSIONS NÃO ENCONTRADA"
    exit 1
}

echo "✓ Banco de dados configurado com sucesso!"

# Seed database (ignore errors if already exists)
echo "7. Executando seeds..."
php artisan db:seed --force || echo "Seeds já existem"

echo "=== Sistema Pronto ==="
echo "Starting Apache..."

# Start Apache
exec apache2-foreground
