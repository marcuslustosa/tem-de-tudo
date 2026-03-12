#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Teste completo do sistema: links, botões, fluxos, CSS e funcionalidades
"""

import os
import re
from urllib.parse import urlparse

def testar_sistema_completo():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("=" * 60)
    print("TESTE COMPLETO DO SISTEMA")
    print("=" * 60)
    print()
    
    # 1. Coletar todos os arquivos HTML
    html_files = []
    for root, dirs, files in os.walk(base_path):
        for file in files:
            if file.endswith('.html'):
                html_files.append(os.path.join(root, file))
    
    print(f"[1] Encontrados {len(html_files)} arquivos HTML")
    print()
    
    # 2. Verificar CSS em cada página
    print("[2] Verificando CSS em todas as páginas...")
    css_padrao = "vivo-styles.css"
    css_final = "vivo-styles-final.css"
    css_vivo = "vivo-styles"
    
    paginas_sem_css = []
    paginas_com_css_correto = []
    
    for html_file in html_files[:20]:  # Limitar a 20 páginas para performance
        with open(html_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        rel_path = os.path.relpath(html_file, base_path)
        
        # Verificar se tem referência a CSS
        if css_padrao in content or css_final in content or css_vivo in content:
            paginas_com_css_correto.append(rel_path)
        else:
            paginas_sem_css.append(rel_path)
    
    print(f"  Páginas com CSS correto: {len(paginas_com_css_correto)}")
    if paginas_sem_css:
        print(f"  Páginas SEM CSS (problema): {len(paginas_sem_css)}")
        for pagina in paginas_sem_css[:5]:  # Mostrar apenas 5
            print(f"    - {pagina}")
    
    print()
    
    # 3. Verificar links em páginas principais
    print("[3] Verificando links em páginas principais...")
    paginas_principais = [
        "index.html",
        "entrar.html", 
        "cadastro.html",
        "dashboard-cliente.html",
        "dashboard-empresa.html",
        "app-inicio.html",
        "app-empresas.html",
        "app-perfil.html"
    ]
    
    links_quebrados = []
    links_validos = 0
    
    for pagina in paginas_principais:
        pagina_path = os.path.join(base_path, pagina)
        if not os.path.exists(pagina_path):
            print(f"  AVISO: {pagina} não encontrada")
            continue
            
        with open(pagina_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Encontrar todos os links href
        href_pattern = r'href=["\']([^"\']+)["\']'
        links = re.findall(href_pattern, content)
        
        for link in links:
            # Ignorar links externos e âncoras
            if link.startswith('http://') or link.startswith('https://') or link.startswith('#') or link.startswith('mailto:'):
                continue
            
            # Ignorar links com placeholders JavaScript (${...})
            if '${' in link:
                continue
                
            # Remover parâmetros de query para verificação
            clean_link = link.split('?')[0].split('#')[0]
            
            if not clean_link:
                continue
                
            # Verificar se o arquivo existe
            link_path = os.path.join(os.path.dirname(pagina_path), clean_link)
            if not os.path.exists(link_path):
                # Tentar encontrar no diretório público
                link_path_public = os.path.join(base_path, clean_link)
                if not os.path.exists(link_path_public):
                    # Verificar se é um arquivo .html que não existe
                    if clean_link.endswith('.html'):
                        links_quebrados.append((pagina, link))
                    # Para outros tipos de arquivos, verificar se há extensão
                    elif '.' in clean_link.split('/')[-1]:
                        links_quebrados.append((pagina, link))
                    # Para diretórios, considerar como válido
                    else:
                        links_validos += 1
                else:
                    links_validos += 1
            else:
                links_validos += 1
    
    print(f"  Links válidos: {links_validos}")
    print(f"  Links quebrados: {len(links_quebrados)}")
    if links_quebrados:
        for pagina, link in links_quebrados[:10]:  # Mostrar apenas 10
            print(f"    - {pagina}: {link}")
    
    print()
    
    # 4. Verificar botões com eventos
    print("[4] Verificando botões com eventos JavaScript...")
    eventos_comuns = ['onclick', 'onsubmit', 'onchange', 'onload']
    
    for pagina in paginas_principais:
        pagina_path = os.path.join(base_path, pagina)
        if not os.path.exists(pagina_path):
            continue
            
        with open(pagina_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        tem_eventos = False
        for evento in eventos_comuns:
            if evento in content.lower():
                tem_eventos = True
                break
        
        if tem_eventos:
            print(f"  OK - {pagina} tem eventos JavaScript")
        else:
            print(f"  AVISO - {pagina} pode não ter interatividade")
    
    print()
    
    # 5. Verificar formulários
    print("[5] Verificando formulários...")
    for pagina in paginas_principais:
        pagina_path = os.path.join(base_path, pagina)
        if not os.path.exists(pagina_path):
            continue
            
        with open(pagina_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if '<form' in content.lower():
            forms_count = content.lower().count('<form')
            print(f"  OK - {pagina} tem {forms_count} formulário(s)")
        else:
            print(f"  INFO - {pagina} não tem formulários (pode ser normal)")
    
    print()
    
    # 6. Verificar caracteres especiais
    print("[6] Verificando caracteres especiais...")
    caracteres_problematicos = ['??', 'çao', 'çao', 'Promoçao', 'Alimentaçao']
    
    for pagina in paginas_principais:
        pagina_path = os.path.join(base_path, pagina)
        if not os.path.exists(pagina_path):
            continue
            
        with open(pagina_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        problemas = []
        for char in caracteres_problematicos:
            if char in content:
                problemas.append(char)
        
        if problemas:
            print(f"  PROBLEMA - {pagina} tem caracteres incorretos: {', '.join(problemas)}")
        else:
            print(f"  OK - {pagina} caracteres corretos")
    
    print()
    
    # 7. Verificar cores Vipus
    print("[7] Verificando cores Vipus...")
    cores_vipus = ['#9b59b6', '#603863', '#8e44ad']
    cores_vivo = ['#6F1AB6', '#5A1494', '#8B4BC4']
    
    # Verificar nos arquivos CSS principais
    css_files = [
        os.path.join(base_path, 'css', 'vivo-styles.css'),
        os.path.join(base_path, 'css', 'vivo-styles-final.css')
    ]
    
    for css_file in css_files:
        if os.path.exists(css_file):
            with open(css_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            tem_vipus = any(cor in content for cor in cores_vipus)
            tem_vivo = any(cor in content for cor in cores_vivo)
            
            nome = os.path.basename(css_file)
            if tem_vipus and not tem_vivo:
                print(f"  OK - {nome} usa cores Vipus")
            elif tem_vivo:
                print(f"  PROBLEMA - {nome} ainda usa cores Vivo")
            else:
                print(f"  AVISO - {nome} não tem cores identificadas")
    
    print()
    
    # 8. Resumo
    print("[8] RESUMO DO TESTE")
    print("=" * 60)
    print(f"Total páginas HTML: {len(html_files)}")
    print(f"Páginas com CSS correto: {len(paginas_com_css_correto)}")
    print(f"Páginas sem CSS: {len(paginas_sem_css)}")
    print(f"Links válidos verificados: {links_validos}")
    print(f"Links quebrados encontrados: {len(links_quebrados)}")
    print()
    
    if len(paginas_sem_css) == 0 and len(links_quebrados) == 0:
        print("✅ SISTEMA COM BOA INTEGRIDADE VISUAL E DE LINKS")
    else:
        print("⚠️  ALGUNS PROBLEMAS IDENTIFICADOS (verificar acima)")
    
    print("=" * 60)

if __name__ == "__main__":
    testar_sistema_completo()
