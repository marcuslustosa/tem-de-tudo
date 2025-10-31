#!/bin/bash
set -e

echo "=== Iniciando Tem de Tudo ==="

cd /var/www/html

# 1. Verificar dependências
echo "Verificando dependências..."
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "❌ ERRO: vendor/autoload.php não encontrado"
    exit 1
fi

# 2. Configurar diretórios
echo "Configurando diretórios..."
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 3. Configurar ambiente
echo "Configurando ambiente..."
cat > .env << 'EOF'
APP_NAME=Tem de Tudo
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tem-de-tudo.onrender.com

LOG_CHANNEL=errorlog
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=tem_de_tudo_database
DB_USERNAME=tem_de_tudo_database_user

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=database

SANCTUM_STATEFUL_DOMAINS=tem-de-tudo.onrender.com
SESSION_DOMAIN=.tem-de-tudo.onrender.com
EOF

# 4. Gerar chave
echo "Gerando APP_KEY..."
php artisan key:generate --show --no-ansi | grep -oP '^.*$' >> .env

# 5. Configurar Laravel
echo "Configurando Laravel..."
LARAVEL_ENV=production php artisan config:cache --no-ansi
LARAVEL_ENV=production php artisan route:cache --no-ansi
LARAVEL_ENV=production php artisan view:cache --no-ansi

# 6. Verificar banco de dados
echo "Verificando banco de dados..."
until LARAVEL_ENV=production php artisan db:monitor --no-ansi; do
    echo "🔄 Aguardando banco de dados..."
    sleep 2
done

# 7. Executar migrations
echo "Executando migrations..."
LARAVEL_ENV=production php artisan migrate --force --no-ansi

# 8. Iniciar Apache
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
