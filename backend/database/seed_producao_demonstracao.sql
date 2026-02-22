-- Dados Fictícios para Demonstração do Sistema Tem de Tudo
-- Execute este SQL para popular o banco com dados de exemplo
-- Compatível com MySQL/PostgreSQL/SQLite

-- Limpar dados existentes (opcional - descomente se necessário)
-- DELETE FROM pontos WHERE id > 0;
-- DELETE FROM empresas WHERE id > 0;
-- DELETE FROM users WHERE id > 0;

-- ========================================
-- USUÁRIOS DE DEMONSTRAÇÃO (3 PERFIS)
-- ========================================

-- 1. ADMINISTRADOR
INSERT INTO users (id, name, email, email_verified_at, password, type, perfil, created_at, updated_at) VALUES 
(1, 'Carlos Eduardo Administrador', 'admin@temdetudo.com', NOW(), '$2y$10$hash_da_senha_123456', 'admin', 'admin', NOW(), NOW());

-- 2. CLIENTES DE EXEMPLO
INSERT INTO users (id, name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES 
(2, 'Maria Silva Santos', 'maria@email.com', NOW(), '$2y$10$hash_da_senha_123456', 'cliente', 'cliente', '(11) 99999-0001', NOW(), NOW()),
(3, 'João Pedro Oliveira', 'joao@email.com', NOW(), '$2y$10$hash_da_senha_123456', 'cliente', 'cliente', '(11) 99999-0002', NOW(), NOW()),
(4, 'Ana Carolina Lima', 'ana@email.com', NOW(), '$2y$10$hash_da_senha_123456', 'cliente', 'cliente', '(11) 99999-0003', NOW(), NOW()),
(5, 'Roberto Costa Silva', 'roberto@email.com', NOW(), '$2y$10$hash_da_senha_123456', 'cliente', 'cliente', '(11) 99999-0004', NOW(), NOW()),
(6, 'Patricia Fernandes', 'patricia@email.com', NOW(), '$2y$10$hash_da_senha_123456', 'cliente', 'cliente', '(11) 99999-0005', NOW(), NOW());

-- 3. EMPRESAS DE EXEMPLO
INSERT INTO users (id, name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES 
(7, 'Restaurante Sabor da Casa', 'contato@sabordacasa.com', NOW(), '$2y$10$hash_da_senha_123456', 'empresa', 'empresa', '(11) 3333-0001', NOW(), NOW()),
(8, 'Farmácia São João', 'contato@farmaciajoao.com', NOW(), '$2y$10$hash_da_senha_123456', 'empresa', 'empresa', '(11) 3333-0002', NOW(), NOW()),
(9, 'Posto Shell Centro', 'contato@shellcentro.com', NOW(), '$2y$10$hash_da_senha_123456', 'empresa', 'empresa', '(11) 3333-0003', NOW(), NOW()),
(10, 'Supermercado Família', 'contato@superfamilia.com', NOW(), '$2y$10$hash_da_senha_123456', 'empresa', 'empresa', '(11) 3333-0004', NOW(), NOW()),
(11, 'Loja de Roupas Fashion', 'contato@fashionloja.com', NOW(), '$2y$10$hash_da_senha_123456', 'empresa', 'empresa', '(11) 3333-0005', NOW(), NOW());

-- ========================================
-- EMPRESAS DETALHADAS
-- ========================================

INSERT INTO empresas (id, user_id, nome_empresa, cnpj, categoria, endereco, cidade, estado, cep, descricao, status, created_at, updated_at) VALUES 
(1, 7, 'Restaurante Sabor da Casa', '12.345.678/0001-01', 'Restaurantes', 'Rua das Flores, 123', 'São Paulo', 'SP', '01234-567', 'Restaurante familiar com pratos caseiros desde 1985', 'ativo', NOW(), NOW()),
(2, 8, 'Farmácia São João', '23.456.789/0001-02', 'Farmácias', 'Av. Paulista, 456', 'São Paulo', 'SP', '01310-100', 'Farmácia completa com atendimento 24h', 'ativo', NOW(), NOW()),
(3, 9, 'Posto Shell Centro', '34.567.890/0001-03', 'Postos de Combustível', 'Rua do Comércio, 789', 'São Paulo', 'SP', '01020-000', 'Posto de combustível com conveniência completa', 'ativo', NOW(), NOW()),
(4, 10, 'Supermercado Família', '45.678.901/0001-04', 'Supermercados', 'Rua do Mercado, 321', 'São Paulo', 'SP', '05432-019', 'Supermercado com produtos frescos e ofertas diárias', 'ativo', NOW(), NOW()),
(5, 11, 'Loja de Roupas Fashion', '56.789.012/0001-05', 'Moda e Vestuário', 'Shopping Center, Loja 205', 'São Paulo', 'SP', '04567-890', 'Moda jovem e tendências atuais', 'ativo', NOW(), NOW());

-- ========================================
-- PONTOS DOS CLIENTES (EXEMPLO)
-- ========================================

INSERT INTO pontos (id, user_id, empresa_id, pontos_ganhos, pontos_utilizados, saldo_atual, descricao, tipo_transacao, created_at, updated_at) VALUES 
-- Maria Silva Santos
(1, 2, 1, 50, 0, 50, 'Compra no restaurante - R$ 25,00', 'ganho', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 2, 30, 0, 30, 'Compra na farmácia - R$ 15,00', 'ganho', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 4, 100, 0, 100, 'Compra no supermercado - R$ 50,00', 'ganho', NOW(), NOW()),

-- João Pedro Oliveira  
(4, 3, 3, 80, 0, 80, 'Abastecimento no posto - R$ 40,00', 'ganho', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 3, 5, 60, 30, 30, 'Compra de roupas - R$ 30,00', 'ganho', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 3, 5, 0, 30, 30, 'Desconto utilizado - R$ 15,00', 'uso', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Ana Carolina Lima
(7, 4, 1, 40, 0, 40, 'Almoço no restaurante - R$ 20,00', 'ganho', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 4, 2, 25, 0, 25, 'Medicamentos - R$ 12,50', 'ganho', NOW(), NOW());

-- ========================================
-- PROMOÇÕES ATIVAS
-- ========================================

INSERT INTO promocoes (id, empresa_id, titulo, descricao, pontos_necessarios, desconto_porcentagem, desconto_valor, valido_ate, status, created_at, updated_at) VALUES 
(1, 1, '10% OFF Almoço Executivo', 'Desconto de 10% no almoço executivo de segunda a sexta', 50, 10, 0, DATE_ADD(NOW(), INTERVAL 30 DAY), 'ativo', NOW(), NOW()),
(2, 2, 'R$ 5 OFF Medicamentos', 'R$ 5,00 de desconto em medicamentos acima de R$ 20,00', 25, 0, 5.00, DATE_ADD(NOW(), INTERVAL 15 DAY), 'ativo', NOW(), NOW()),
(3, 3, 'Combustível com Desconto', '5% de desconto no combustível', 100, 5, 0, DATE_ADD(NOW(), INTERVAL 7 DAY), 'ativo', NOW(), NOW()),
(4, 4, '15% OFF Compras acima de R$ 50', 'Desconto de 15% em compras acima de R$ 50,00', 75, 15, 0, DATE_ADD(NOW(), INTERVAL 20 DAY), 'ativo', NOW(), NOW()),
(5, 5, 'R$ 10 OFF Roupas', 'R$ 10,00 de desconto em roupas acima de R$ 80,00', 60, 0, 10.00, DATE_ADD(NOW(), INTERVAL 25 DAY), 'ativo', NOW(), NOW());

-- ========================================
-- NOTIFICAÇÕES PARA DEMONSTRAÇÃO
-- ========================================

INSERT INTO notificacoes (id, user_id, titulo, mensagem, tipo, lida, created_at, updated_at) VALUES 
(1, 2, 'Bem-vindo ao Tem de Tudo!', 'Sua conta foi criada com sucesso! Comece a acumular pontos agora.', 'boas-vindas', 0, NOW(), NOW()),
(2, 2, 'Pontos Ganhos!', 'Você ganhou 50 pontos no Restaurante Sabor da Casa', 'pontos', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 3, 'Promoção Especial!', 'Nova promoção no Posto Shell Centro: 5% de desconto!', 'promocao', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 4, 'Lembrete', 'Você tem pontos acumulados! Que tal usar em alguma promoção?', 'lembrete', 0, NOW(), NOW());

-- ========================================
-- CONFIGURAÇÕES DE PONTUAÇÃO 
-- ========================================

INSERT INTO configuracoes (id, chave, valor, descricao, created_at, updated_at) VALUES 
(1, 'pontos_por_real', '2', 'Quantos pontos são ganhos por real gasto', NOW(), NOW()),
(2, 'pontos_minimos_uso', '25', 'Quantidade mínima de pontos para usar', NOW(), NOW()),
(3, 'valor_ponto', '0.50', 'Valor em reais de cada ponto', NOW(), NOW()),
(4, 'bonus_cadastro', '100', 'Pontos de bônus ao se cadastrar', NOW(), NOW());

-- ========================================
-- DADOS RESUMO PARA ADMIN
-- ========================================

-- Atualizar contadores para o admin dashboard
UPDATE users SET 
    total_pontos = (SELECT COALESCE(SUM(saldo_atual), 0) FROM pontos WHERE user_id = users.id),
    ultimo_acesso = CASE 
        WHEN id = 1 THEN NOW() 
        WHEN id IN (2,3,4) THEN DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY)
        ELSE DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY)
    END
WHERE id > 0;

-- ========================================
-- SENHAS DE DEMONSTRAÇÃO
-- ========================================

/*
SENHAS PARA TESTE (todas são: 123456):

ADMIN:
Email: admin@temdetudo.com
Senha: 123456

CLIENTES:
Email: maria@email.com - Senha: 123456
Email: joao@email.com - Senha: 123456
Email: ana@email.com - Senha: 123456

EMPRESAS:
Email: contato@sabordacasa.com - Senha: 123456
Email: contato@farmaciajoao.com - Senha: 123456
Email: contato@shellcentro.com - Senha: 123456

NOTA: As senhas estão com hash fictício. 
Para funcionar, substitua pela hash real do seu sistema:
password_hash('123456', PASSWORD_DEFAULT) no PHP
*/

-- ========================================
-- VERIFICAÇÃO DOS DADOS INSERIDOS
-- ========================================

SELECT 'USUÁRIOS INSERIDOS:' as info, COUNT(*) as total FROM users;
SELECT 'EMPRESAS INSERIDAS:' as info, COUNT(*) as total FROM empresas; 
SELECT 'PONTOS INSERIDOS:' as info, COUNT(*) as total FROM pontos;
SELECT 'PROMOÇÕES ATIVAS:' as info, COUNT(*) as total FROM promocoes WHERE status = 'ativo';
SELECT 'NOTIFICAÇÕES:' as info, COUNT(*) as total FROM notificacoes;