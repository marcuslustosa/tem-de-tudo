#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# 1. Preparar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache
chmod -R 777 storage
chmod -R 777 bootstrap/cache

# 2. Configurar ambiente
if [ ! -f ".env" ]; then
    echo "Criando arquivo .env..."
    cp .env.example .env
fi

# 3. Configurar variáveis
echo "Configurando variáveis de ambiente..."
{
    echo "APP_ENV=production"
    echo "APP_DEBUG=false"
    echo "APP_KEY="
    echo "LOG_CHANNEL=errorlog"
    echo "LOG_LEVEL=debug"
    echo "SESSION_DRIVER=database"
    echo "DB_CONNECTION=pgsql"
    echo "DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com"
    echo "DB_PORT=5432"
    echo "DB_DATABASE=tem_de_tudo_database"
    echo "DB_USERNAME=tem_de_tudo_database_user"
} > .env

# 4. Gerar chave da aplicação
php artisan key:generate --force

# 5. Verificar dependências
echo "Verificando dependências..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Instalando dependências..."
    composer install --no-dev --prefer-dist --no-scripts --no-progress
fi

# 6. Otimizar autoloader
composer dump-autoload --optimize --no-dev

# 7. Configurar cache
echo "Configurando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Executar migrations
echo "Executando migrations..."
php artisan migrate --force

# 9. Ajustar permissões finais
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 10. Iniciar Apache
echo "Iniciando servidor..."
apache2-foreground
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
