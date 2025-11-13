#!/bin/bash
set -e

echo "🚀 === Iniciando Deploy no Render ==="

# Aguardar banco estar disponível
echo "⏳ Aguardando banco de dados..."
for i in {1..30}; do
    if php artisan db:show 2>/dev/null; then
        echo "✅ Banco de dados conectado!"
        break
    fi
    echo "Tentativa $i/30..."
    sleep 2
done

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan route:clear || true

# Criar tabelas de sistema
echo "📝 Criando tabelas de sistema..."
php artisan cache:table 2>/dev/null || true
php artisan session:table 2>/dev/null || true
php artisan queue:table 2>/dev/null || true

# Executar migrações
echo "📊 Executando migrações..."
php artisan migrate --force || {
    echo "⚠️  Erro nas migrações, continuando..."
}

# Seed do banco
echo "🌱 Populando banco de dados..."
php artisan db:seed --force || echo "⚠️  Seed já executado"

# Otimizar para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage link
echo "🔗 Criando link de storage..."
php artisan storage:link || true

# Permissões
echo "🔒 Ajustando permissões..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✨ === Deploy concluído com sucesso! ==="
echo "🌐 Aplicação: https://app-tem-de-tudo.onrender.com"