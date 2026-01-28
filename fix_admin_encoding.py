#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import chardet

files = [
    r"c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public\admin-configuracoes.html",
    r"c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public\admin-relatorios.html",
    r"c:\Users\marcu\OneDrive\Desktop\TDD\tem-de-tudo\backend\public\admin-create-user.html"
]

replacements = [
    ('??', 'çõ'),  # Configura??es → Configurações
    ('?r', 'ór'),  # Relat?rios → Relatórios
    ('?v', 'ív'),  # N?vel → Nível
    ('?s', 'ís'),  # Estat?sticas, Últimos
    ('?n', 'ên'),  # Seguran?a não, mas podemos deixar
    ('?e', 'óe'),  # Per?odo → Período (mas é ?o)
    ('??', 'çã'),  # Alimenta??o → Alimentação
    ('Configura??es', 'Configurações'),
    ('Relat?rios', 'Relatórios'),
    ('Usu?rios', 'Usuários'),
    ('Informa??es', 'Informações'),
    ('Estat?sticas', 'Estatísticas'),
    ('?ltimos', 'Últimos'),
    ('Per?odo', 'Período'),
    ('Alimenta??o', 'Alimentação'),
    ('Servi?os', 'Serviços'),
    ('An?lise', 'Análise'),
    ('?rea', 'Área'),
    ('cria??o', 'criação'),
    ('B?sicas', 'Básicas'),
    ('Tempor?ria', 'Temporária'),
    ('aleat?ria', 'aleatória'),
    ('ap?s', 'após'),
    ('N?vel', 'Nível'),
    ('Permiss?es', 'Permissões'),
    ('permiss?es', 'permissões'),
    ('espec?ficas', 'específicas'),
    ('par?metros', 'parâmetros'),
    ('Seguran?a', 'Segurança'),
    ('Notifica??es', 'Notificações'),
    ('Administra??o', 'Administração'),
]

for filepath in files:
    try:
        # Detect encoding
        with open(filepath, 'rb') as f:
            raw = f.read()
            result = chardet.detect(raw)
            detected_encoding = result['encoding']
            confidence = result['confidence']
        
        print(f"✓ Detected {filepath}")
        print(f"  Encoding: {detected_encoding} (confidence: {confidence:.0%})")
        
        # Read with detected encoding
        with open(filepath, 'r', encoding=detected_encoding) as f:
            content = f.read()
        
        # Apply replacements
        modified = False
        changes = []
        for old, new in replacements:
            if old in content:
                count = content.count(old)
                content = content.replace(old, new)
                modified = True
                changes.append(f"{old} → {new} ({count}x)")
        
        if modified:
            # Write with UTF-8
            with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
                f.write(content)
            print(f"✓ Saved {filepath}")
            for change in changes[:10]:  # Show first 10
                print(f"  - {change}")
            if len(changes) > 10:
                print(f"  ... and {len(changes) - 10} more")
        else:
            print(f"- No changes needed")
        print()
    
    except Exception as e:
        print(f"✗ Error: {e}")
        import traceback
        traceback.print_exc()
        print()

print("Done!")
