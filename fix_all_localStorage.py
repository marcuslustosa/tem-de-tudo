#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import re
from pathlib import Path

# Diretório base
base_dir = Path(r"c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public")

# Padrões a substituir
replacements = [
    # localStorage.setItem
    (r"localStorage\.setItem\('token'", "localStorage.setItem('tem_de_tudo_token'"),
    (r'localStorage\.setItem\("token"', 'localStorage.setItem("tem_de_tudo_token"'),
    (r"localStorage\.setItem\('user'", "localStorage.setItem('tem_de_tudo_user'"),
    (r'localStorage\.setItem\("user"', 'localStorage.setItem("tem_de_tudo_user"'),
    
    # localStorage.getItem
    (r"localStorage\.getItem\('token'\)", "localStorage.getItem('tem_de_tudo_token')"),
    (r'localStorage\.getItem\("token"\)', 'localStorage.getItem("tem_de_tudo_token")'),
    (r"localStorage\.getItem\('user'\)", "localStorage.getItem('tem_de_tudo_user')"),
    (r'localStorage\.getItem\("user"\)', 'localStorage.getItem("tem_de_tudo_user")'),
    
    # localStorage.removeItem
    (r"localStorage\.removeItem\('token'\)", "localStorage.removeItem('tem_de_tudo_token')"),
    (r'localStorage\.removeItem\("token"\)', 'localStorage.removeItem("tem_de_tudo_token")'),
    (r"localStorage\.removeItem\('user'\)", "localStorage.removeItem('tem_de_tudo_user')"),
    (r'localStorage\.removeItem\("user"\)', 'localStorage.removeItem("tem_de_tudo_user")'),
    
    # sessionStorage também
    (r"sessionStorage\.setItem\('token'", "sessionStorage.setItem('tem_de_tudo_token'"),
    (r'sessionStorage\.setItem\("token"', 'sessionStorage.setItem("tem_de_tudo_token"'),
    (r"sessionStorage\.setItem\('user'", "sessionStorage.setItem('tem_de_tudo_user'"),
    (r'sessionStorage\.setItem\("user"', 'sessionStorage.setItem("tem_de_tudo_user"'),
    
    (r"sessionStorage\.getItem\('token'\)", "sessionStorage.getItem('tem_de_tudo_token')"),
    (r'sessionStorage\.getItem\("token"\)', 'sessionStorage.getItem("tem_de_tudo_token")'),
    (r"sessionStorage\.getItem\('user'\)", "sessionStorage.getItem('tem_de_tudo_user')"),
    (r'sessionStorage\.getItem\("user"\)', 'sessionStorage.getItem("tem_de_tudo_user")'),
    
    (r"sessionStorage\.removeItem\('token'\)", "sessionStorage.removeItem('tem_de_tudo_token')"),
    (r'sessionStorage\.removeItem\("token"\)', 'sessionStorage.removeItem("tem_de_tudo_token")'),
    (r"sessionStorage\.removeItem\('user'\)", "sessionStorage.removeItem('tem_de_tudo_user')"),
    (r'sessionStorage\.removeItem\("user"\)', 'sessionStorage.removeItem("tem_de_tudo_user")'),
]

# Encontrar todos os arquivos HTML e JS
files = list(base_dir.glob("**/*.html")) + list(base_dir.glob("**/*.js"))

total_changes = 0
files_changed = []

for file_path in files:
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        file_changes = 0
        
        # Aplicar todas as substituições
        for pattern, replacement in replacements:
            matches = len(re.findall(pattern, content))
            if matches > 0:
                content = re.sub(pattern, replacement, content)
                file_changes += matches
        
        # Salvar se houve mudanças
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            relative_path = file_path.relative_to(base_dir.parent)
            files_changed.append((relative_path, file_changes))
            total_changes += file_changes
            print(f"✓ {relative_path} - {file_changes} mudanças")
    
    except Exception as e:
        print(f"✗ Erro em {file_path}: {e}")

print(f"\n{'='*60}")
print(f"Total: {total_changes} mudanças em {len(files_changed)} arquivos")
print(f"{'='*60}")

if files_changed:
    print("\nArquivos modificados:")
    for path, changes in files_changed:
        print(f"  - {path} ({changes})")
