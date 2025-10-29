#!/bin/bash

echo " Iniciando Tem de Tudo..."

# Configura ambiente
export APP_ENV=production
export APP_DEBUG=false

# Cria diret?rios essenciais
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Define permiss?es
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Limpa caches
echo " Limpando caches..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Gera chave se necess?rio
echo " Configurando APP_KEY..."
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force --no-interaction 2>/dev/null || true
fi

# PRIMEIRO: Tenta PostgreSQL (PREFERENCIAL)
if [ -n "$DB_HOST" ] && [ -n "$DB_PASSWORD" ]; then
    echo " Configurando PostgreSQL PRODU??O..."
    export DB_CONNECTION=pgsql
    export DB_SSL_MODE=require
    
    # Testa conex?o PostgreSQL
    echo " Testando conex?o PostgreSQL..."
    if php artisan migrate --force --no-interaction; then
        echo " PostgreSQL conectado e funcionando!"
        USING_POSTGRES=true
    else
        echo " Erro no PostgreSQL, usando SQLite como backup..."
        USING_POSTGRES=false
    fi
else
    echo " Vari?veis PostgreSQL n?o encontradas"
    USING_POSTGRES=false
fi

# Se PostgreSQL falhou, usar SQLite
if [ "$USING_POSTGRES" = "false" ]; then
    echo " Configurando SQLite como backup..."
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
    
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    
    php artisan migrate --force --no-interaction
fi

# Seeds SEMPRE (criar usu?rios de teste)
echo " Criando usu?rios de teste..."
php artisan db:seed --force --no-interaction || true

# Cache para performance
echo " Aplicando cache..."
php artisan config:cache 2>/dev/null || true

echo " Aplica??o configurada! Iniciando Apache..."

# Inicia Apache
exec apache2-foreground