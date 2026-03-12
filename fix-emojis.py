#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para corrigir emojis quebrados (??) nos arquivos HTML
"""

import os
import re

def fix_emojis_in_file(filepath):
    """Corrige emojis quebrados em um arquivo"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original = content
    
    # Mapeamento de emojis quebrados para emojis corretos
    emoji_map = {
        '??': '👋',  # mão acenando
        '??': '💰',  # saco de dinheiro
        '??': '📍',  # alfinete de localização
        '??': '🎉',  # festa
        '??': '⭐',  # estrela
        '??': '📱',  # celular
        '??': '🎁',  # presente
        '??': '🏆',  # troféu
        '??': '💎',  # diamante
        '??': '🔥',  # fogo
        '??': '⚡',  # raio
        '??': '🎯',  # alvo
        '??': '💡',  # lâmpada
        '??': '📲',  # celular com seta
        '??': '👤',  # silhueta
        '??': '🔒',  # cadeado fechado
        '??': '🔓',  # cadeado aberto
        '??': '❌',  # X
        '??': '✅',  # check
        '??': '⚠️',  # alerta
        '??': '📢',  # alto-falante
        '??': '🔔',  # sino
        '??': '📋',  # prancheta
        '??': '🕐',  # relógio
        '??': '📊',  # gráfico
        '??': '📈',  # gráfico subindo
        '??': '📉',  # gráfico descendo
        '??': '💳',  # cartão
        '??': '🏷️',  # etiqueta
        '??': '📌',  # pino
        '??': '❤️',  # coração
        '??': '💜',  # coração roxo
        '??': '🟣',  # círculo roxo
    }
    
    # Substituir emojis quebrados
    for broken, emoji in emoji_map.items():
        content = content.replace(broken, emoji)
    
    # Corrigir também acentuação comum
    content = content.replace('Alimentaçao', 'Alimentação')
    content = content.replace('Alimentação', 'Alimentação')
    content = content.replace('localizaçao', 'localização')
    content = content.replace('localização', 'localização')
    content = content.replace('Promoçao', 'Promoção')
    content = content.replace('Promoção', 'Promoção')
    content = content.replace('descriçao', 'descrição')
    content = content.replace('descrição', 'descrição')
    content = content.replace('informaçoes', 'informações')
    content = content.replace('informações', 'informações')
    content = content.replace('açãO', 'ação')
    content = content.replace('AÇÃ', 'AÇÃO')
    content = content.replace('Demonstraç', 'Demonstração')
    content = content.replace('demonstraç', 'demonstração')
    content = content.replace('MODO DEMONSTRAÇ', 'MODO DEMONSTRAÇÃO')
    content = content.replace('DEMONSTRAÇ', 'DEMONSTRAÇÃO')
    
    if content != original:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

def main():
    base_path = "c:/Users/marcu/OneDrive/Desktop/TDD/backend/public"
    
    print("Corrigindo emojis quebrados...")
    print("=" * 50)
    
    # Arquivos para corrigir
    files_to_fix = [
        "app-inicio.html",
        "entrar.html",
        "dashboard-cliente.html",
        "app-empresas.html",
        "app-bonus-adesao.html",
        "app-bonus-aniversario.html",
        "app-scanner.html",
        "app-meu-qrcode.html",
    ]
    
    fixed_count = 0
    for filename in files_to_fix:
        filepath = os.path.join(base_path, filename)
        if os.path.exists(filepath):
            if fix_emojis_in_file(filepath):
                print(f"✓ {filename} corrigido")
                fixed_count += 1
            else:
                print(f"- {filename} (sem alterações)")
        else:
            print(f"✗ {filename} (não encontrado)")
    
    print("=" * 50)
    print(f"Concluído! {fixed_count} arquivos corrigidos.")

if __name__ == "__main__":
    main()
