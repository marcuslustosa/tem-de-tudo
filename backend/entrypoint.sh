#!/bin/bash#!/bin/bash



echo "🚀 Iniciando Tem de Tudo..."echo "🚀 Iniciando Tem de Tudo..."



# Configura ambiente# Configura ambiente

export APP_ENV=productionexport APP_ENV=production

export APP_DEBUG=falseexport APP_DEBUG=true

export LOG_LEVEL=debug

# Cria diretórios essenciais

mkdir -p /var/www/html/storage/framework/{sessions,views,cache}# Cria diretórios essenciais

mkdir -p /var/www/html/storage/logsmkdir -p /var/www/html/storage/framework/{sessions,views,cache}

mkdir -p /var/www/html/bootstrap/cachemkdir -p /var/www/html/storage/logs

mkdir -p /var/www/html/databasemkdir -p /var/www/html/bootstrap/cache

mkdir -p /var/www/html/database

# Define permissões

chown -R www-data:www-data /var/www/html/storage# Define permissões

chown -R www-data:www-data /var/www/html/bootstrap/cachechown -R www-data:www-data /var/www/html/storage

chmod -R 775 /var/www/html/storagechown -R www-data:www-data /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/bootstrap/cachechmod -R 775 /var/www/html/storage

chmod -R 775 /var/www/html/bootstrap/cache

# Limpa caches

echo "🧹 Limpando caches..."# Limpa caches ANTES de qualquer coisa

php artisan config:clear 2>/dev/null || trueecho "🧹 Limpando caches..."

php artisan route:clear 2>/dev/null || truephp artisan config:clear 2>/dev/null || true

php artisan view:clear 2>/dev/null || truephp artisan route:clear 2>/dev/null || true

php artisan cache:clear 2>/dev/null || truephp artisan view:clear 2>/dev/null || true

php artisan cache:clear 2>/dev/null || true

# Gera chave se necessário

echo "🔑 Configurando APP_KEY..."# Gera chave se necessário

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; thenecho "🔑 Configurando APP_KEY..."

    php artisan key:generate --force --no-interaction 2>/dev/null || trueif [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then

fi    php artisan key:generate --force --no-interaction 2>/dev/null || true

fi

# PRIMEIRO: Tenta PostgreSQL (PREFERENCIAL)

if [ -n "$DB_HOST" ] && [ -n "$DB_PASSWORD" ]; then# Usar SQLite SEMPRE para evitar problemas PostgreSQL

    echo "🗃️ Configurando PostgreSQL PRODUÇÃO..."echo "🗃️ Usando SQLite para máxima estabilidade..."

    export DB_CONNECTION=pgsqlexport DB_CONNECTION=sqlite

    export DB_SSL_MODE=requireexport DB_DATABASE=/var/www/html/database/database.sqlite

    

    # Testa conexão PostgreSQLtouch /var/www/html/database/database.sqlite

    echo "🔗 Testando conexão PostgreSQL..."chown www-data:www-data /var/www/html/database/database.sqlite

    if php artisan migrate --force --no-interaction; thenchmod 664 /var/www/html/database/database.sqlite

        echo "✅ PostgreSQL conectado e funcionando!"

        USING_POSTGRES=true# Migrations

    elseecho "� Executando migrations..."

        echo "❌ Erro no PostgreSQL, usando SQLite como backup..."php artisan migrate --force --no-interaction 2>/dev/null || true

        USING_POSTGRES=false

    fi# Seeds

elseecho "🌱 Executando seeds..."

    echo "⚠️ Variáveis PostgreSQL não encontradas"php artisan db:seed --force --no-interaction 2>/dev/null || true

    USING_POSTGRES=false

fi# Cache MÍNIMO

echo "⚡ Aplicando cache mínimo..."

# Se PostgreSQL falhou, usar SQLitephp artisan config:cache 2>/dev/null || true

if [ "$USING_POSTGRES" = "false" ]; then

    echo "🗃️ Configurando SQLite como backup..."echo "✅ Aplicação configurada! Iniciando Apache..."

    export DB_CONNECTION=sqlite

    export DB_DATABASE=/var/www/html/database/database.sqlite# Inicia Apache

    exec apache2-foreground

    touch /var/www/html/database/database.sqlite

    chown www-data:www-data /var/www/html/database/database.sqliteMAIL_MAILER=smtp

    chmod 664 /var/www/html/database/database.sqliteMAIL_HOST=mailpit

    MAIL_PORT=1025

    php artisan migrate --force --no-interactionMAIL_USERNAME=null

fiMAIL_PASSWORD=null

MAIL_ENCRYPTION=null

# Seeds SEMPRE (criar usuários de teste)MAIL_FROM_ADDRESS="contato@temdetudo.com"

echo "🌱 Criando usuários de teste..."MAIL_FROM_NAME="Tem de Tudo"

php artisan db:seed --force --no-interaction || true

SANCTUM_STATEFUL_DOMAINS="tem-de-tudo.onrender.com,localhost"

# Cache para performanceSESSION_DOMAIN=".temdetudo.com"

echo "⚡ Aplicando cache..."

php artisan config:cache 2>/dev/null || trueVITE_APP_NAME="Tem de Tudo"

EOF

echo "✅ Aplicação configurada! Iniciando Apache..."else

    echo "✅ Arquivo .env já existe"

# Inicia Apachefi

exec apache2-foreground
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