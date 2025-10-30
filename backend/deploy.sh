#!/bin/bash
set -e

echo "=== Iniciando Deploy no Render ==="

# 1. Limpar caches
echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 2. Otimizar autoloader
echo "Otimizando autoloader..."
composer dump-autoload --optimize --no-dev

# 3. Configurar Banco de Dados
echo "Configurando banco de dados..."
php artisan migrate:status || {
    echo "❌ Erro ao verificar status das migrations"
    exit 1
}

# 4. Executar Migrations
echo "Executando migrations..."
php artisan migrate --force || {
    echo "❌ Erro ao executar migrations"
    exit 1
}

# 5. Verificar Tabelas
echo "Verificando tabelas..."
php artisan db:monitor || {
    echo "❌ Erro ao verificar banco de dados"
    exit 1
}

# 6. Otimizar Laravel
echo "Otimizando Laravel..."
php artisan optimize
php artisan route:cache
php artisan config:cache

echo "✓ Deploy concluído com sucesso!"