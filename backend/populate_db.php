<?php
// Script simples para popular o banco de dados SQLite
// Execute com: php populate_db.php

// Configuração básica
require_once __DIR__ . '/vendor/autoload.php';

// Usar SQLite diretamente
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Conectado ao banco SQLite...\n";

try {
    // Inserir usuário admin se não existir
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (name, email, password, perfil, pontos, nivel, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Administrador',
        'admin@temdetudo.com',
        password_hash('123456', PASSWORD_DEFAULT),
        'admin',
        0,
        'Gold',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s')
    ]);
    echo "Usuário admin inserido/já existe\n";

    // Buscar ID do admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@temdetudo.com']);
    $admin = $stmt->fetch();
    $adminId = $admin['id'];

    // Empresas para inserir
    $empresas = [
        ['Sabor e Arte', 'Rua das Flores, 123 - Centro, São Paulo - SP', '(11) 3333-4444', '11.111.111/0001-01', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400', 'Restaurante brasileiro com pratos tradicionais', 1.5],
        ['Bella Napoli', 'Av. Paulista, 456 - Bela Vista, São Paulo - SP', '(11) 5555-6666', '22.222.222/0001-02', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400', 'Pizzaria artesanal com ingredientes frescos', 2.0],
        ['FitLife Academia', 'Rua da Saúde, 789 - Liberdade, São Paulo - SP', '(11) 7777-8888', '33.333.333/0001-03', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400', 'Academia completa com aparelhos modernos', 1.0],
        ['Beleza Total', 'Rua Augusta, 321 - Consolação, São Paulo - SP', '(11) 9999-0000', '44.444.444/0001-04', 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400', 'Salão de beleza com serviços completos', 1.2],
        ['Café & Cia', 'Rua Oscar Freire, 654 - Jardins, São Paulo - SP', '(11) 1111-2222', '55.555.555/0001-05', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400', 'Cafeteria premium com grãos especiais', 1.5],
        ['Pet Shop Amigo Fiel', 'Rua dos Animais, 987 - Vila Madalena, São Paulo - SP', '(11) 3333-4455', '66.666.666/0001-06', 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400', 'Pet Shop com produtos e serviços para pets', 1.0],
        ['Farmácia Saúde Plus', 'Av. Faria Lima, 111 - Itaim Bibi, São Paulo - SP', '(11) 5555-7788', '77.777.777/0001-07', 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400', 'Farmácia 24h com medicamentos e conveniência', 1.3],
        ['Burger Gourmet', 'Rua da Liberdade, 222 - Liberdade, São Paulo - SP', '(11) 9988-7766', '88.888.888/0001-08', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400', 'Hamburgueria artesanal com ingredientes selecionados', 2.0]
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO empresas (nome, endereco, telefone, cnpj, logo, descricao, points_multiplier, ativo, owner_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)");
    
    foreach ($empresas as $empresa) {
        $stmt->execute([
            $empresa[0], // nome
            $empresa[1], // endereco
            $empresa[2], // telefone
            $empresa[3], // cnpj
            $empresa[4], // logo
            $empresa[5], // descricao
            $empresa[6], // points_multiplier
            $adminId,    // owner_id
            date('Y-m-d H:i:s'), // created_at
            date('Y-m-d H:i:s')  // updated_at
        ]);
        echo "Empresa inserida: {$empresa[0]}\n";
    }

    // Verificar total
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empresas");
    $result = $stmt->fetch();
    echo "\nTotal de empresas no banco: {$result['total']}\n";

    echo "População concluída com sucesso!\n";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}