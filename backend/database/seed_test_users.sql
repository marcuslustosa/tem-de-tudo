-- ===================================
-- POPULAR BANCO COM USUÁRIOS DE TESTE
-- ===================================

-- Limpar dados antigos (opcional - comentar se não quiser limpar)
-- TRUNCATE TABLE users CASCADE;

-- SENHA PARA TODOS: senha123

-- 1. ADMIN
INSERT INTO users (name, email, cpf_cnpj, telefone, perfil, password, status, created_at, updated_at)
VALUES 
('Admin Sistema', 'admin@temdetudo.com', '00000000000', '11999999999', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- 2. CLIENTES (5 exemplos)
INSERT INTO users (name, email, cpf_cnpj, telefone, perfil, password, pontos_totais, nivel, status, created_at, updated_at)
VALUES 
('João Silva', 'joao@cliente.com', '12345678901', '11987654321', 'cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1500, 'Prata', 'ativo', NOW(), NOW()),
('Maria Santos', 'maria@cliente.com', '98765432101', '11876543210', 'cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 500, 'Bronze', 'ativo', NOW(), NOW()),
('Pedro Costa', 'pedro@cliente.com', '11122233344', '11765432109', 'cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5500, 'Ouro', 'ativo', NOW(), NOW()),
('Ana Oliveira', 'ana@cliente.com', '55566677788', '11654321098', 'cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 12000, 'Diamante', 'ativo', NOW(), NOW()),
('Carlos Mendes', 'carlos@cliente.com', '99988877766', '11543210987', 'cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 250, 'Bronze', 'ativo', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- 3. EMPRESAS (5 exemplos)
INSERT INTO users (name, email, cpf_cnpj, telefone, perfil, password, status, created_at, updated_at)
VALUES 
('Pizzaria Bella', 'contato@pizzariabella.com', '12345678000199', '1130001000', 'empresa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW()),
('Loja Fashion Style', 'contato@fashionstyle.com', '98765432000188', '1130002000', 'empresa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW()),
('Café Aroma', 'contato@cafearoma.com', '11223344000177', '1130003000', 'empresa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW()),
('Academia FitGym', 'contato@fitgym.com', '55667788000166', '1130004000', 'empresa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW()),
('Salão Beauty', 'contato@salonbeauty.com', '99887766000155', '1130005000', 'empresa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- Confirmar inserções
SELECT 
    perfil,
    COUNT(*) as total,
    STRING_AGG(email, ', ') as emails
FROM users
GROUP BY perfil
ORDER BY perfil;

-- ===================================
-- CREDENCIAIS DE ACESSO
-- ===================================

/*
TODOS OS USUÁRIOS TÊM A SENHA: senha123

ADMIN:
- Email: admin@temdetudo.com
- Senha: senha123

CLIENTES:
- joao@cliente.com (1500 pontos - Prata)
- maria@cliente.com (500 pontos - Bronze)
- pedro@cliente.com (5500 pontos - Ouro)
- ana@cliente.com (12000 pontos - Diamante)
- carlos@cliente.com (250 pontos - Bronze)

EMPRESAS:
- contato@pizzariabella.com
- contato@fashionstyle.com
- contato@cafearoma.com
- contato@fitgym.com
- contato@salonbeauty.com

Todos com senha: senha123
*/
