#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# Configurar variáveis do banco se não existirem
if [ -z "${DB_CONNECTION}" ]; then
    echo "⚠️ DB_CONNECTION não definida, usando padrão: pgsql"
    DB_CONNECTION="pgsql"
fi

if [ -z "${DB_HOST}" ]; then
    echo "⚠️ DB_HOST não definida, usando padrão do Render"
    DB_HOST="dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com"
fi

if [ -z "${DB_DATABASE}" ]; then
    echo "⚠️ DB_DATABASE não definida, usando padrão do Render"
    DB_DATABASE="tem_de_tudo_database"
fi

if [ -z "${DB_USERNAME}" ]; then
    echo "⚠️ DB_USERNAME não definida, usando padrão do Render"
    DB_USERNAME="tem_de_tudo_database_user"
fi

if [ -z "${DB_PASSWORD}" ]; then
    echo "⚠️ DB_PASSWORD não definida, usando padrão do Render"
    DB_PASSWORD="9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA"
fi

echo "✓ Configuração do banco:"
echo "- Conexão: ${DB_CONNECTION}"
echo "- Host: ${DB_HOST}"
echo "- Database: ${DB_DATABASE}"
echo "- Username: ${DB_USERNAME}"
echo "- Password: ***********"

# 1. Preparar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 777 storage bootstrap/cache

# 2. Verificar conexão com banco
echo "Testando conexão com PostgreSQL..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\l" || {
    echo "❌ Erro ao conectar no banco"
    echo "Tentando conexão alternativa..."
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "postgres" -c "CREATE DATABASE ${DB_DATABASE};" || true
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\l" || {
        echo "❌ Erro fatal ao conectar no banco"
        exit 1
    }
}

echo "✓ Conexão PostgreSQL estabelecida"

# 2. Configurar .env
echo "Configurando ambiente..."
# Gerar chave JWT se não existir
JWT_SECRET=$(php -r "echo bin2hex(random_bytes(32));")

cat > .env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=${JWT_SECRET}
JWT_TTL=60

DB_CONNECTION=${DB_CONNECTION}
DB_HOST=${DB_HOST}
DB_PORT=5432
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
SESSION_DRIVER=array
APP_KEY=

LOG_CHANNEL=errorlog
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user
DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

SESSION_DRIVER=database
EOF

# 3. Gerar APP_KEY
echo "Gerando APP_KEY..."
php artisan key:generate --force

# 4. Executar migrations
echo "Executando migrations..."
php artisan migrate --force

# 5. Iniciar Apache
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

echo "4. Verificando status atual das migrations..."
php artisan migrate:status || true

echo "4.1 Aumentando tempo limite do PHP..."
php -d max_execution_time=300 artisan config:clear

echo "4.2 Executando migrations (modo seguro)..."
php -d max_execution_time=300 artisan migrate --force --seed --no-interaction || {
    echo "⚠️ Aviso: Primeiro método falhou, tentando alternativa..."
    
    echo "4.3 Verificando problemas no banco..."
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\d+" || true
    
    echo "4.4 Tentando migração sem seed..."
    php -d max_execution_time=300 artisan migrate --force --no-interaction || {
        echo "❌ ERRO FATAL AO EXECUTAR MIGRATIONS"
        echo "Logs do Laravel:"
        tail -n 50 storage/logs/laravel.log || true
        echo "Status final das migrations:"
        php artisan migrate:status || true
        exit 1
    }
}

echo "4.5 Verificando status final das migrations..."
php artisan migrate:status || true

echo "5. Configurando ambiente..."
# Garante diretório de sessões
mkdir -p storage/framework/sessions
chmod -R 777 storage/framework/sessions

echo "6. Configurando variáveis de ambiente..."
# Força uso de arquivo para sessões
cat >> .env << EOF
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
EOF

echo "7. Limpando cache e configurações..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "8. Verificando conexão com banco..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\d" || {
    echo "❌ Erro ao conectar no banco"
    exit 1
}

echo "9. Removendo tabela sessions se existir..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "DROP TABLE IF EXISTS sessions CASCADE;"

echo "10. Executando migrações (sem sessions)..."
php artisan migrate:refresh --force --no-interaction || {
    echo "⚠️ Tentando método alternativo..."
    php artisan migrate:fresh --force --no-interaction
}

echo "7. Limpando cache final..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "✓ Setup do banco concluído"
echo "✓ Banco de dados configurado com sucesso!"

echo "=== Sistema Pronto ==="
echo "Starting Apache..."

# Start Apache
exec apache2-foreground
