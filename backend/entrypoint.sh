#!/bin/bash

# Script de inicialização para Render - Apache
echo "🚀 Iniciando Tem de Tudo no Render..."

# Aguarda banco de dados estar disponível
echo "📡 Aguardando conexão com PostgreSQL..."
sleep 10

# Configura variáveis de ambiente se não estiverem definidas
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}

# Limpa caches do Laravel
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Gera chave da aplicação se não estiver definida
if [ -z "$APP_KEY" ]; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
fi

# Executa migrations
echo "📊 Executando migrations..."
php artisan migrate --force

# Executa seeds para criar usuário admin
echo "👤 Criando usuário administrador..."
php artisan db:seed --force

# Otimiza configurações para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Aplicação pronta! Iniciando Apache..."

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