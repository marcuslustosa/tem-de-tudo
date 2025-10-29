#!/bin/bash

echo "🚀 Iniciando Tem de Tudo..."

# Configura ambiente
export APP_ENV=production
export APP_DEBUG=false

# Cria diretórios essenciais
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/database

# Define permissões
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Limpa caches
echo "🧹 Limpando caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# Gera chave se necessário
echo "🔑 Configurando APP_KEY..."
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force --no-interaction || true
fi

# Verifica se variáveis do PostgreSQL estão definidas
if [ -n "$DB_HOST" ] && [ -n "$DB_PASSWORD" ]; then
    echo "🗃️ Usando PostgreSQL..."
    export DB_CONNECTION=pgsql
    
    # Tenta conectar ao PostgreSQL
    if php artisan migrate --force --no-interaction 2>/dev/null; then
        echo "✅ PostgreSQL conectado com sucesso!"
    else
        echo "❌ Erro no PostgreSQL, usando SQLite como fallback..."
        export DB_CONNECTION=sqlite
        export DB_DATABASE=/var/www/html/database/database.sqlite
        
        touch /var/www/html/database/database.sqlite
        chown www-data:www-data /var/www/html/database/database.sqlite
        chmod 664 /var/www/html/database/database.sqlite
        
        php artisan migrate --force --no-interaction
    fi
else
    echo "🗃️ Usando SQLite (variáveis PostgreSQL não encontradas)..."
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
    
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    
    php artisan migrate --force --no-interaction
fi

# Seeds
echo "🌱 Executando seeds..."
php artisan db:seed --force --no-interaction || true

# Cache final
echo "⚡ Aplicando cache..."
php artisan config:cache || true
php artisan route:cache || true

echo "✅ Aplicação configurada! Iniciando Apache..."

# Inicia Apache
exec apache2-foreground

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="contato@temdetudo.com"
MAIL_FROM_NAME="Tem de Tudo"

SANCTUM_STATEFUL_DOMAINS="tem-de-tudo.onrender.com,localhost"
SESSION_DOMAIN=".temdetudo.com"

VITE_APP_NAME="Tem de Tudo"
EOF
else
    echo "✅ Arquivo .env já existe"
fi

# Criar banco SQLite se não existir para fallback
if [ ! -f /app/database/database.sqlite ]; then
    echo "🗃️ Criando banco SQLite de fallback..."
    touch /app/database/database.sqlite
    chmod 664 /app/database/database.sqlite
fi

# Cache de configuração otimizado
echo "⚡ Otimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Rodar migrations
echo "🔄 Executando migrations..."
php artisan migrate --force || echo "⚠️ Erro nas migrations (pode ser ignorado em primeira execução)"

# Seeder de usuários de demonstração
echo "👥 Criando usuários de demonstração..."
php artisan db:seed --force || echo "⚠️ Erro no seeder (pode ser ignorado)"

# Criar link simbólico para storage
echo "🔗 Criando link do storage..."
php artisan storage:link || echo "⚠️ Erro no storage link (pode ser ignorado)"

# Limpar caches antigos
echo "🧹 Limpando caches..."
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Cache final
echo "💫 Cache final..."
php artisan config:cache
php artisan route:cache

echo "✅ Deploy concluído! Iniciando servidor na porta $PORT"

# Iniciar o servidor
exec php -S 0.0.0.0:$PORT -t public