#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

# 1. Preparar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p database
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# 2. Configurar ambiente
if [ ! -f ".env" ]; then
    echo "Criando arquivo .env..."
    cp .env.example .env
fi

# 3. Configurar variáveis
echo "Configurando variáveis de ambiente..."
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's/LOG_CHANNEL=.*/LOG_CHANNEL=errorlog/' .env
sed -i 's/LOG_LEVEL=.*/LOG_LEVEL=error/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env

# 4. Configurar PostgreSQL
echo "Configurando PostgreSQL..."
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
sed -i "s|DB_HOST=.*|DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com|" .env
sed -i "s|DB_PORT=.*|DB_PORT=5432|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=tem_de_tudo_database|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=tem_de_tudo_database_user|" .env

# 5. Verificar APP_KEY
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
    echo "Gerando nova APP_KEY..."
    php artisan key:generate --force
fi

# 6. Limpar caches
echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 7. Executar migrations
echo "Executando migrations..."
php artisan migrate --force || {
    echo "❌ Erro nas migrations"
    php artisan migrate:status
    exit 1
}

# 8. Verificar conexão
echo "Verificando banco de dados..."
php artisan db:monitor || {
    echo "❌ Erro no banco de dados"
    cat storage/logs/laravel.log
    exit 1
}

# 9. Iniciar Apache
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
