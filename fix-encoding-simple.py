#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script simplificado para corrigir encoding de arquivos HTML problemáticos
"""

import os

# Lista de arquivos que falharam no script anterior
arquivos_problematicos = [
    "backend/public/admin-configuracoes.html",
    "backend/public/admin-empresas.html",
    "backend/public/admin-login.html",
    "backend/public/admin-painel.html",
    "backend/public/admin-promocoes.html",
    "backend/public/admin-relatorios.html",
    "backend/public/admin-usuarios.html",
    "backend/public/app-cartoes.html",
    "backend/public/app-categorias.html",
    "backend/public/app-configuracoes.html",
    "backend/public/app-editar-perfil.html",
    "backend/public/app-empresas.html",
    "backend/public/app-enderecos.html",
    "backend/public/app-favoritos.html",
    "backend/public/app-historico.html",
    "backend/public/app-inicio.html",
    "backend/public/app-meu-qrcode.html",
    "backend/public/app-meus-pontos.html",
    "backend/public/app-notificacoes-config.html",
    "backend/public/app-notificacoes.html",
    "backend/public/app-perfil.html",
    "backend/public/app-pontos-novo.html",
    "backend/public/app-pontos.html",
    "backend/public/app-promocoes-todas.html",
    "backend/public/app-promocoes.html",
    "backend/public/app-qrcode-novo.html",
    "backend/public/app-qrcode.html",
    "backend/public/app.html",
    "backend/public/cadastro.html",
    "backend/public/dashboard-cliente.html",
    "backend/public/demo-dados.html",
    "backend/public/empresa-nova-promocao.html",
    "backend/public/empresa-promocoes.html",
    "backend/public/entrar.html",
    "backend/public/escolher-tipo.html",
    "backend/public/faq.html",
    "backend/public/index.html",
    "backend/public/register-company-success.html",
    "backend/public/register-company.html",
]

def corrigir_arquivo(filepath):
    """Corrige o encoding de um arquivo"""
    try:
        # Tentar ler como latin-1
        with open(filepath, 'r', encoding='latin-1') as f:
            content = f.read()
        
        # Substituições comuns
        content = content.replace('Ã©', 'é')
        content = content.replace('Ã£', 'ã')
        content = content.replace('Ã³', 'ó')
        content = content.replace('Ãº', 'ú')
        content = content.replace('Ã­', 'í')
        content = content.replace('Ã¬', 'ì')
        content = content.replace('Ã²', 'ò')
        content = content.replace('Ã¹', 'ù')
        content = content.replace('Ã¢', 'â')
        content = content.replace('Ãª', 'ê')
        content = content.replace('Ã®', 'î')
        content = content.replace('Ã´', 'ô')
        content = content.replace('Ã»', 'û')
        content = content.replace('Ã§', 'ç')
        content = content.replace('Ã', 'Ç')
        content = content.replace('Ã‰', 'É')
        content = content.replace('Ã', 'Á')
        content = content.replace('Ã"', 'Í')
        content = content.replace('Ã"', 'Ì')
        content = content.replace('Ã"', 'Ò')
        content = content.replace('Ã"', 'Ù')
        content = content.replace('Ã"', 'Â')
        content = content.replace('Ã"', 'Ê')
        content = content.replace('Ã"', 'Î')
        content = content.replace('Ã"', 'Ô')
        content = content.replace('Ã"', 'Û')
        content = content.replace('OlÂ£', 'Olá')
        content = content.replace('Ol¢', 'Olá')
        content = content.replace('OlÂ', 'Olá')
        content = content.replace('AÂ§', 'Ações')
        content = content.replace('A§', 'Ações')
        content = content.replace('Promoes', 'Promoções')
        content = content.replace('disponÂ', 'disponíveis')
        content = content.replace('nÂ', 'nível')
        content = content.replace('VocÂ', 'Você')
        content = content.replace('sucÃ©ss', 'sucesso')
        
        # Salvar como UTF-8
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        
        return True
    except Exception as e:
        print(f"ERRO em {filepath}: {e}")
        return False

# Processar arquivos
print("Corrigindo arquivos com problemas de encoding...")
print("=" * 50)

sucesso = 0
falha = 0

for arquivo in arquivos_problematicos:
    if os.path.exists(arquivo):
        if corrigir_arquivo(arquivo):
            print(f"✓ {arquivo}")
            sucesso += 1
        else:
            print(f"✗ {arquivo}")
            falha += 1
    else:
        print(f"- {arquivo} (não encontrado)")
        falha += 1

print("=" * 50)
print(f"Resultado: {sucesso} corrigidos, {falha} falhas")
