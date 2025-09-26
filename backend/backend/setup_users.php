<?php

// Teste simples de conexão PostgreSQL
$host = 'dpg-d3649r9r0fns73bk8af0-a.oregon-postgres.render.com';
$port = '5432';
$dbname = 'temdetudo';
$user = 'temdetudo_user';
$password = 'iGkxwfolwLle003d2Q2OdREQ0MF0OB12';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexão com PostgreSQL estabelecida com sucesso!\n";
    
    // Criar tabela users se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'cliente',
            pontos INTEGER DEFAULT 100,
            telefone VARCHAR(20),
            status VARCHAR(20) DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "✅ Tabela users criada/verificada!\n";
    
    // Criar usuário teste se não existir
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@temdetudo.com']);
    
    if ($stmt->fetchColumn() == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, pontos, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Administrador',
            'admin@temdetudo.com',
            $passwordHash,
            'admin',
            0,
            'ativo'
        ]);
        
        echo "✅ Usuário admin criado!\n";
        echo "   Email: admin@temdetudo.com\n";
        echo "   Senha: admin123\n";
    } else {
        echo "ℹ️ Usuário admin já existe!\n";
    }
    
    // Criar usuário cliente teste se não existir
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['cliente@teste.com']);
    
    if ($stmt->fetchColumn() == 0) {
        $passwordHash = password_hash('123456789', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, pontos, status, telefone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Cliente Teste',
            'cliente@teste.com',
            $passwordHash,
            'cliente',
            100,
            'ativo',
            '(11) 99999-9999'
        ]);
        
        echo "✅ Usuário cliente criado!\n";
        echo "   Email: cliente@teste.com\n";
        echo "   Senha: 123456789\n";
    } else {
        echo "ℹ️ Usuário cliente já existe!\n";
    }
    
    // Verificar total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total = $stmt->fetchColumn();
    echo "📊 Total de usuários no banco: $total\n";
    
} catch(PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
}

echo "\n🔗 URLs para teste:\n";
echo "   Login cliente: http://localhost:8000/login.html\n";
echo "   Login admin: http://localhost:8000/admin-login.html\n";

?>