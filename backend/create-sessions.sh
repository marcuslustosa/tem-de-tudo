#!/bin/bash
set -e

echo "=== SCRIPT DE MIGRATIONS CRÍTICAS ==="
echo "$(date) - Iniciando..."

cd /var/www/html

# Mostrar variáveis de ambiente
echo "Environment Variables:"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "DB_PASSWORD exists: $(if [ -n "$DB_PASSWORD" ]; then echo "yes"; else echo "no"; fi)"

# Testar conexão com banco
echo "Testando conexão com banco..."
php artisan db:monitor || {
    echo "❌ Erro ao conectar com banco"
    php artisan db:show || true
    exit 1
}

# Executar migrations
echo "Executando migrations..."
php artisan migrate --force || {
    echo "❌ Erro ao executar migrations"
    php artisan migrate:status || true
    exit 1
}

# Verificar status das migrations
echo "Status das migrations:"
php artisan migrate:status

# Verificar tabelas
echo "Listando tabelas..."
php artisan db:table sessions || true

echo "Script concluído em $(date)"