#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

echo "Verificando diretório vendor..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "❌ ERRO: Dependências não foram instaladas corretamente no build"
    ls -la
    exit 1
fi

# 1. Configurar ambiente
echo "Configurando ambiente..."
{
    echo "APP_NAME='Tem de Tudo'"
    echo "APP_ENV=production"
    echo "APP_DEBUG=false"
    echo "APP_URL=https://tem-de-tudo.onrender.com"
    echo "APP_KEY="
    echo "LOG_CHANNEL=errorlog"
    echo "LOG_LEVEL=debug"
    echo "DB_CONNECTION=pgsql"
    echo "DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com"
    echo "DB_PORT=5432"
    echo "DB_DATABASE=tem_de_tudo_database"
    echo "DB_USERNAME=tem_de_tudo_database_user"
    echo "SESSION_DRIVER=database"
    echo "FILESYSTEM_DISK=local"
    echo "QUEUE_CONNECTION=sync"
    echo "BROADCAST_DRIVER=log"
    echo "CACHE_DRIVER=file"
} > .env

echo "Gerando chave da aplicação..."
php artisan key:generate --force

echo "Limpando e cacheando configurações..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan view:cache

echo "Executando migrations..."
php artisan migrate --force

echo "Ajustando permissões finais..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✓ Iniciando Apache..."
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
