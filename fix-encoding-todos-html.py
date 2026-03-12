#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir encoding de todos os arquivos HTML do projeto
"""

import os
import codecs

def fix_encoding_file(filepath):
    """Corrige o encoding de um arquivo HTML"""
    try:
        # Tentar ler como UTF-8 primeiro
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Se já está em UTF-8, só precisa salvar com BOM
        # Procurar por caracteres problemáticos
        problem_chars = ['Ol\xa0', '\xa0', '\xbd', '\x92', '\x93', '\x94']
        has_problems = any(char in content for char in problem_chars)
        
        if has_problems:
            # Tentar detectar a codificação correta
            try:
                with open(filepath, 'rb') as f:
                    raw = f.read()
                
                # Tentar latin-1
                content = raw.decode('latin-1')
            except:
                pass
        
        # Salvar como UTF-8 com BOM (para garantir compatibilidade)
        with open(filepath, 'w', encoding='utf-8-sig') as f:
            f.write(content)
        
        print(f"✓ {filepath}")
        return True
        
    except Exception as e:
        print(f"✗ {filepath}: {e}")
        return False

def main():
    """Main function"""
    html_dir = "backend/public"
    
    # Lista de arquivos importantes para corrigir
    important_files = [
        "dashboard-cliente.html",
        "entrar.html",
        "index.html",
        "login.html",
        "cadastro.html",
    ]
    
    # Contador
    fixed = 0
    total = 0
    
    # Processar todos os arquivos HTML
    for root, dirs, files in os.walk(html_dir):
        for file in files:
            if file.endswith('.html'):
                filepath = os.path.join(root, file)
                total += 1
                if fix_encoding_file(filepath):
                    fixed += 1
    
    print(f"\n=== RESULTADO ===")
    print(f"Arquivos processados: {total}")
    print(f"Arquivos corrigidos: {fixed}")

if __name__ == "__main__":
    main()
