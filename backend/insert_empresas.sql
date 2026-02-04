-- Script SQL para popular empresas no banco SQLite

-- Primeiro, inserir usuário admin se não existir
INSERT OR IGNORE INTO users (name, email, password, perfil, pontos, nivel, created_at, updated_at) 
VALUES ('Administrador', 'admin@temdetudo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, 'Gold', datetime('now'), datetime('now'));

-- Obter ID do admin (será 1 se for o primeiro usuário)
-- Para SQLite, vamos assumir que será ID 1

-- Inserir empresas
INSERT OR IGNORE INTO empresas (nome, endereco, telefone, cnpj, logo, descricao, points_multiplier, ativo, owner_id, created_at, updated_at) VALUES
('Sabor e Arte', 'Rua das Flores, 123 - Centro, São Paulo - SP', '(11) 3333-4444', '11.111.111/0001-01', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400', 'Restaurante brasileiro com pratos tradicionais', 1.5, 1, 1, datetime('now'), datetime('now')),
('Bella Napoli', 'Av. Paulista, 456 - Bela Vista, São Paulo - SP', '(11) 5555-6666', '22.222.222/0001-02', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400', 'Pizzaria artesanal com ingredientes frescos', 2.0, 1, 1, datetime('now'), datetime('now')),
('FitLife Academia', 'Rua da Saúde, 789 - Liberdade, São Paulo - SP', '(11) 7777-8888', '33.333.333/0001-03', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400', 'Academia completa com aparelhos modernos', 1.0, 1, 1, datetime('now'), datetime('now')),
('Beleza Total', 'Rua Augusta, 321 - Consolação, São Paulo - SP', '(11) 9999-0000', '44.444.444/0001-04', 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400', 'Salão de beleza com serviços completos', 1.2, 1, 1, datetime('now'), datetime('now')),
('Café & Cia', 'Rua Oscar Freire, 654 - Jardins, São Paulo - SP', '(11) 1111-2222', '55.555.555/0001-05', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400', 'Cafeteria premium com grãos especiais', 1.5, 1, 1, datetime('now'), datetime('now')),
('Pet Shop Amigo Fiel', 'Rua dos Animais, 987 - Vila Madalena, São Paulo - SP', '(11) 3333-4455', '66.666.666/0001-06', 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400', 'Pet Shop com produtos e serviços para pets', 1.0, 1, 1, datetime('now'), datetime('now')),
('Farmácia Saúde Plus', 'Av. Faria Lima, 111 - Itaim Bibi, São Paulo - SP', '(11) 5555-7788', '77.777.777/0001-07', 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400', 'Farmácia 24h com medicamentos e conveniência', 1.3, 1, 1, datetime('now'), datetime('now')),
('Burger Gourmet', 'Rua da Liberdade, 222 - Liberdade, São Paulo - SP', '(11) 9988-7766', '88.888.888/0001-08', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400', 'Hamburgueria artesanal com ingredientes selecionados', 2.0, 1, 1, datetime('now'), datetime('now'));

-- Verificar se inseriu corretamente
SELECT 'Total de empresas:', COUNT(*) FROM empresas;
SELECT nome, cnpj FROM empresas;