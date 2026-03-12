#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir links quebrados automaticamente
"""

import os
import re

def corrigir_links_arquivo(filepath, base_path):
    """Corrige links quebrados em um arquivo HTML"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    rel_path = os.path.relpath(filepath, base_path)
    dir_path = os.path.dirname(filepath)
    
    # Padrões de links comuns que precisam de correção
    corrections = []
    
    # 1. Corrigir /manifest.json para manifest.json (sem barra inicial)
    if '/manifest.json' in content:
        # Verificar se manifest.json existe no diretório público
        manifest_path = os.path.join(base_path, 'manifest.json')
        if os.path.exists(manifest_path):
            content = content.replace('href="/manifest.json"', 'href="manifest.json"')
            content = content.replace('href=\'/manifest.json\'', 'href=\'manifest.json\'')
            corrections.append('/manifest.json → manifest.json')
    
    # 2. Corrigir /css/vivo-styles.css para css/vivo-styles.css
    if '/css/vivo-styles.css' in content:
        css_path = os.path.join(base_path, 'css', 'vivo-styles.css')
        if os.path.exists(css_path):
            content = content.replace('href="/css/vivo-styles.css"', 'href="css/vivo-styles.css"')
            content = content.replace('href=\'/css/vivo-styles.css\'', 'href=\'css/vivo-styles.css\'')
            corrections.append('/css/vivo-styles.css → css/vivo-styles.css')
    
    # 3. Corrigir /icons/ para icons/ ou img/
    if '/icons/' in content:
        # Verificar se o diretório icons existe
        icons_dir = os.path.join(base_path, 'icons')
        if not os.path.exists(icons_dir):
            # Talvez as imagens estejam em img/
            content = content.replace('/icons/icon-192x192.png', 'img/icon-192.png')
            content = content.replace('/icons/icon-32x32.png', 'img/icon-32.png')
            corrections.append('/icons/ → img/')
    
    # 4. Corrigir img/icon-32.png (verificar se existe)
    if 'img/icon-32.png' in content:
        icon_path = os.path.join(base_path, 'img', 'icon-32.png')
        if not os.path.exists(icon_path):
            # Verificar se existe em outro lugar
            icon_path2 = os.path.join(base_path, 'icon-32.png')
            if os.path.exists(icon_path2):
                content = content.replace('img/icon-32.png', 'icon-32.png')
                corrections.append('img/icon-32.png → icon-32.png')
            else:
                # Criar placeholder
                print(f"  AVISO: icon-32.png não encontrado para {rel_path}")
    
    # 5. Corrigir caminhos absolutos para relativos
    # Padrão: href="/alguma-coisa" sem ser http://
    pattern = r'href=["\']/([^/][^"\']*)["\']'
    def replace_absolute(match):
        path = match.group(1)
        # Verificar se o arquivo existe na raiz
        full_path = os.path.join(base_path, path)
        if os.path.exists(full_path):
            return f'href="{path}"'
        return match.group(0)
    
    content = re.sub(pattern, replace_absolute, content)
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True, corrections
    return False, []

def main():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("Corrigindo links quebrados...")
    print("=" * 50)
    
    # Páginas principais identificadas com problemas
    paginas_com_problemas = [
        "index.html",
        "entrar.html",
        "cadastro.html",
        "dashboard-cliente.html",
        "dashboard-empresa.html",
        "app-inicio.html",
        "app-empresas.html",
        "app-perfil.html"
    ]
    
    total_corrigidas = 0
    total_correcoes = 0
    
    for pagina in paginas_com_problemas:
        filepath = os.path.join(base_path, pagina)
        if os.path.exists(filepath):
            corrigido, correcoes = corrigir_links_arquivo(filepath, base_path)
            if corrigido:
                total_corrigidas += 1
                total_correcoes += len(correcoes)
                print(f"✓ {pagina} corrigido")
                for correcao in correcoes:
                    print(f"  → {correcao}")
            else:
                print(f"- {pagina} (sem alterações)")
        else:
            print(f"✗ {pagina} (não encontrada)")
    
    print("=" * 50)
    print(f"Concluído! {total_corrigidas} arquivos corrigidos, {total_correcoes} correções aplicadas.")
    
    # Também corrigir caracteres em app-perfil.html
    print("\nCorrigindo caracteres em app-perfil.html...")
    app_perfil_path = os.path.join(base_path, "app-perfil.html")
    if os.path.exists(app_perfil_path):
        with open(app_perfil_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if '??' in content:
            content = content.replace('??', '👤')
            with open(app_perfil_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print("✓ app-perfil.html: ?? corrigido para 👤")
        else:
            print("- app-perfil.html: sem caracteres problemáticos")

if __name__ == "__main__":
    main()
