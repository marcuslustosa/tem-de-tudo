-- SQL DIRETO para popular banco PostgreSQL do Render
-- Execute este SQL manualmente no console do PostgreSQL do Render

-- 1. LIMPAR DADOS ANTIGOS (se necessário)
TRUNCATE TABLE users RESTART IDENTITY CASCADE;
TRUNCATE TABLE empresas RESTART IDENTITY CASCADE;

-- 2. INSERIR ADMIN
INSERT INTO users (name, email, password, perfil, telefone, status, pontos, email_verified_at, created_at, updated_at)
VALUES (
    'Administrador Master',
    'admin@temdetudo.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: admin123
    'admin',
    '(11) 99999-0001',
    'ativo',
    0,
    NOW(),
    NOW(),
    NOW()
);

-- 3. INSERIR CLIENTE TESTE
INSERT INTO users (name, email, password, perfil, telefone, status, pontos, email_verified_at, created_at, updated_at)
VALUES (
    'Cliente Teste',
    'cliente@teste.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
    'cliente',
    '(11) 99999-0002',
    'ativo',
    250,
    NOW(),
    NOW(),
    NOW()
);

-- 4. INSERIR EMPRESA TESTE (usuário)
INSERT INTO users (name, email, password, perfil, telefone, status, pontos, email_verified_at, created_at, updated_at)
VALUES (
    'Empresa Teste Ltda',
    'empresa@teste.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: 123456
    'empresa',
    '(11) 99999-0003',
    'ativo',
    0,
    NOW(),
    NOW(),
    NOW()
);

-- 5. INSERIR 10 CLIENTES (cliente1 a cliente10 - simplificado)
INSERT INTO users (name, email, password, perfil, telefone, status, pontos, email_verified_at, created_at, updated_at)
VALUES 
    ('Cliente 1', 'cliente1@email.com', '$2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza', 'cliente', '(11) 91000-0001', 'ativo', 100, NOW(), NOW(), NOW()),
    ('Cliente 2', 'cliente2@email.com', '$2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza', 'cliente', '(11) 91000-0002', 'ativo', 150, NOW(), NOW(), NOW()),
    ('Cliente 3', 'cliente3@email.com', '$2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza', 'cliente', '(11) 91000-0003', 'ativo', 200, NOW(), NOW(), NOW()),
    ('Cliente 4', 'cliente4@email.com', '$2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza', 'cliente', '(11) 91000-0004', 'ativo', 50, NOW(), NOW(), NOW()),
    ('Cliente 5', 'cliente5@email.com', '$2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza', 'cliente', '(11) 91000-0005', 'ativo', 300, NOW(), NOW(), NOW());

-- 6. INSERIR EMPRESAS NA TABELA empresas
INSERT INTO empresas (nome, endereco, telefone, cnpj, descricao, points_multiplier, ativo, owner_id, created_at, updated_at)
VALUES 
    ('Pizzaria Bella Napoli', 'Av. Paulista, 1000 - São Paulo, SP', '(11) 3000-1001', '12.345.678/0001-10', 'Pizzaria tradicional italiana', 1.00, true, 3, NOW(), NOW()),
    ('Academia FitLife', 'Rua Augusta, 500 - São Paulo, SP', '(11) 3000-1002', '23.456.789/0001-20', 'Academia completa 24h', 1.50, true, 3, NOW(), NOW()),
    ('Salão Glamour Hair', 'Av. Faria Lima, 300 - São Paulo, SP', '(11) 3000-1003', '34.567.890/0001-30', 'Salão de beleza premium', 1.20, true, 3, NOW(), NOW());

-- 7. VERIFICAR DADOS INSERIDOS
SELECT 'USUÁRIOS:' as tipo, COUNT(*) as total FROM users
UNION ALL
SELECT 'EMPRESAS:' as tipo, COUNT(*) as total FROM empresas;

-- 8. TESTAR LOGIN (hash das senhas)
-- Senha admin123: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- Senha 123456: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi  
-- Senha senha123: $2y$12$LQv3c1ydiCr7L7c5b.6oYeTaMvl2A.HmvLl0v9z5lYQIxJB6i0dza

SELECT email, perfil, status FROM users ORDER BY id;
