#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir referências a ícones
"""

import os
import re

def corrigir_icones():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("Corrigindo referências a ícones...")
    print("=" * 50)
    
    # Páginas que referenciam icon-32.png
    paginas = [
        "entrar.html",
        "dashboard-cliente.html", 
        "app-inicio.html",
        "app-empresas.html",
        "app-perfil.html"
    ]
    
    for pagina in paginas:
        filepath = os.path.join(base_path, pagina)
        if not os.path.exists(filepath):
            print(f"✗ {pagina} (não encontrada)")
            continue
            
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        
        # Substituir img/icon-32.png por favicon-32x32.png
        content = content.replace('img/icon-32.png', 'favicon-32x32.png')
        content = content.replace('"icon-32.png"', '"favicon-32x32.png"')
        content = content.replace("'icon-32.png'", "'favicon-32x32.png'")
        
        # Também corrigir icon-192.png se necessário
        content = content.replace('img/icon-192.png', 'img/icon-192.png')  # Já existe
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✓ {pagina} corrigido")
        else:
            print(f"- {pagina} (sem alterações)")
    
    print("=" * 50)
    print("Concluído!")
    
    # Verificar se favicon-32x32.png existe
    favicon_path = os.path.join(base_path, "favicon-32x32.png")
    if os.path.exists(favicon_path):
        print("✅ favicon-32x32.png existe")
    else:
        print("⚠️  favicon-32x32.png não encontrado")

if __name__ == "__main__":
    corrigir_icones()
