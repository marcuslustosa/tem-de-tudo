#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Script para corrigir TODAS as cores dos HTMLs para #667eea -> #764ba2"""

import os
import re

BASE_DIR = r"C:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public"

# Mapa de cores ERRADAS -> CORRETAS
COLOR_MAP = {
    '#4c1d95': '#667eea',  # Roxo velho -> Roxo correto
    '#7c3aed': '#764ba2',  # Roxo velho claro -> Roxo correto escuro
    '#10b981': '#667eea',  # Verde -> Roxo
    '#059669': '#764ba2',  # Verde escuro -> Roxo escuro
    '#f59e0b': '#667eea',  # Amarelo -> Roxo
    '#d97706': '#764ba2',  # Amarelo escuro -> Roxo escuro
    '#1f2937': '#667eea',  # Cinza escuro -> Roxo
    '#374151': '#764ba2',  # Cinza escuro 2 -> Roxo escuro
}

# Arquivos a corrigir
FILES = [
    'register-company.html',
    'planos.html',
    'admin-login.html',
    'pontos.html',
    'contato.html',
    'register-company-success.html',
]

def fix_colors(filepath):
    """Substitui cores erradas por corretas"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Substituir cores
        for old_color, new_color in COLOR_MAP.items():
            content = re.sub(old_color, new_color, content, flags=re.IGNORECASE)
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            
            # Contar mudanças
            changes = sum(1 for old, new in COLOR_MAP.items() if old.lower() in original.lower())
            print(f"✅ {os.path.basename(filepath)}: {changes} cores corrigidas")
            return True
        else:
            print(f"⚪ {os.path.basename(filepath)}: Nenhuma mudança necessária")
            return False
            
    except Exception as e:
        print(f"❌ Erro em {os.path.basename(filepath)}: {e}")
        return False

def main():
    print("🎨 Corrigindo cores para #667eea → #764ba2...\n")
    
    fixed = 0
    for filename in FILES:
        filepath = os.path.join(BASE_DIR, filename)
        if os.path.exists(filepath):
            if fix_colors(filepath):
                fixed += 1
        else:
            print(f"⚠️  {filename} não encontrado")
    
    print(f"\n✨ Total: {fixed}/{len(FILES)} arquivos corrigidos!")
    print("🎨 TODAS AS CORES AGORA SÃO: #667eea → #764ba2")

if __name__ == '__main__':
    main()
