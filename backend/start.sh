#!/bin/bash
set -e

echo "🚀 Iniciando aplicação no Render..."

# Garantir que .env existe
if [ ! -f .env ]; then
    echo "📝 Criando .env a partir de .env.render..."
    cp .env.render .env
fi

# Mostrar configuração de banco (debug)
echo "🔍 Verificando configuração do banco:"
echo "DB_HOST: $DB_HOST"
echo "DB_DATABASE: $DB_DATABASE"

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear

# Executar migrations
echo "🗄️ Executando migrations..."
php artisan migrate --force || echo "⚠️ Migrations falharam, continuando..."

# Iniciar servidor
echo "✅ Iniciando servidor na porta ${PORT:-8080}..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
