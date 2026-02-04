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

# Criar acessos de teste automaticamente (PRESERVA dados reais)
echo "🔑 Criando acessos de teste (preservando dados reais)..."
php artisan db:seed --force --class=SafeSeeder

# Rodar seeders extras se existirem
echo "🌱 Executando seeders extras..."
php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || echo "⚠️ DatabaseSeeder não encontrado (normal)"

echo "✅ Deploy concluído!"

# Iniciar servidor
php artisan serve --host=0.0.0.0 --port=10000
