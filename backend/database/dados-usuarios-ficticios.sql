-- Usuários de Teste para Tem de Tudo
-- Senha para todos: senha123 (hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)

-- ====================
-- IMPORTANTE: CRIAR TABELAS ANTES
-- ====================

-- CLIENTES (2 usuários)
INSERT INTO usuarios (nome, email, senha, telefone, cpf, tipo, pontos, ativo, created_at, updated_at) VALUES
('Maria Silva', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11987654321', '12345678901', 'cliente', 250, true, NOW(), NOW()),
('João Santos', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11976543210', '98765432109', 'cliente', 150, true, NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- EMPRESAS USUÁRIOS (2 usuários)
INSERT INTO usuarios (nome, email, senha, telefone, tipo, pontos, ativo, created_at, updated_at) VALUES
('Restaurante Sabor & Arte', 'saborearte@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1133334444', 'empresa', 0, true, NOW(), NOW()),
('Pizzaria Bella Napoli', 'bellanapoli@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1122223333', 'empresa', 0, true, NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- ADMINISTRADORES (2 usuários)
INSERT INTO usuarios (nome, email, senha, telefone, tipo, pontos, ativo, created_at, updated_at) VALUES
('Admin Sistema', 'admin@temdetudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1199998888', 'admin', 0, true, NOW(), NOW()),
('Gerente Operacional', 'gerente@temdetudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1188887777', 'admin', 0, true, NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- EMPRESAS CADASTRADAS (6 empresas total)
INSERT INTO empresas (usuario_id, cnpj, nome, categoria, descricao, endereco, latitude, longitude, pontos_checkin, ativo, created_at, updated_at) 
SELECT u.id, '12345678000195', 'Restaurante Sabor & Arte', 'alimentacao', 'Culinária brasileira contemporânea', 'Av. Paulista, 1000 - SP', -23.561414, -46.655881, 10, true, NOW(), NOW()
FROM usuarios u WHERE u.email = 'saborearte@email.com'
ON CONFLICT (cnpj) DO NOTHING;

INSERT INTO empresas (usuario_id, cnpj, nome, categoria, descricao, endereco, latitude, longitude, pontos_checkin, ativo, created_at, updated_at)
SELECT u.id, '98765432000187', 'Pizzaria Bella Napoli', 'alimentacao', 'Pizzas artesanais no forno a lenha', 'Rua Augusta, 500 - SP', -23.555771, -46.662928, 15, true, NOW(), NOW()
FROM usuarios u WHERE u.email = 'bellanapoli@email.com'
ON CONFLICT (cnpj) DO NOTHING;

INSERT INTO empresas (cnpj, nome, categoria, descricao, endereco, latitude, longitude, pontos_checkin, ativo, created_at, updated_at) VALUES
('11222333000144', 'Loja Moda Urbana', 'moda', 'Roupas e acessórios modernos', 'Shopping Center, Loja 201', -23.563987, -46.654321, 8, true, NOW(), NOW()),
('22333444000155', 'Academia Corpo Ativo', 'saude', 'Musculação e aulas coletivas', 'Rua das Flores, 123', -23.567890, -46.651234, 12, true, NOW(), NOW()),
('33444555000166', 'Salão Bella Vista', 'beleza', 'Cortes, coloração e tratamentos', 'Av. Brasil, 789', -23.559876, -46.658765, 10, true, NOW(), NOW()),
('44555666000177', 'Café Aroma & Sabor', 'alimentacao', 'Cafés especiais e doces', 'Praça da República, 45', -23.543210, -46.643218, 5, true, NOW(), NOW())
ON CONFLICT (cnpj) DO NOTHING;

-- PROMOÇÕES (10 promoções variadas)
INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, '20% de Desconto no Almoço', 'Válido segunda a sexta 11h-15h', 50, NOW() + INTERVAL '30 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '12345678000195';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, 'Sobremesa Grátis', 'Na compra de qualquer prato executivo', 30, NOW() + INTERVAL '15 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '12345678000195';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, 'Pizza Grande R$ 29,90', 'Até 2 sabores tradicionais', 80, NOW() + INTERVAL '7 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '98765432000187';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, 'Compre 1 Leve 2 (Terças)', 'Pizza grande', 100, NOW() + INTERVAL '60 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '98765432000187';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, '15% OFF em Toda Loja', 'Qualquer peça do mostruário', 60, NOW() + INTERVAL '20 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '11222333000144';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, '1 Mês Grátis na Matrícula', 'Novos alunos', 150, NOW() + INTERVAL '45 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '22333444000155';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, 'Escova Grátis', 'No corte + coloração', 40, NOW() + INTERVAL '25 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '33444555000166';

INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, validade, ativo, created_at, updated_at)
SELECT e.id, 'Café + Bolo R$ 10', 'Combo até 11h', 20, NOW() + INTERVAL '10 days', true, NOW(), NOW()
FROM empresas e WHERE e.cnpj = '44555666000177';

-- CHECK-INS (Histórico Maria - 4 check-ins = 45 pontos)
INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'maria@email.com'),
    e.id, 
    10, 
    -23.561414, -46.655881, 
    'manual', 
    NOW() - INTERVAL '5 days', 
    NOW() - INTERVAL '5 days'
FROM empresas e WHERE e.cnpj = '12345678000195';

INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'maria@email.com'),
    e.id, 
    15, 
    -23.555771, -46.662928, 
    'qrcode', 
    NOW() - INTERVAL '3 days', 
    NOW() - INTERVAL '3 days'
FROM empresas e WHERE e.cnpj = '98765432000187';

INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'maria@email.com'),
    e.id, 
    8, 
    -23.563987, -46.654321, 
    'manual', 
    NOW() - INTERVAL '2 days', 
    NOW() - INTERVAL '2 days'
