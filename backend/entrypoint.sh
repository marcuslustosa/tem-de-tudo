#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# Configurar variáveis do banco
export DB_CONNECTION=pgsql
export DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com
export DB_PORT=5432
export DB_DATABASE=tem_de_tudo_database
export DB_USERNAME=tem_de_tudo_database_user
export DB_PASSWORD=9P0c4gV4RZd8moh9ZYqGIo0BmyZ10XhA

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

echo "4.0 Limpando ambiente Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "4.1 Alterando driver de sessão temporariamente..."
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env

echo "4.2 Configurando PHP..."
export PHP_CLI_SERVER_WORKERS=1
php -d max_execution_time=300 artisan config:clear

echo "4.3 Tentando remover tabela sessions..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "
    DO \$\$ 
    BEGIN
        EXECUTE 
            (SELECT 'DROP TABLE IF EXISTS ' || string_agg(quote_ident(schemaname) || '.' || quote_ident(tablename), ', ') 
             FROM pg_tables 
             WHERE tablename = 'sessions');
    END \$\$;
" || true

echo "4.4 Removendo dependências da sessions..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "
    DROP TABLE IF EXISTS migrations CASCADE;
    DELETE FROM migrations WHERE migration LIKE '%_create_sessions_table%';
" || true

echo "4.5 Verificando estado atual..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\d sessions" || true

echo "4.6 Executando apenas a migration da sessions..."
rm -f database/migrations/*_create_sessions_table.php
php artisan make:migration create_sessions_table --create=sessions || true
php artisan migrate --force --path=database/migrations/*_create_sessions_table.php || true

echo "4.7 Executando restante das migrations..."
php -d max_execution_time=300 artisan migrate:fresh --force --no-interaction --drop-views --drop-types || {
    echo "⚠️ Fresh falhou, tentando alternativa..."
    
    echo "4.7 Removendo sessions manualmente..."
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "DROP TABLE IF EXISTS sessions;" || true
    
    echo "4.8 Tentando migrate individual..."
    php -d max_execution_time=300 artisan migrate --force --no-interaction --path=database/migrations/2023_01_01_000000_create_sessions_table.php || {
        echo "❌ ERRO FATAL NAS MIGRATIONS"
        echo "Diagnóstico final:"
        PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\d sessions" || true
        echo "Logs do Laravel:"
        tail -n 50 storage/logs/laravel.log || true
        exit 1
    }
}

echo "4.4 Verificando tabelas criadas..."
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "\dt" || true

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
