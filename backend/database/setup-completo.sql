-- ============================================
-- SCRIPT COMPLETO DE CRIAÇÃO E POPULAÇÃO DO BANCO
-- Sistema: Tem de Tudo - Programa de Fidelidade
-- Data: 03/02/2026
-- ============================================

-- DROP tables se existirem (para ambiente de desenvolvimento)
DROP TABLE IF EXISTS cupons CASCADE;
DROP TABLE IF EXISTS promocoes CASCADE;
DROP TABLE IF EXISTS checkins CASCADE;
DROP TABLE IF EXISTS empresas CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- ============================================
-- TABELA: users (Clientes e Empresas)
-- ============================================
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'empresa', 'admin') DEFAULT 'cliente',
    cpf VARCHAR(14) NULL,
    cnpj VARCHAR(18) NULL,
    telefone VARCHAR(20) NULL,
    pontos DECIMAL(10, 2) DEFAULT 0,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tipo ON users(tipo);
CREATE INDEX idx_users_cpf ON users(cpf);
CREATE INDEX idx_users_cnpj ON users(cnpj);

-- ============================================
-- TABELA: empresas (Estabelecimentos)
-- ============================================
CREATE TABLE empresas (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    categoria VARCHAR(100) NULL,
    endereco TEXT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    telefone VARCHAR(20) NULL,
    horario_funcionamento TEXT NULL,
    foto_url VARCHAR(500) NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_empresas_user_id ON empresas(user_id);
CREATE INDEX idx_empresas_categoria ON empresas(categoria);
CREATE INDEX idx_empresas_ativo ON empresas(ativo);

-- ============================================
-- TABELA: checkins (Registro de visitas)
-- ============================================
CREATE TABLE checkins (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    empresa_id BIGINT NOT NULL,
    pontos_ganhos DECIMAL(10, 2) DEFAULT 10,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    metodo ENUM('qrcode', 'manual', 'automatico') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE INDEX idx_checkins_user_id ON checkins(user_id);
CREATE INDEX idx_checkins_empresa_id ON checkins(empresa_id);
CREATE INDEX idx_checkins_created_at ON checkins(created_at);

-- ============================================
-- TABELA: promocoes (Ofertas e descontos)
-- ============================================
CREATE TABLE promocoes (
    id BIGSERIAL PRIMARY KEY,
    empresa_id BIGINT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    pontos_necessarios INT NOT NULL,
    desconto_percentual DECIMAL(5, 2) NULL,
    desconto_valor DECIMAL(10, 2) NULL,
    validade_inicio DATE NULL,
    validade_fim DATE NULL,
    quantidade_disponivel INT NULL,
    quantidade_resgatada INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    imagem_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE INDEX idx_promocoes_empresa_id ON promocoes(empresa_id);
CREATE INDEX idx_promocoes_ativo ON promocoes(ativo);
CREATE INDEX idx_promocoes_validade ON promocoes(validade_fim);

-- ============================================
-- TABELA: cupons (Cupons resgatados pelos clientes)
-- ============================================
CREATE TABLE cupons (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    promocao_id BIGINT NOT NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    data_uso TIMESTAMP NULL,
    validade DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (promocao_id) REFERENCES promocoes(id) ON DELETE CASCADE
);

CREATE INDEX idx_cupons_user_id ON cupons(user_id);
CREATE INDEX idx_cupons_promocao_id ON cupons(promocao_id);
CREATE INDEX idx_cupons_codigo ON cupons(codigo);
CREATE INDEX idx_cupons_usado ON cupons(usado);

-- ============================================
-- POPULAÇÃO DO BANCO COM DADOS FICTÍCIOS
-- ============================================

-- Inserir USUÁRIOS (6 usuários de teste)
-- Senha: senha123 (hash bcrypt)
INSERT INTO users (nome, email, password, tipo, cpf, telefone, pontos) VALUES
('Maria Silva', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', '123.456.789-01', '(11) 98765-4321', 195.00),
('João Santos', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', '987.654.321-09', '(11) 91234-5678', 120.00),
('Restaurante Sabor & Arte', 'saborearte@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', NULL, '(11) 3456-7890', 0),
('Pizzaria Bella Napoli', 'bellanapoli@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', NULL, '(11) 3789-0123', 0),
('Admin Sistema', 'admin@temdetudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '(11) 99999-9999', 0),
('Gerente Operacional', 'gerente@temdetudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '111.222.333-44', '(11) 98888-8888', 0)
ON CONFLICT (email) DO NOTHING;

-- Inserir EMPRESAS (6 empresas)
INSERT INTO empresas (user_id, nome, descricao, categoria, endereco, latitude, longitude, telefone, horario_funcionamento, ativo) VALUES
(3, 'Restaurante Sabor & Arte', 'Culinária brasileira com toque contemporâneo. Ambiente aconchegante e pratos deliciosos.', 'alimentacao', 'Rua das Flores, 123 - Centro, São Paulo - SP', -23.550520, -46.633308, '(11) 3456-7890', 'Seg-Sex: 11h-23h | Sáb-Dom: 12h-00h', TRUE),
(4, 'Pizzaria Bella Napoli', 'As melhores pizzas artesanais da região. Massa fina e ingredientes selecionados.', 'alimentacao', 'Av. Paulista, 456 - Bela Vista, São Paulo - SP', -23.561414, -46.656178, '(11) 3789-0123', 'Ter-Dom: 18h-23h', TRUE),
(1, 'Salão Beleza Pura', 'Serviços de cabelo, maquiagem e estética com profissionais qualificados.', 'beleza', 'Rua Augusta, 789 - Consolação, São Paulo - SP', -23.554820, -46.662520, '(11) 3333-4444', 'Seg-Sáb: 9h-19h', TRUE),
(2, 'Academia FitPower', 'Academia completa com musculação, aeróbica e aulas coletivas.', 'bemestar', 'Rua dos Esportes, 321 - Mooca, São Paulo - SP', -23.549300, -46.599200, '(11) 2222-3333', 'Seg-Sex: 6h-22h | Sáb: 8h-14h', TRUE),
(3, 'Auto Center Speed', 'Manutenção automotiva, troca de óleo, alinhamento e balanceamento.', 'automotivo', 'Av. do Estado, 654 - Ipiranga, São Paulo - SP', -23.587900, -46.610100, '(11) 4444-5555', 'Seg-Sex: 8h-18h | Sáb: 8h-12h', TRUE),
(4, 'Farmácia Saúde Total', 'Medicamentos, perfumaria e produtos de higiene com ótimos preços.', 'saude', 'Rua da Consolação, 987 - República, São Paulo - SP', -23.543300, -46.645400, '(11) 5555-6666', '24 horas', TRUE)
ON CONFLICT DO NOTHING;

-- Inserir PROMOÇÕES (10 promoções variadas)
INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, desconto_percentual, desconto_valor, validade_inicio, validade_fim, quantidade_disponivel, ativo, imagem_url) VALUES
(1, '20% OFF no rodízio', 'Ganhe 20% de desconto no rodízio completo de carnes nobres.', 50, 20.00, NULL, '2026-02-01', '2026-08-31', 100, TRUE, 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba'),
(1, 'Sobremesa Grátis', 'Sobremesa grátis na compra de qualquer prato executivo.', 30, NULL, 15.00, '2026-02-01', '2026-06-30', 200, TRUE, 'https://images.unsplash.com/photo-1551024506-0bccd828d307'),
(2, 'Pizza Grande por R$ 39,90', 'Qualquer pizza grande de até 3 sabores por apenas R$ 39,90.', 80, NULL, 20.00, '2026-02-01', '2026-12-31', 150, TRUE, 'https://images.unsplash.com/photo-1513104890138-7c749659a591'),
(2, '2ª Pizza 50% OFF', 'Na compra de uma pizza grande, leve a segunda com 50% de desconto.', 60, 50.00, NULL, '2026-02-01', '2026-07-31', 100, TRUE, 'https://images.unsplash.com/photo-1571997478779-2adcbbe9ab2f'),
(3, 'Corte + Barba R$ 60', 'Pacote completo de corte masculino + barba por apenas R$ 60.', 40, NULL, 25.00, '2026-02-01', '2026-05-31', 80, TRUE, 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1'),
(3, '15% OFF em Coloração', 'Desconto especial em todos os serviços de coloração.', 70, 15.00, NULL, '2026-02-01', '2026-09-30', 60, TRUE, 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e'),
(4, '1 Mês Grátis de Musculação', 'Ganhe 1 mês grátis de musculação na matrícula anual.', 100, NULL, 150.00, '2026-02-01', '2026-04-30', 50, TRUE, 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48'),
(5, '10% OFF na Revisão Completa', 'Desconto na revisão completa do seu veículo.', 50, 10.00, NULL, '2026-02-01', '2026-10-31', 120, TRUE, 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3'),
(6, 'R$ 20 OFF acima de R$ 100', 'Compre R$ 100 ou mais e ganhe R$ 20 de desconto.', 35, NULL, 20.00, '2026-02-01', '2026-12-31', 300, TRUE, 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de'),
(6, 'Frete Grátis', 'Frete grátis em compras acima de R$ 50.', 25, NULL, 10.00, '2026-02-01', '2026-11-30', 500, TRUE, 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88')
ON CONFLICT DO NOTHING;

-- Inserir CHECK-INS (10 check-ins de exemplo)
INSERT INTO checkins (user_id, empresa_id, pontos_ganhos, latitude, longitude, metodo, created_at) VALUES
(1, 1, 10.00, -23.550520, -46.633308, 'qrcode', '2026-02-01 12:30:00'),
(1, 2, 10.00, -23.561414, -46.656178, 'manual', '2026-02-02 19:15:00'),
(1, 3, 15.00, -23.554820, -46.662520, 'qrcode', '2026-02-03 14:00:00'),
(1, 5, 10.00, -23.587900, -46.610100, 'manual', '2026-02-03 16:45:00'),
(2, 1, 10.00, -23.550520, -46.633308, 'qrcode', '2026-02-01 13:00:00'),
(2, 4, 20.00, -23.549300, -46.599200, 'automatico', '2026-02-02 07:30:00'),
(2, 6, 10.00, -23.543300, -46.645400, 'manual', '2026-02-03 10:20:00'),
(1, 4, 15.00, -23.549300, -46.599200, 'qrcode', '2026-02-04 18:00:00'),
(2, 3, 10.00, -23.554820, -46.662520, 'manual', '2026-02-05 11:30:00'),
(1, 6, 10.00, -23.543300, -46.645400, 'qrcode', '2026-02-06 15:45:00')
ON CONFLICT DO NOTHING;

-- Inserir CUPONS (4 cupons resgatados)
INSERT INTO cupons (user_id, promocao_id, codigo, usado, data_uso, validade, created_at) VALUES
(1, 1, 'CUPOM-20OFF-001', FALSE, NULL, '2026-08-31', '2026-02-01 14:00:00'),
(1, 3, 'CUPOM-PIZZA-002', FALSE, NULL, '2026-12-31', '2026-02-02 20:00:00'),
(2, 5, 'CUPOM-CORTE-003', TRUE, '2026-02-05 10:00:00', '2026-05-31', '2026-02-04 15:30:00'),
(2, 9, 'CUPOM-FARMACIA-004', FALSE, NULL, '2026-12-31', '2026-02-06 16:00:00')
ON CONFLICT (codigo) DO NOTHING;

-- ============================================
-- ATUALIZAR CONTADORES
-- ============================================
UPDATE promocoes SET quantidade_resgatada = 1 WHERE id = 1;
UPDATE promocoes SET quantidade_resgatada = 1 WHERE id = 3;
UPDATE promocoes SET quantidade_resgatada = 1 WHERE id = 5;
UPDATE promocoes SET quantidade_resgatada = 1 WHERE id = 9;

-- ============================================
-- VERIFICAÇÕES FINAIS
-- ============================================
-- Verificar total de registros criados
SELECT 'users' AS tabela, COUNT(*) AS total FROM users
UNION ALL
SELECT 'empresas', COUNT(*) FROM empresas
UNION ALL
SELECT 'checkins', COUNT(*) FROM checkins
UNION ALL
SELECT 'promocoes', COUNT(*) FROM promocoes
UNION ALL
SELECT 'cupons', COUNT(*) FROM cupons;

-- ============================================
-- SCRIPT COMPLETO - FIM
-- ============================================
