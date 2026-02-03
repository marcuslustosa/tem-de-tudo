-- ============================================
-- SCRIPT DE DADOS FICTÍCIOS - TEM DE TUDO
-- ============================================
-- Popular banco com dados realistas para demonstração
-- Inclui: Clientes, Empresas, Admin, Promoções, Check-ins
-- ============================================

-- Limpar dados existentes (exceto estrutura)
TRUNCATE TABLE pontos_cliente;
TRUNCATE TABLE check_ins;
TRUNCATE TABLE promocoes;
TRUNCATE TABLE empresas CASCADE;
TRUNCATE TABLE usuarios CASCADE;

-- ============================================
-- 1. USUÁRIOS CLIENTES (10 clientes fictícios)
-- ============================================

INSERT INTO usuarios (nome, email, senha, cpf, telefone, data_nascimento, perfil, ativo, created_at) VALUES
('João Silva Santos', 'joao.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123.456.789-00', '(11) 98765-4321', '1990-05-15', 'cliente', true, NOW()),
('Maria Oliveira Costa', 'maria.oliveira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '234.567.890-11', '(11) 98765-4322', '1985-08-20', 'cliente', true, NOW()),
('Pedro Henrique Lima', 'pedro.lima@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '345.678.901-22', '(11) 98765-4323', '1995-03-10', 'cliente', true, NOW()),
('Ana Carolina Souza', 'ana.souza@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456.789.012-33', '(11) 98765-4324', '1992-11-25', 'cliente', true, NOW()),
('Lucas Fernando Alves', 'lucas.alves@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '567.890.123-44', '(11) 98765-4325', '1988-07-30', 'cliente', true, NOW()),
('Juliana Martins Pereira', 'juliana.martins@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '678.901.234-55', '(11) 98765-4326', '1993-12-05', 'cliente', true, NOW()),
('Rafael dos Santos', 'rafael.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789.012.345-66', '(11) 98765-4327', '1991-04-18', 'cliente', true, NOW()),
('Fernanda Rodrigues', 'fernanda.rodrigues@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '890.123.456-77', '(11) 98765-4328', '1987-09-22', 'cliente', true, NOW()),
('Carlos Eduardo Nunes', 'carlos.nunes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '901.234.567-88', '(11) 98765-4329', '1994-02-14', 'cliente', true, NOW()),
('Camila Beatriz Freitas', 'camila.freitas@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '012.345.678-99', '(11) 98765-4330', '1989-06-08', 'cliente', true, NOW());

-- ============================================
-- 2. EMPRESAS (10 empresas diversas)
-- ============================================

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Sabor e Arte Restaurante LTDA',
    'Restaurante Sabor & Arte',
    '12.345.678/0001-01',
    'alimentacao',
    'Culinária brasileira contemporânea com ingredientes frescos e selecionados. Ambiente aconchegante perfeito para almoços executivos e jantares românticos.',
    'Rua das Flores, 123',
    'São Paulo',
    'SP',
    '01234-567',
    '(11) 3456-7890',
    '(11) 98765-4321',
    '@saborarte',
    '/saborartesp',
    'www.saborarte.com.br',
    'Seg-Sex: 11h-15h, 18h-23h | Sáb-Dom: 11h-23h',
    10,
    true,
    'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400',
    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800',
    NOW()
FROM usuarios WHERE email = 'joao.silva@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Pizzaria Bella Napoli LTDA',
    'Pizzaria Bella Napoli',
    '23.456.789/0001-02',
    'alimentacao',
    'Pizzas artesanais autênticas preparadas em forno a lenha importado da Itália. Massa fermentada naturalmente por 48h.',
    'Avenida Paulista, 456',
    'São Paulo',
    'SP',
    '01310-100',
    '(11) 3456-7891',
    '(11) 98765-4322',
    '@bellanapoli',
    '/pizzariabellanapoli',
    'www.bellanapoli.com.br',
    'Ter-Dom: 18h-23h | Fecha Segunda',
    15,
    true,
    'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400',
    'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800',
    NOW()
FROM usuarios WHERE email = 'maria.oliveira@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Style Fashion Boutique LTDA',
    'Boutique Style Fashion',
    '34.567.890/0001-03',
    'moda',
    'Moda feminina exclusiva com peças de designers nacionais e internacionais. Coleções sazonais únicas.',
    'Rua Oscar Freire, 789',
    'São Paulo',
    'SP',
    '01426-001',
    '(11) 3456-7892',
    '(11) 98765-4323',
    '@stylefashionsp',
    '/stylefashionboutique',
    'www.stylefashion.com.br',
    'Seg-Sáb: 10h-20h | Dom: 12h-18h',
    20,
    true,
    'https://images.unsplash.com/photo-1445205170230-053b83016050?w=400',
    'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800',
    NOW()
FROM usuarios WHERE email = 'pedro.lima@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Academia Corpo e Mente LTDA',
    'Academia Corpo & Mente',
    '45.678.901/0001-04',
    'saude',
    'Espaço completo de fitness com musculação, pilates, yoga e aulas coletivas. Personal trainers certificados.',
    'Rua dos Atletas, 321',
    'São Paulo',
    'SP',
    '04567-890',
    '(11) 3456-7893',
    '(11) 98765-4324',
    '@corpoemantesp',
    '/academiacorpoemente',
    'www.corpoemente.com.br',
    'Seg-Sex: 6h-22h | Sáb: 8h-18h | Dom: 8h-14h',
    25,
    true,
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400',
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800',
    NOW()
FROM usuarios WHERE email = 'ana.souza@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Beleza Pura Salão LTDA',
    'Salão Beleza Pura',
    '56.789.012/0001-05',
    'beleza',
    'Salão de beleza completo: cabelo, maquiagem, depilação, estética facial e corporal. Produtos premium.',
    'Avenida da Beleza, 654',
    'São Paulo',
    'SP',
    '05678-901',
    '(11) 3456-7894',
    '(11) 98765-4325',
    '@belezapurasp',
    '/salaobelezapura',
    'www.belezapura.com.br',
    'Seg-Sáb: 9h-20h | Fecha Domingo',
    18,
    true,
    'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400',
    'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800',
    NOW()
FROM usuarios WHERE email = 'lucas.alves@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Cafeteria Aroma e Grão LTDA',
    'Cafeteria Aroma & Grão',
    '67.890.123/0001-06',
    'alimentacao',
    'Cafeteria especializada em cafés especiais de micro-lotes. Doces artesanais e pães frescos diários.',
    'Rua dos Cafés, 987',
    'São Paulo',
    'SP',
    '06789-012',
    '(11) 3456-7895',
    '(11) 98765-4326',
    '@aromaegrao',
    '/cafeteriaaromaegrao',
    'www.aromaegrao.com.br',
    'Seg-Sex: 7h-20h | Sáb-Dom: 8h-18h',
    8,
    true,
    'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400',
    'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800',
    NOW()
FROM usuarios WHERE email = 'juliana.martins@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Pet Shop Amigo Fiel LTDA',
    'Pet Shop Amigo Fiel',
    '78.901.234/0001-07',
    'servicos',
    'Pet shop completo com banho, tosa, veterinário e loja de produtos pet. Cuidamos do seu melhor amigo.',
    'Rua dos Pets, 147',
    'São Paulo',
    'SP',
    '07890-123',
    '(11) 3456-7896',
    '(11) 98765-4327',
    '@amigofielsp',
    '/petshopamigosfiel',
    'www.amigofiel.com.br',
    'Seg-Sáb: 8h-19h | Dom: 9h-15h',
    12,
    true,
    'https://images.unsplash.com/photo-1581888227599-779811939961?w=400',
    'https://images.unsplash.com/photo-1548681528-6a5c45b66b42?w=800',
    NOW()
FROM usuarios WHERE email = 'rafael.santos@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Farmácia Vida e Saúde LTDA',
    'Farmácia Vida & Saúde',
    '89.012.345/0001-08',
    'saude',
    'Farmácia de manipulação e produtos naturais. Entrega delivery 24h. Medicamentos genéricos e importados.',
    'Avenida Saúde, 258',
    'São Paulo',
    'SP',
    '08901-234',
    '(11) 3456-7897',
    '(11) 98765-4328',
    '@vidasaudesp',
    '/farmaciavidasaude',
    'www.vidasaude.com.br',
    'Aberto 24 horas',
    10,
    true,
    'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=400',
    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800',
    NOW()
FROM usuarios WHERE email = 'fernanda.rodrigues@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Hamburgueria Urban Grill LTDA',
    'Hamburgueria Urban Grill',
    '90.123.456/0001-09',
    'alimentacao',
    'Hambúrgueres artesanais premium com carnes selecionadas e pães brioche. Batatas rústicas crocantes.',
    'Rua dos Burgers, 369',
    'São Paulo',
    'SP',
    '09012-345',
    '(11) 3456-7898',
    '(11) 98765-4329',
    '@urbangrillsp',
    '/hamburgueriaurbangrill',
    'www.urbangrill.com.br',
    'Seg-Dom: 18h-00h',
    15,
    true,
    'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=400',
    'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800',
    NOW()
FROM usuarios WHERE email = 'carlos.nunes@email.com';

INSERT INTO empresas (usuario_id, razao_social, nome_fantasia, cnpj, categoria, descricao, endereco, cidade, estado, cep, telefone, whatsapp, instagram, facebook, website, horario_funcionamento, pontos_por_checkin, ativo, logo_url, foto_capa_url, created_at) 
SELECT 
    id,
    'Tech Store Eletrônicos LTDA',
    'Loja Tech Store',
    '01.234.567/0001-10',
    'servicos',
    'Eletrônicos e acessórios de última geração. Apple Premium Reseller. Assistência técnica especializada.',
    'Shopping Center, Loja 205',
    'São Paulo',
    'SP',
    '01234-567',
    '(11) 3456-7899',
    '(11) 98765-4330',
    '@techstoresp',
    '/techstorebrasil',
    'www.techstore.com.br',
    'Seg-Sáb: 10h-22h | Dom: 12h-20h',
    30,
    true,
    'https://images.unsplash.com/photo-1601524909162-ae8725290836?w=400',
    'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800',
    NOW()
FROM usuarios WHERE email = 'camila.freitas@email.com';

-- ============================================
-- 3. USUÁRIO ADMIN
-- ============================================

INSERT INTO usuarios (nome, email, senha, cpf, telefone, perfil, ativo, created_at) VALUES
('Administrador Sistema', 'admin@temdettudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '000.000.000-00', '(11) 99999-9999', 'admin', true, NOW());

-- Nota: A senha para TODOS os usuários é: "password"
-- Hash bcrypt de "password": $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- ============================================
-- 4. PROMOÇÕES ATIVAS
-- ============================================

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '🎉 Desconto de 20% no almoço executivo',
    'Aproveite 20% de desconto em nosso menu executivo de segunda a sexta-feira até 15h. Não cumulativo com outras promoções.',
    20,
    50,
    NOW(),
    NOW() + INTERVAL '30 days',
    true,
    'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Restaurante Sabor & Arte';

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '🍕 Compre 1 Pizza Leve 2 às Terças',
    'Todas as terças-feiras: Na compra de 1 pizza grande, ganhe 1 pizza média grátis! Sabor à sua escolha.',
    0,
    30,
    NOW(),
    NOW() + INTERVAL '60 days',
    true,
    'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Pizzaria Bella Napoli';

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '👗 30% OFF Coleção Verão',
    'Desconto especial de 30% em peças selecionadas da nova coleção verão 2026. Parcele em até 3x sem juros.',
    30,
    100,
    NOW(),
    NOW() + INTERVAL '45 days',
    true,
    'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Boutique Style Fashion';

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '💪 1 Mês Grátis na Matrícula Anual',
    'Matricule-se por 1 ano e ganhe 1 mês grátis! Inclui personal trainer nos 3 primeiros meses sem custo adicional.',
    0,
    150,
    NOW(),
    NOW() + INTERVAL '15 days',
    true,
    'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Academia Corpo & Mente';

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '💇 Corte + Escova R$ 59',
    'Promoção especial: Corte + Escova por apenas R$ 59,00. Válido de segunda a quinta-feira mediante agendamento.',
    0,
    40,
    NOW(),
    NOW() + INTERVAL '30 days',
    true,
    'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Salão Beleza Pura';

INSERT INTO promocoes (empresa_id, titulo, descricao, desconto_percentual, pontos_necessarios, validade_inicio, validade_fim, ativo, imagem_url, created_at)
SELECT 
    id,
    '☕ Combo Café + Bolo R$ 15',
    'Experimente nosso combo especial: Café expresso + Fatia de bolo por apenas R$ 15. Perfeito para a tarde!',
    0,
    20,
    NOW(),
    NOW() + INTERVAL '60 days',
    true,
    'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=600',
    NOW()
FROM empresas WHERE nome_fantasia = 'Cafeteria Aroma & Grão';

-- ============================================
-- 5. CHECK-INS E PONTOS
-- ============================================

-- Simular alguns check-ins para clientes
-- (Isso criará histórico realista)

INSERT INTO check_ins (cliente_id, empresa_id, pontos_ganhos, data_checkin, created_at)
SELECT 
    u.id,
    e.id,
    e.pontos_por_checkin,
    NOW() - INTERVAL '5 days',
    NOW() - INTERVAL '5 days'
FROM usuarios u
CROSS JOIN empresas e
WHERE u.email = 'joao.silva@email.com'
AND e.nome_fantasia = 'Restaurante Sabor & Arte'
LIMIT 1;

INSERT INTO check_ins (cliente_id, empresa_id, pontos_ganhos, data_checkin, created_at)
SELECT 
    u.id,
    e.id,
    e.pontos_por_checkin,
    NOW() - INTERVAL '3 days',
    NOW() - INTERVAL '3 days'
FROM usuarios u
CROSS JOIN empresas e
WHERE u.email = 'joao.silva@email.com'
AND e.nome_fantasia = 'Pizzaria Bella Napoli'
LIMIT 1;

-- Adicionar pontos acumulados
INSERT INTO pontos_cliente (cliente_id, empresa_id, pontos_acumulados, pontos_usados, created_at, updated_at)
SELECT 
    u.id,
    e.id,
    125,
    0,
    NOW(),
    NOW()
FROM usuarios u
CROSS JOIN empresas e
WHERE u.email = 'joao.silva@email.com'
AND e.nome_fantasia = 'Restaurante Sabor & Arte'
LIMIT 1;

-- ============================================
-- RESUMO DO SCRIPT
-- ============================================
-- ✅ 10 Clientes fictícios com dados completos
-- ✅ 10 Empresas de categorias diversas
-- ✅ 1 Administrador do sistema
-- ✅ 6 Promoções ativas
-- ✅ Check-ins de exemplo
-- ✅ Pontos acumulados de exemplo
-- ============================================
-- CREDENCIAIS DE ACESSO (TODOS):
-- Senha: password
-- ============================================
-- CLIENTES:
-- joao.silva@email.com
-- maria.oliveira@email.com
-- pedro.lima@email.com
-- ana.souza@email.com
-- lucas.alves@email.com
-- juliana.martins@email.com
-- rafael.santos@email.com
-- fernanda.rodrigues@email.com
-- carlos.nunes@email.com
-- camila.freitas@email.com
-- ============================================
-- ADMIN:
-- admin@temdettudo.com
-- ============================================
