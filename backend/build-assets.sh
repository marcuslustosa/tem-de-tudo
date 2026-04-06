#!/bin/bash
# Script de build de assets para produção
# Uso: bash build-assets.sh

echo "🔨 Iniciando build de assets..."

# Criar diretório dist
mkdir -p public/dist

# Minificar stitch-app.js usando terser (precisa instalar: npm i -g terser)
if command -v terser &> /dev/null; then
    echo "📦 Minificando stitch-app.js..."
    terser public/js/stitch-app.js \
        --compress \
        --mangle \
        --output public/dist/stitch-app.min.js \
        --comments false
    
    # Calcular tamanhos
    original_size=$(wc -c < public/js/stitch-app.js)
    minified_size=$(wc -c < public/dist/stitch-app.min.js)
    reduction=$(echo "scale=2; (1 - $minified_size / $original_size) * 100" | bc)
    
    echo "✅ Minificado: $original_size bytes → $minified_size bytes (${reduction}% redução)"
else
    echo "⚠️  terser não encontrado. Instalando..."
    npm install -g terser
    bash build-assets.sh
    exit 0
fi

echo "✅ Build concluído! Use /dist/stitch-app.min.js em produção"
