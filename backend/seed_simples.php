<?php
/**
 * Script Simplificado para Popular Banco com Dados de Demonstração
 * Tem de Tudo - Sistema de Fidelidade
 * 
 * Execute este arquivo para popular o banco com dados fictícios
 * para demonstração dos 3 perfis: Admin, Cliente, Empresa
 */

try {
    // Configurações do banco - ajuste conforme necessário
    $host = 'localhost';
    $dbname = 'tem_de_tudo';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Conectado ao banco de dados: $dbname\n";
    
    echo "🧹 Limpando dados existentes (se houver)...\n";
    
    // Limpar dados existentes (opcional)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM pontos WHERE id > 0");
    $pdo->exec("DELETE FROM promocoes WHERE id > 0");
    $pdo->exec("DELETE FROM notificacoes WHERE id > 0");
    $pdo->exec("DELETE FROM empresas WHERE id > 0");
    $pdo->exec("DELETE FROM users WHERE id > 0");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "👑 Inserindo ADMINISTRADOR (PERFIL OFICIAL DE TESTE)...\n";
    
    // 1. ADMINISTRADOR OFICIAL DE TESTE
    $adminSenha = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())");
    $stmt->execute(['Administrador Sistema', 'admin@temdetudo.com', $adminSenha, 'admin', 'admin']);
    $adminId = $pdo->lastInsertId();
    
    echo "🏢 Inserindo EMPRESA (PERFIL OFICIAL DE TESTE)...\n";
    
    // 2. EMPRESA OFICIAL DE TESTE  
    $empresaSenha = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute(['Empresa de Teste', 'empresa@teste.com', $empresaSenha, 'empresa', 'empresa', '(11) 99999-9999']);
    $empresaTesteUserId = $pdo->lastInsertId();
    
    echo "👤 Inserindo CLIENTE (PERFIL OFICIAL DE TESTE)...\n";
    
    // 3. CLIENTE OFICIAL DE TESTE
    $clienteSenha = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute(['Cliente de Teste', 'cliente@teste.com', $clienteSenha, 'cliente', 'cliente', '(11) 88888-8888']);
    $clienteTesteId = $pdo->lastInsertId();
    
    echo "👥 Inserindo CLIENTES EXTRAS PARA DEMONSTRAÇÃO...\n";
    
    // Hash da senha padrão para outros usuários: 123456
    $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
    
    // 4. CLIENTES EXTRAS DE EXEMPLO
    $clientes = [
        ['Maria Silva Santos', 'maria@email.com', '(11) 99999-0001'],
        ['João Pedro Oliveira', 'joao@email.com', '(11) 99999-0002'],
        ['Ana Carolina Lima', 'ana@email.com', '(11) 99999-0003']
    ];
    
    $clienteIds = [$clienteTesteId]; // Incluir o cliente de teste
    foreach ($clientes as $cliente) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$cliente[0], $cliente[1], $senhaHash, 'cliente', 'cliente', $cliente[2]]);
        $clienteIds[] = $pdo->lastInsertId();
    }
    
    echo "🏢 Inserindo EMPRESAS EXTRAS PARA DEMONSTRAÇÃO...\n";
    
    // 5. EMPRESAS EXTRAS DE EXEMPLO
    $empresas = [
        ['Restaurante Sabor da Casa', 'contato@sabordacasa.com', '(11) 3333-0001'],
        ['Farmácia São João', 'contato@farmaciajoao.com', '(11) 3333-0002']
    ];
    
    $empresaUserIds = [$empresaTesteUserId]; // Incluir a empresa de teste
    foreach ($empresas as $empresa) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$empresa[0], $empresa[1], $senhaHash, 'empresa', 'empresa', $empresa[2]]);
        $empresaUserIds[] = $pdo->lastInsertId();
    }
    
    // 6. EMPRESAS DETALHADAS
    $empresasDetalhes = [
        [$empresaTesteUserId, 'Empresa de Teste Demonstração', '11.111.111/0001-11', 'Teste', 'Rua de Teste, 123', 'São Paulo', 'SP', '00000-000', 'Empresa oficial para testes do sistema'],
        [$empresaUserIds[1], 'Restaurante Sabor da Casa', '12.345.678/0001-01', 'Restaurantes', 'Rua das Flores, 123', 'São Paulo', 'SP', '01234-567', 'Restaurante familiar com pratos caseiros desde 1985'],
        [$empresaUserIds[2], 'Farmácia São João', '23.456.789/0001-02', 'Farmácias', 'Av. Paulista, 456', 'São Paulo', 'SP', '01310-100', 'Farmácia completa com atendimento 24h']
    ];
    
    $empresaIds = [];
    foreach ($empresasDetalhes as $empresa) {
        $stmt = $pdo->prepare("INSERT INTO empresas (user_id, nome_empresa, cnpj, categoria, endereco, cidade, estado, cep, descricao, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())");
        $stmt->execute($empresa);
        $empresaIds[] = $pdo->lastInsertId();
    }
    
    echo "⭐ Inserindo PONTOS DOS CLIENTES...\n";
    
    // 7. PONTOS DOS CLIENTES
    $pontos = [
        // Cliente de Teste Oficial (150 pontos iniciais)
        [$clienteTesteId, $empresaIds[1], 50, 0, 50, 'Compra inicial no restaurante - R$ 25,00', 'ganho', '-5 day'],
        [$clienteTesteId, $empresaIds[2], 40, 0, 40, 'Compra na farmácia - R$ 20,00', 'ganho', '-3 day'],
        [$clienteTesteId, $empresaIds[1], 60, 0, 60, 'Compra no restaurante - R$ 30,00', 'ganho', '-1 day'],
        
        // Maria Silva Santos (clienteIds[1])
        [$clienteIds[1], $empresaIds[1], 50, 0, 50, 'Compra no restaurante - R$ 25,00', 'ganho', '-2 day'],
        [$clienteIds[1], $empresaIds[2], 30, 0, 30, 'Compra na farmácia - R$ 15,00', 'ganho', '-1 day'],
        [$clienteIds[1], $empresaIds[1], 100, 0, 100, 'Compra no restaurante - R$ 50,00', 'ganho', '0 day']
    ];
    
    foreach ($pontos as $ponto) {
        $data = date('Y-m-d H:i:s', strtotime($ponto[7]));
        $stmt = $pdo->prepare("INSERT INTO pontos (user_id, empresa_id, pontos_ganhos, pontos_utilizados, saldo_atual, descricao, tipo_transacao, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ponto[0], $ponto[1], $ponto[2], $ponto[3], $ponto[4], $ponto[5], $ponto[6], $data, $data]);
    }
    
    echo "🎁 Inserindo PROMOÇÕES ATIVAS...\n";
    
    // 8. PROMOÇÕES ATIVAS
    $promocoes = [
        [$empresaIds[0], 'Oferta de Teste', 'Promoção especial da empresa de teste', 30, 15, 0, '+30 day'],
        [$empresaIds[1], '10% OFF Almoço Executivo', 'Desconto de 10% no almoço executivo de segunda a sexta', 50, 10, 0, '+30 day'],
        [$empresaIds[2], 'R$ 5 OFF Medicamentos', 'R$ 5,00 de desconto em medicamentos acima de R$ 20,00', 25, 0, 5.00, '+15 day']
    ];
    
    foreach ($promocoes as $promocao) {
        $validoAte = date('Y-m-d H:i:s', strtotime($promocao[6]));
        $stmt = $pdo->prepare("INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, desconto_porcentagem, desconto_valor, valido_ate, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())");
        $stmt->execute([$promocao[0], $promocao[1], $promocao[2], $promocao[3], $promocao[4], $promocao[5], $validoAte]);
    }
    
    echo "\n✅ DADOS DE DEMONSTRAÇÃO INSERIDOS COM SUCESSO!\n\n";
    echo "🔑 CREDENCIAIS OFICIAIS PARA TESTE:\n";
    echo "========================================\n";
    echo "👑 ADMINISTRADOR:\n";
    echo "   Email: admin@temdetudo.com\n";
    echo "   Senha: admin123\n\n";
    
    echo "🏢 EMPRESA:\n";
    echo "   Email: empresa@teste.com\n";
    echo "   Senha: 123456\n\n";
    
    echo "👤 CLIENTE:\n";
    echo "   Email: cliente@teste.com\n";
    echo "   Senha: 123456 (150 pontos)\n\n";
    
    echo "📋 USUÁRIOS EXTRAS PARA DEMONSTRAÇÃO:\n";
    echo "=======================================\n";
    echo "👥 CLIENTES EXTRAS:\n";
    foreach ($clientes as $cliente) {
        echo "   Email: {$cliente[1]} - Senha: 123456\n";
    }
    echo "\n";
    
    echo "🏢 EMPRESAS EXTRAS:\n";
    foreach ($empresas as $empresa) {
        echo "   Email: {$empresa[1]} - Senha: 123456\n";
    }
    echo "\n";
    
    // Verificar dados inseridos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $users = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empresas");
    $empresas_count = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pontos");
    $pontos_count = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM promocoes WHERE status = 'ativo'");
    $promocoes_count = $stmt->fetch()['total'];
    
    echo "📊 RESUMO DOS DADOS INSERIDOS:\n";
    echo "===============================\n";
    echo "👥 Usuários: $users\n";
    echo "🏢 Empresas: $empresas_count\n";
    echo "⭐ Transações de Pontos: $pontos_count\n";
    echo "🎁 Promoções Ativas: $promocoes_count\n\n";
    
    echo "🚀 SISTEMA PRONTO PARA TESTE!\n";
    echo "Acesse: https://tem-de-tudo-9g7r.onrender.com/\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Verifique:\n";
    echo "1. Se o banco 'tem_de_tudo' existe\n";
    echo "2. Se as credenciais estão corretas\n";
    echo "3. Se as tabelas foram criadas\n";
}
?>