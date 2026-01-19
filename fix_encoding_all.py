#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Corrige encoding de TODOS os arquivos HTML do projeto
"""
import os
import glob

def fix_file_encoding(filepath):
    """Corrige encoding de um arquivo"""
    try:
        # Tentar ler com diferentes encodings
        content = None
        for encoding in ['utf-8', 'latin-1', 'cp1252', 'iso-8859-1']:
            try:
                with open(filepath, 'r', encoding=encoding) as f:
                    content = f.read()
                print(f"✓ Lido com {encoding}: {filepath}")
                break
            except:
                continue
        
        if content is None:
            print(f"✗ Não conseguiu ler: {filepath}")
            return False
        
        # Substituições comuns de caracteres quebrados
        replacements = {
            'ser�': 'será',
            'Ser�': 'Será',
            'recupera��o': 'recuperação',
            'Recupera��o': 'Recuperação',
            'implementa��o': 'implementação',
            'Implementa��o': 'Implementação',
            'usu�rio': 'usuário',
            'Usu�rio': 'Usuário',
            'cat�logo': 'catálogo',
            'Cat�logo': 'Catálogo',
            'pr�mios': 'prêmios',
            'Pr�mios': 'Prêmios',
            'hist�rico': 'histórico',
            'Hist�rico': 'Histórico',
            'relat�rio': 'relatório',
            'Relat�rio': 'Relatório',
            'not�cias': 'notícias',
            'Not�cias': 'Notícias',
            'configura��es': 'configurações',
            'Configura��es': 'Configurações',
            'promo��es': 'promoções',
            'Promo��es': 'Promoções',
            'op��es': 'opções',
            'Op��es': 'Opções',
            'an�lise': 'análise',
            'An�lise': 'Análise',
            'v�lido': 'válido',
            'V�lido': 'Válido',
            'c�digo': 'código',
            'C�digo': 'Código',
            'adi��o': 'adição',
            'Adi��o': 'Adição',
            'transa��o': 'transação',
            'Transa��o': 'Transação',
            'cria��o': 'criação',
            'Cria��o': 'Criação',
            'verifica��o': 'verificação',
            'Verifica��o': 'Verificação',
            'N�o': 'Não',
            'n�o': 'não',
            'est�': 'está',
            'Est�': 'Está',
            'ser�o': 'serão',
            'Ser�o': 'Serão',
            'voc�': 'você',
            'Voc�': 'Você',
            '�': 'à',
            '�s': 'às',
            'tamb�m': 'também',
            'Tamb�m': 'Também',
            'at�': 'até',
            'At�': 'Até',
            'dispon�vel': 'disponível',
            'Dispon�vel': 'Disponível',
            'pr�ximo': 'próximo',
            'Pr�ximo': 'Próximo',
            '�ltimo': 'último',
            '�ltimo': 'Último',
            'f�cil': 'fácil',
            'F�cil': 'Fácil',
            'm�s': 'mês',
            'M�s': 'Mês',
            'endere�o': 'endereço',
            'Endere�o': 'Endereço',
        }
        
        changed = False
        for old, new in replacements.items():
            if old in content:
                content = content.replace(old, new)
                changed = True
        
        # Salvar com UTF-8
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        
        if changed:
            print(f"✅ CORRIGIDO: {filepath}")
            return True
        else:
            print(f"  OK (sem mudanças): {filepath}")
            return False
            
    except Exception as e:
        print(f"✗ ERRO em {filepath}: {e}")
        return False

def main():
    print("=" * 60)
    print("CORRIGINDO ENCODING DE TODOS OS ARQUIVOS HTML")
    print("=" * 60)
    
    # Procurar todos os HTML no backend/public
    html_files = glob.glob('backend/public/**/*.html', recursive=True)
    
    print(f"\n📄 Encontrados {len(html_files)} arquivos HTML\n")
    
    fixed_count = 0
    for filepath in sorted(html_files):
        if fix_file_encoding(filepath):
            fixed_count += 1
    
    print("\n" + "=" * 60)
    print(f"✅ CONCLUÍDO! {fixed_count} arquivos corrigidos de {len(html_files)} total")
    print("=" * 60)

if __name__ == '__main__':
    main()
