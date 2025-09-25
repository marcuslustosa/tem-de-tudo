<?php

// Test database connection
$host = 'dpg-d3649r9r0fns73bk8af0-a.oregon-postgres.render.com';
$port = '5432';
$database = 'temdetudo';
$username = 'temdetudo_user';
$password = 'iGkxwfolwLle003d2Q2OdREQ0MF0OB12';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Conexão com PostgreSQL estabelecida com sucesso!\n";
    
    // Create users table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'cliente',
            nivel_acesso VARCHAR(50) DEFAULT 'cliente',
            pontos INTEGER DEFAULT 0,
            telefone VARCHAR(20),
            status VARCHAR(20) DEFAULT 'ativo',
            is_active BOOLEAN DEFAULT true,
            permissions TEXT,
            email_verified_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "✅ Tabela users criada/verificada!\n";
    
    // Check existing columns
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Colunas existentes: " . implode(', ', $existingColumns) . "\n";
    
    // Insert admin users
    $adminPassword = password_hash('TemDeTudo2024!', PASSWORD_DEFAULT);
    $operatorPassword = password_hash('Operador2024!', PASSWORD_DEFAULT);
    $clientePassword = password_hash('cliente123', PASSWORD_DEFAULT);
    
    // Use only existing columns
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, created_at, updated_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON CONFLICT (email) DO UPDATE SET
        password = EXCLUDED.password,
        name = EXCLUDED.name,
        updated_at = CURRENT_TIMESTAMP
    ");
    
    // Admin principal
    $stmt->execute([
        'Administrador Principal',
        'admin@temdetudo.com',
        $adminPassword
    ]);
    
    // Operador
    $stmt->execute([
        'Operador Sistema',
        'operador@temdetudo.com',
        $operatorPassword
    ]);
    
    // Cliente teste
    $stmt->execute([
        'Cliente Teste',
        'cliente@teste.com',
        $clientePassword
    ]);
    
    echo "✅ Usuários de teste criados com sucesso!\n";
    echo "\n📋 CREDENCIAIS DE ACESSO:\n";
    echo "══════════════════════════════════════\n";
    echo "👑 ADMIN PRINCIPAL:\n";
    echo "   Email: admin@temdetudo.com\n";
    echo "   Senha: TemDeTudo2024!\n";
    echo "\n👤 OPERADOR:\n";
    echo "   Email: operador@temdetudo.com\n";
    echo "   Senha: Operador2024!\n";
    echo "\n🧑‍💼 CLIENTE TESTE:\n";
    echo "   Email: cliente@teste.com\n";
    echo "   Senha: cliente123\n";
    echo "══════════════════════════════════════\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "🔢 Total de usuários no banco: " . $result['total'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
}