#!/bin/bash
set -e

echo "=== Tem de Tudo - Starting ==="
echo "PHP Version: $(php -v | head -n 1)"
echo "Current directory: $(pwd)"
echo "Files: $(ls -la)"

# Create directories
echo "Creating storage directories..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database
echo "Directories created!"

# Set permissions (more permissive for Render)
echo "Setting permissions..."
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache
echo "Permissions set!"

# Clear any cached config that might cause issues
echo "Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
echo "Caches cleared!"

# Copy production environment if exists
if [ -f ".env.render" ]; then
    echo "Using Render production environment"
    cp .env.render .env
elif [ -f ".env.production" ]; then
    echo "Using production environment"
    cp .env.production .env
else
    echo "WARNING: No production env found, using default .env"
fi

echo "Checking .env file..."
if [ -f ".env" ]; then
    echo ".env exists"
    echo "APP_KEY status: $(grep APP_KEY .env | head -n 1)"

    # Configurar ambiente de produção
    sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
    sed -i 's/LOG_CHANNEL=.*/LOG_CHANNEL=errorlog/' .env
    sed -i 's/LOG_LEVEL=.*/LOG_LEVEL=error/' .env
    sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
    sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
    
    echo "Environment configured for production"
else
    echo "ERROR: .env file not found!"
    exit 1
fi

# Testar conexão com o banco
echo "Testing database connection..."
php artisan db:monitor || {
    echo "ERROR: Database connection failed!"
    php artisan db:show || true
    cat storage/logs/laravel.log || true
    exit 1
}

# Generate application key if not exists
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key"
    php artisan key:generate --force
else
    echo "APP_KEY already set"
fi

# Database setup
echo "=== Configurando Banco de Dados ==="
echo "Driver: PostgreSQL (forçado)"

# Garantir que estamos usando PostgreSQL
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
sed -i 's/DB_HOST=.*/DB_HOST=dpg-d3vps0k9c44c738q64gg-a.oregon-postgres.render.com/' .env
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
