-- ================================================
-- SCRIPT DE USUÁRIOS DE TESTE - TEM DE TUDO
-- ================================================
-- Este script cria usuários de teste para cada perfil
-- Senha padrão para todos: senha123
-- Hash gerado com: bcrypt('senha123', 10)
-- ================================================

-- 1. ADMIN MASTER
-- Email: admin@temdetudo.com
-- Senha: admin123
INSERT INTO users (name, email, password, perfil, telefone, status, created_at, updated_at, pontos, nivel)
VALUES (
    'Admin Master',
    'admin@temdetudo.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    '(11) 99999-0000',
    'ativo',
    NOW(),
    NOW(),
    0,
    'Master'
) ON CONFLICT (email) DO UPDATE SET
    perfil = 'admin',
    status = 'ativo',
    updated_at = NOW();

-- 2. CLIENTE DE TESTE
-- Email: cliente@teste.com
-- Senha: senha123
INSERT INTO users (name, email, password, perfil, telefone, status, created_at, updated_at, pontos, nivel)
VALUES (
    'Cliente Teste',
    'cliente@teste.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'cliente',
    '(11) 98888-1111',
    'ativo',
    NOW(),
    NOW(),
    465,
    'Ouro'
) ON CONFLICT (email) DO UPDATE SET
    perfil = 'cliente',
    status = 'ativo',
    pontos = 465,
    nivel = 'Ouro',
    updated_at = NOW();

-- 3. EMPRESA DE TESTE
-- Email: empresa@teste.com
-- Senha: senha123
INSERT INTO users (name, email, password, perfil, telefone, status, created_at, updated_at, pontos, nivel)
VALUES (
    'Empresa Teste Ltda',
    'empresa@teste.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'empresa',
    '(11) 97777-2222',
    'ativo',
    NOW(),
    NOW(),
    0,
    'Estabelecimento'
) ON CONFLICT (email) DO UPDATE SET
    perfil = 'empresa',
    status = 'ativo',
    updated_at = NOW();

-- 4. CLIENTES ADICIONAIS PARA DEMONSTRAÇÃO
INSERT INTO users (name, email, password, perfil, telefone, status, created_at, updated_at, pontos, nivel)
VALUES 
    (
        'Maria Santos',
        'maria.santos@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'cliente',
        '(11) 96666-3333',
        'ativo',
        NOW(),
        NOW(),
        2500,
        'Diamante'
    ),
    (
        'João Silva',
        'joao.silva@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'cliente',
        '(11) 95555-4444',
        'ativo',
        NOW(),
        NOW(),
        1850,
        'Platina'
    ),
    (
        'Carlos Mendes',
        'carlos.mendes@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'cliente',
        '(11) 94444-5555',
        'ativo',
        NOW(),
        NOW(),
        1200,
        'Ouro'
    ),
    (
        'Ana Paula',
        'ana.paula@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'cliente',
        '(11) 93333-6666',
        'ativo',
        NOW(),
        NOW(),
        720,
        'Prata'
    ),
    (
        'Fernanda Lima',
        'fernanda.lima@email.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'cliente',
        '(11) 92222-7777',
        'ativo',
        NOW(),
        NOW(),
        320,
        'Bronze'
    )
ON CONFLICT (email) DO NOTHING;

-- ================================================
-- RESUMO DOS ACESSOS CRIADOS
-- ================================================
-- 
-- ADMIN MASTER:
-- URL: /admin-login.html
-- Email: admin@temdetudo.com
-- Senha: admin123
-- Dashboard: /admin.html
--
-- CLIENTE TESTE:
-- URL: /login.html
-- Email: cliente@teste.com
-- Senha: senha123
-- Dashboard: /dashboard-cliente.html
-- Pontos: 465
-- Nível: Ouro
--
-- EMPRESA TESTE:
-- URL: /login.html
-- Email: empresa@teste.com
-- Senha: senha123
-- Dashboard: /dashboard-estabelecimento.html
--
-- CLIENTES DEMONSTRAÇÃO:
-- - maria.santos@email.com (Diamante - 2500 pts)
-- - joao.silva@email.com (Platina - 1850 pts)
-- - carlos.mendes@email.com (Ouro - 1200 pts)
-- - ana.paula@email.com (Prata - 720 pts)
-- - fernanda.lima@email.com (Bronze - 320 pts)
--
-- Todos com senha: senha123
-- ================================================

-- Verificar usuários criados
SELECT 
    id,
    name,
    email,
    perfil,
    pontos,
    nivel,
    status,
    created_at
FROM users
ORDER BY perfil, pontos DESC;