FROM empresas e WHERE e.cnpj = '11222333000144';

INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'maria@email.com'),
    e.id, 
    12, 
    -23.567890, -46.651234, 
    'qrcode', 
    NOW() - INTERVAL '1 day', 
    NOW() - INTERVAL '1 day'
FROM empresas e WHERE e.cnpj = '22333444000155';

-- CHECK-INS (Histórico João - 3 check-ins = 35 pontos)
INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'joao@email.com'),
    e.id, 
    10, 
    -23.561414, -46.655881, 
    'qrcode', 
    NOW() - INTERVAL '7 days', 
    NOW() - INTERVAL '7 days'
FROM empresas e WHERE e.cnpj = '12345678000195';

INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'joao@email.com'),
    e.id, 
    15, 
    -23.555771, -46.662928, 
    'manual', 
    NOW() - INTERVAL '4 days', 
    NOW() - INTERVAL '4 days'
FROM empresas e WHERE e.cnpj = '98765432000187';

INSERT INTO check_ins (usuario_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'joao@email.com'),
    e.id, 
    10, 
    -23.559876, -46.658765, 
    'qrcode', 
    NOW() - INTERVAL '2 days', 
    NOW() - INTERVAL '2 days'
FROM empresas e WHERE e.cnpj = '33444555000166';

-- CUPONS (alguns usados, outros disponíveis)
INSERT INTO cupons (usuario_id, promocao_id, codigo, status, data_resgate, validade, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'maria@email.com'),
    p.id,
    'CUPOM-MARIA-001',
    'usado',
    NOW() - INTERVAL '2 days',
    NOW() + INTERVAL '25 days',
    NOW() - INTERVAL '3 days',
    NOW() - INTERVAL '2 days'
FROM promocoes p LIMIT 1;

INSERT INTO cupons (usuario_id, promocao_id, codigo, status, data_resgate, validade, created_at, updated_at)
SELECT 
    (SELECT id FROM usuarios WHERE email = 'joao@email.com'),
    p.id,
    'CUPOM-JOAO-001',
    'disponivel',
    NOW(),
    NOW() + INTERVAL '7 days',
    NOW(),
    NOW()
FROM promocoes p LIMIT 1 OFFSET 2;

-- ====================
-- RESUMO DOS USUÁRIOS
-- ====================
-- CLIENTES:
--   Email: maria@email.com | Senha: senha123 | Pontos: 45
--   Email: joao@email.com  | Senha: senha123 | Pontos: 35
--
-- EMPRESAS:
--   Email: saborearte@email.com  | Senha: senha123
--   Email: bellanapoli@email.com | Senha: senha123
--
-- ADMINS:
--   Email: admin@temdetudo.com   | Senha: senha123
--   Email: gerente@temdetudo.com | Senha: senha123
