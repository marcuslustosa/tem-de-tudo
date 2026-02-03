#!/bin/bash
set -e

echo "========================================="
echo "🚀 TEM DE TUDO - Deploy Render.com"
echo "========================================="

# Debug: mostrar variáveis disponíveis
echo "🔍 DEBUG - Variáveis PostgreSQL:"
echo "PGHOST=$PGHOST"
echo "PGPORT=$PGPORT"
echo "PGDATABASE=$PGDATABASE"
echo "PGUSER=$PGUSER"

# NÃO criar .env - usar apenas variáveis de ambiente
echo "⚠️ Usando APENAS variáveis de ambiente (sem .env)"

# Aguardar PostgreSQL estar pronto
echo "⏳ Aguardando PostgreSQL..."
sleep 10

# Executar migrations
echo "📦 Executando migrations..."
php artisan migrate --force --no-interaction

# Executar seeders SEMPRE (usa updateOrCreate, não duplica)
echo "🌱 Populando banco de dados..."
php artisan db:seed --force --class=DatabaseSeeder --no-interaction

# Limpar TODOS os caches (incluindo os gerados no build)
echo "🧹 Limpando TODOS os caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php

# NÃO gerar config:cache - Laravel vai ler variáveis em runtime
echo "⚠️ Rodando SEM cache de configuração (leitura direta de variáveis)"

echo "✅ Deploy concluído! Iniciando servidor..."
echo "========================================="

# Iniciar Apache
exec apache2-foreground
