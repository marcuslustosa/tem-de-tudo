#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

def fix_file_encoding(filepath):
    """Fix encoding issues in HTML files"""
    try:
        # Read file with errors='ignore'
        with open(filepath, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
        
        # Replace common broken characters
        replacements = {
            '�': '',  # Generic replacement character
            'Usu�rio': 'Usuário',
            'n�o': 'não',
            'j�': 'já',
            '��������': '••••••••',
            'b�nus': 'bônus',
            'Sele��o': 'Seleção',
            'Espec�ficos': 'Específicos',
            'Endere�o': 'Endereço',
            'n�mero': 'número',
            'M�nimo': 'Mínimo',
            'pol�tica': 'política',
            'in�cio': 'início',
            'Valida��es': 'Validações',
            'b�sicas': 'básicas',
            'coincidem': 'coincidem',
            'obrigat�rios': 'obrigatórios',
            'b�sico': 'básico',
            'inv�lida': 'inválida',
            'usu�rio': 'usuário',
            'confirma��o': 'confirmação',
            'conex�o': 'conexão',
            'funcion�rios': 'funcionários',
            'necess�rio': 'necessário',
            'selecionado': 'selecionado',
            'm�s': 'mês',
            'At�': 'Até',
            'Relat�rios': 'Relatórios',
            'avan�ados': 'avançados',
            'priorit�rio': 'prioritário',
            'Integra��o': 'Integração',
            'M�ltiplas': 'Múltiplas',
            'dispon�vel': 'disponível',
            'informa��es': 'informações',
            'Raz�o': 'Razão',
            'Inscri��o': 'Inscrição',
            'Descri��o': 'Descrição',
            'Neg�cio': 'Negócio',
            'servi�os': 'serviços',
            'N�mero': 'Número',
            'Cart�o': 'Cartão',
            'Cr�dito': 'Crédito',
            'autom�tico': 'automático',
            'Banc�rio': 'Bancário',
            '�teis': 'úteis',
            'Ap�s': 'Após',
            'receber�': 'receberá',
            'Pr�ximos': 'Próximos',
            'ativa��o': 'ativação',
            'm�todo': 'método',
            'est�': 'está',
            'cadastrado': 'cadastrado',
            'obrigat�rio': 'obrigatório',
            'v�lido': 'válido',
            'confere': 'confere',
            'copi�vel': 'copiável',
            'bot�o': 'botão',
            '�s': 'às',
            'neg�cios': 'negócios',
        }
        
        for old, new in replacements.items():
            content = content.replace(old, new)
        
        # Write back with UTF-8
        with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
            f.write(content)
        
        print(f"✓ Fixed: {filepath}")
        return True
    except Exception as e:
        print(f"✗ Error fixing {filepath}: {e}")
        return False

# Fix all HTML files
public_dir = r'c:\Users\X472795\Desktop\Projetos\tem-de-tudo\backend\public'

files_to_fix = [
    'login.html',
    'register.html',
    'register-company.html',
]

print("Starting encoding fixes...")
for filename in files_to_fix:
    filepath = os.path.join(public_dir, filename)
    if os.path.exists(filepath):
        fix_file_encoding(filepath)
    else:
        print(f"✗ File not found: {filepath}")

print("\nDone!")
