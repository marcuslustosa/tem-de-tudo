#!/bin/bash

# Script simplificado para corrigir erro 500
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

# Tenta PostgreSQL, se falhar usa SQLite
echo "🗃️ Configurando banco de dados..."
if ! php artisan migrate --force --no-interaction 2>/dev/null; then
    echo "⚠️ PostgreSQL indisponível, usando SQLite..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    
    # Cria .env com SQLite
    cat > /var/www/html/.env << EOF
APP_NAME="Tem de Tudo"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:3cQV4S7tE8m2dR9wQ5lN6pK1jH0uI8yT7rE3wQ9pL5k=
APP_URL=https://app-tem-de-tudo.onrender.com

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_LEVEL=error

JWT_SECRET=t3md3tud0syst3mj4wt53cr3tk3y2024s3cur3h4shk3y
EOF
    
    # Roda migrations com SQLite
    php artisan migrate --force --no-interaction
fi

# Seeds
echo "🌱 Executando seeds..."
php artisan db:seed --force --no-interaction || true

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