#!/bin/bash

# Script de deploy e seed para Render
# Executa após build do Docker

echo "🚀 Iniciando deploy..."

# Aguardar banco de dados estar pronto
echo "⏳ Aguardando PostgreSQL..."
sleep 5

# Rodar migrations
echo "📦 Executando migrations..."
php artisan migrate --force

# Rodar seeders (importa dados de teste)
echo "🌱 Executando seeders..."
php artisan db:seed --force --class=DatabaseSeeder

echo "✅ Deploy concluído!"

# Iniciar servidor
php artisan serve --host=0.0.0.0 --port=10000
