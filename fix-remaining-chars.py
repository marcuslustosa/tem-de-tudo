#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir caracteres restantes com problemas de encoding
"""

import os
import re

def corrigir_caracteres(content):
    """Corrige caracteres restantes com problemas de encoding"""
    
    # Substituições de caracteres problemáticos
    replacements = [
        # á com problemas
        ('á', 'á'),
        ('à', 'à'),
        ('â', 'â'),
        ('ã', 'ã'),
        # é com problemas
        ('é', 'é'),
        ('ê', 'ê'),
        # í com problemas
        ('í', 'í'),
        ('ì', 'ì'),
        ('î', 'î'),
        # ó com problemas
        ('ó', 'ó'),
        ('ô', 'ô'),
        ('õ', 'õ'),
        # ú com problemas
        ('ú', 'ú'),
        ('ù', 'ù'),
        ('û', 'û'),
        # ç com problemas
        ('ç', 'ç'),
        # Ç com problemas
        ('Ç', 'Ç'),
        # Á
        ('Á', 'Á'),
        # É
        ('É', 'É'),
        # Í
        ('Í', 'Í'),
        # Ó
        ('Ó', 'Ó'),
        # Ú
        ('Ú', 'Ú'),
    ]
    
    # Na verdade, o problema é o inverso - precisamos detectar os caracteres quebrados
    # e substituí-los pelos corretos
    
    # Padrões comuns de caracteres quebrados (UTF-8 mal interpretado como Latin-1)
    broken_patterns = [
        # á aparece como á (já está correto, mas às vezes não)
        ('Ã¡', 'á'),
        ('Ã ', 'à'),
        ('Ã¢', 'â'),
        ('Ã£', 'ã'),
        ('Ã©', 'é'),
        ('Ãª', 'ê'),
        ('Ã­', 'í'),
        ('Ã¬', 'ì'),
        ('Ã®', 'î'),
        ('Ã³', 'ó'),
        ('Ã´', 'ô'),
        ('Ãµ', 'õ'),
        ('Ãº', 'ú'),
        ('Ã¹', 'ù'),
        ('Ã»', 'û'),
        ('Ã§', 'ç'),
        ('Ã', 'Ç'),
        ('Ã', 'Á'),
        ('Ã‰', 'É'),
        ('Ã', 'Í'),
        ('Ã"', 'Ì'),
        ('Ã"', 'Î'),
        ('Ã"', 'Ó'),
        ('Ã"', 'Ô'),
        ('Ã"', 'Õ'),
        ('Ã"', 'Ú'),
        ('Ã"', 'Ù'),
        ('Ã"', 'Û'),
        # Casos especiais
        ('OlÂ£', 'Olá'),
        ('Ol¢', 'Olá'),
        ('OlÂ', 'Olá'),
        ('AÂ§', 'Ações'),
        ('A§', 'Ações'),
        ('Promoes', 'Promoções'),
        ('disponÂ', 'disponíveis'),
        ('nÂ', 'nível'),
        ('VocÂ', 'Você'),
        ('sucÃ©ss', 'sucesso'),
        ('AlimentaÃ§Ã', 'Alimentação'),
        ('AlimentaÃ§', 'Alimentação'),
        ('DiversÃ¡o', 'Diversão'),
        ('Diversáo', 'Diversão'),
        ('SalÃ¡o', 'Salão'),
        ('Saláo', 'Salão'),
        ('PromoÃ§Ãµ', 'Promoções'),
        ('PromoÃ§', 'Promção'),
        ('SÃ¡o', 'São'),
        ('Sáo', 'São'),
        ('localizaÃ§Ã', 'localização'),
        ('localizaÃ§', 'localização'),
        ('localizaáo', 'localização'),
        ('padÃ¡o', 'padrão'),
        ('padráo', 'padrão'),
        ('invÃ¡lido', 'inválido'),
        ('inválido', 'inválido'),
        ('demonstraÃ§Ã', 'demonstração'),
        ('demonstraÃ§', 'demonstração'),
        ('demonstraáo', 'demonstração'),
    ]
    
    for pattern, replacement in broken_patterns:
        content = content.replace(pattern, replacement)
    
    return content

# Arquivos para corrigir
arquivos = [
    "backend/public/app-inicio.html",
    "backend/public/entrar.html",
    "backend/public/dashboard-cliente.html",
    "backend/public/index.html",
    "backend/public/cadastro.html",
]

print("Corrigindo caracteres restantes...")
print("=" * 50)

for arquivo in arquivos:
    if os.path.exists(arquivo):
        try:
            with open(arquivo, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original = content
            content = corrigir_caracteres(content)
            
            if content != original:
                with open(arquivo, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"✓ {arquivo}")
            else:
                print(f"- {arquivo} (sem alterações)")
        except Exception as e:
            print(f"✗ {arquivo}: {e}")
    else:
        print(f"✗ {arquivo} (não encontrado)")

print("=" * 50)
print("Concluído!")
