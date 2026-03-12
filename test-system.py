#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script Python para testar funcionalidades do sistema
"""

import os
import re

def test_system():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("=" * 50)
    print("VERIFICAÇÃO DE FUNCIONALIDADES")
    print("=" * 50)
    print()
    
    # 1. Verificar arquivos JS principais
    print("[1] Verificando arquivos JavaScript principais...")
    js_files = ["js/config.js", "js/auth-manager.js", "js/api-client.js", "js/auth-guard.js"]
    for js in js_files:
        full_path = os.path.join(base_path, js)
        if os.path.exists(full_path):
            print(f"  OK - {js} existe")
        else:
            print(f"  FALTA - {js}")
    
    # 2. Verificar formulários
    print()
    print("[2] Verificando formulários de login...")
    login_pages = ["entrar.html", "login.html", "admin-login.html"]
    for page in login_pages:
        full_path = os.path.join(base_path, page)
        if os.path.exists(full_path):
            with open(full_path, 'r', encoding='utf-8') as f:
                content = f.read()
            if '<form' in content:
                print(f"  OK - {page} tem formulário")
            else:
                print(f"  FALTA - {page} SEM formulário")
    
    # 3. Verificar botões de ação
    print()
    print("[3] Verificando botões de ação...")
    pages_with_buttons = ["entrar.html", "cadastro.html", "app-perfil.html", "app-bonus-aniversario.html"]
    for page in pages_with_buttons:
        full_path = os.path.join(base_path, page)
        if os.path.exists(full_path):
            with open(full_path, 'r', encoding='utf-8') as f:
                content = f.read()
            if 'onclick=' in content:
                print(f"  OK - {page} tem botões com eventos")
            else:
                print(f"  AVISO - {page} pode não ter eventos")
    
    # 4. Verificar referências a APIs
    print()
    print("[4] Verificando referências de API...")
    api_patterns = ["/api/login", "/api/register", "/api/user", "/api/empresas"]
    api_files = ["js/auth.js", "js/auth-manager.js", "js/api-client.js"]
    for file in api_files:
        full_path = os.path.join(base_path, file)
        if os.path.exists(full_path):
            with open(full_path, 'r', encoding='utf-8') as f:
                content = f.read()
            api_count = 0
            for pattern in api_patterns:
                if pattern in content:
                    api_count += 1
            print(f"  OK - {file} tem {api_count} referências de API")
    
    # 5. Verificar CSS
    print()
    print("[5] Verificando CSS...")
    css_files = ["css/vivo-styles.css", "css/vivo-styles-final.css"]
    for css in css_files:
        full_path = os.path.join(base_path, css)
        if os.path.exists(full_path):
            size = os.path.getsize(full_path)
            print(f"  OK - {css} ({size} bytes)")
        else:
            print(f"  FALTA - {css}")
    
    # 6. Verificar arquivos de imagens e manifest
    print()
    print("[6] Verificando recursos...")
    resources = ["manifest.json", "service-worker.js", "sw.js"]
    for res in resources:
        full_path = os.path.join(base_path, res)
        if os.path.exists(full_path):
            print(f"  OK - {res} existe")
        else:
            print(f"  FALTA - {res}")
    
    # 7. Verificar caracteres especiais
    print()
    print("[7] Verificando caracteres especiais...")
    test_pages = ["app-inicio.html", "entrar.html", "dashboard-cliente.html"]
    for page in test_pages:
        full_path = os.path.join(base_path, page)
        if os.path.exists(full_path):
            with open(full_path, 'r', encoding='utf-8') as f:
                content = f.read()
            # Verificar caracteres problemáticos
            if '??' in content:
                print(f"  AVISO - {page} tem emojis quebrados (??)")
            if 'çao' in content or 'çao' in content:
                print(f"  AVISO - {page} tem acentuação incorreta")
            if 'Alimentaçao' in content:
                print(f"  AVISO - {page} tem 'Alimentaçao' (deveria ser 'Alimentação')")
    
    print()
    print("=" * 50)
    print("VERIFICAÇÃO CONCLUÍDA")
    print("=" * 50)

if __name__ == "__main__":
    test_system()
