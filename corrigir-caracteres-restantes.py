#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir caracteres restantes problemáticos
"""

import os
import re

def corrigir_caracteres_arquivo(filepath):
    """Corrige caracteres problemáticos em um arquivo"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # Correções específicas encontradas
    correcoes = [
        ('DemonstraçãoãoáO', 'DEMONSTRAÇÃO'),
        ('MODO DEMONSTRAÇÃOãoáO ATIVADO', 'MODO DEMONSTRAÇÃO ATIVADO'),
        ('Alimentaçáo', 'Alimentação'),
        ('localizaçáo', 'localização'),
        ('Promoçáo', 'Promoção'),
        ('descriçáo', 'descrição'),
        ('informaçoes', 'informações'),
        ('açãO', 'ação'),
        ('AÇÃ', 'AÇÃO'),
        ('? Caf? Supremo', 'Café Supremo'),
        ('Caf? Supremo', 'Café Supremo'),
        ('SalÃ¡o', 'Salão'),
        ('Saláo', 'Salão'),
        ('funciona', 'funciona'),
        ('funcion', 'função'),
        ('Funç', 'Função'),
        ('verificaç', 'verificação'),
        ('aplicaç', 'aplicação'),
        ('aniversário', 'aniversário'),
        ('atenção', 'atenção'),
        ('você', 'você'),
        ('? Caf? Supremo', 'Café Supremo'),
        ('çáo', 'ção'),
        ('áO', 'ão'),
    ]
    
    for errado, correto in correcoes:
        content = content.replace(errado, correto)
    
    # Corrigir emojis duplicados
    content = content.replace('🟣?', '🟣')
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

def main():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("Corrigindo caracteres restantes...")
    print("=" * 50)
    
    # Arquivos com problemas identificados
    arquivos = [
        "app-inicio.html",
        "app-categorias.html",
        "app-empresas.html",
        "entrar.html",
        "cadastro.html"
    ]
    
    corrigidos = 0
    for arquivo in arquivos:
        filepath = os.path.join(base_path, arquivo)
        if os.path.exists(filepath):
            if corrigir_caracteres_arquivo(filepath):
                print(f"✓ {arquivo} corrigido")
                corrigidos += 1
            else:
                print(f"- {arquivo} (sem alterações)")
        else:
            print(f"✗ {arquivo} (não encontrado)")
    
    print("=" * 50)
    print(f"Concluído! {corrigidos} arquivos corrigidos.")

if __name__ == "__main__":
    main()
