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

echo "Configuração do PostgreSQL:"
echo "Host: $(grep DB_HOST .env)"
echo "Port: $(grep DB_PORT .env)"
echo "Database: $(grep DB_DATABASE .env)"
echo "Username: $(grep DB_USERNAME .env)"

echo "Executando migrations..."
php artisan migrate --force || {
    echo "❌ Erro ao executar migrations"
    php artisan migrate:status
    cat storage/logs/laravel.log || true
    exit 1
}

echo "✓ Migrations executadas com sucesso!"
php artisan db:monitor

# Seed database (ignore errors if already exists)
echo "Executando seeds..."
php artisan db:seed --force || echo "Seeds já existem"

echo "=== Sistema Pronto ==="
echo "Starting Apache..."

# Start Apache
exec apache2-foreground
