<?php
/**
 * Script para Popular Banco com Dados de Demonstração
 * Tema de Tudo - Sistema de Fidelidade
 * 
 * Execute este arquivo para popular o banco com dados fictícios
 * para demonstração dos 3 perfis: Admin, Cliente, Empresa
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

try {
    // Conexão com banco de dados
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'tem_de_tudo';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔗 Conectado ao banco de dados: $dbname\n";
    
    // Hash da senha padrão: 123456
    $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
    
    echo "🧹 Limpando dados existentes (se houver)...\n";
    
    // Limpar dados existentes (opcional)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM pontos WHERE id > 0");
    $pdo->exec("DELETE FROM promocoes WHERE id > 0");
    $pdo->exec("DELETE FROM notificacoes WHERE id > 0");
    $pdo->exec("DELETE FROM empresas WHERE id > 0");
    $pdo->exec("DELETE FROM users WHERE id > 0");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "👨‍💼 Inserindo ADMINISTRADOR...\n";
    
    // 1. ADMINISTRADOR
    $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())");
    $stmt->execute(['Carlos Eduardo Administrador', 'admin@temdetudo.com', $senhaHash, 'admin', 'admin']);
    $adminId = $pdo->lastInsertId();
    
    echo "👥 Inserindo CLIENTES DE EXEMPLO...\n";
    
    // 2. CLIENTES DE EXEMPLO
    $clientes = [
        ['Maria Silva Santos', 'maria@email.com', '(11) 99999-0001'],
        ['João Pedro Oliveira', 'joao@email.com', '(11) 99999-0002'],
        ['Ana Carolina Lima', 'ana@email.com', '(11) 99999-0003'],
        ['Roberto Costa Silva', 'roberto@email.com', '(11) 99999-0004'],
        ['Patricia Fernandes', 'patricia@email.com', '(11) 99999-0005']
    ];
    
    $clienteIds = [];
    foreach ($clientes as $cliente) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$cliente[0], $cliente[1], $senhaHash, 'cliente', 'cliente', $cliente[2]]);
        $clienteIds[] = $pdo->lastInsertId();
    }
    
    echo "🏢 Inserindo EMPRESAS DE EXEMPLO...\n";
    
    // 3. EMPRESAS DE EXEMPLO
    $empresas = [
        ['Restaurante Sabor da Casa', 'contato@sabordacasa.com', '(11) 3333-0001'],
        ['Farmácia São João', 'contato@farmaciajoao.com', '(11) 3333-0002'],
        ['Posto Shell Centro', 'contato@shellcentro.com', '(11) 3333-0003'],
        ['Supermercado Família', 'contato@superfamilia.com', '(11) 3333-0004'],
        ['Loja de Roupas Fashion', 'contato@fashionloja.com', '(11) 3333-0005']
    ];
    
    $empresaUserIds = [];
    foreach ($empresas as $empresa) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, telefone, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$empresa[0], $empresa[1], $senhaHash, 'empresa', 'empresa', $empresa[2]]);
        $empresaUserIds[] = $pdo->lastInsertId();
    }
    
    // 4. EMPRESAS DETALHADAS
    $empresasDetalhes = [
        [$empresaUserIds[0], 'Restaurante Sabor da Casa', '12.345.678/0001-01', 'Restaurantes', 'Rua das Flores, 123', 'São Paulo', 'SP', '01234-567', 'Restaurante familiar com pratos caseiros desde 1985'],
        [$empresaUserIds[1], 'Farmácia São João', '23.456.789/0001-02', 'Farmácias', 'Av. Paulista, 456', 'São Paulo', 'SP', '01310-100', 'Farmácia completa com atendimento 24h'],
        [$empresaUserIds[2], 'Posto Shell Centro', '34.567.890/0001-03', 'Postos de Combustível', 'Rua do Comércio, 789', 'São Paulo', 'SP', '01020-000', 'Posto de combustível com conveniência completa'],
        [$empresaUserIds[3], 'Supermercado Família', '45.678.901/0001-04', 'Supermercados', 'Rua do Mercado, 321', 'São Paulo', 'SP', '05432-019', 'Supermercado com produtos frescos e ofertas diárias'],
        [$empresaUserIds[4], 'Loja de Roupas Fashion', '56.789.012/0001-05', 'Moda e Vestuário', 'Shopping Center, Loja 205', 'São Paulo', 'SP', '04567-890', 'Moda jovem e tendências atuais']
    ];
    
    $empresaIds = [];
    foreach ($empresasDetalhes as $empresa) {
        $stmt = $pdo->prepare("INSERT INTO empresas (user_id, nome_empresa, cnpj, categoria, endereco, cidade, estado, cep, descricao, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())");
        $stmt->execute($empresa);
        $empresaIds[] = $pdo->lastInsertId();
    }
    
    echo "⭐ Inserindo PONTOS DOS CLIENTES...\n";
    
    // 5. PONTOS DOS CLIENTES (EXEMPLO)
    $pontos = [
        // Maria Silva Santos (clienteIds[0])
        [$clienteIds[0], $empresaIds[0], 50, 0, 50, 'Compra no restaurante - R$ 25,00', 'ganho', '-2 day'],
        [$clienteIds[0], $empresaIds[1], 30, 0, 30, 'Compra na farmácia - R$ 15,00', 'ganho', '-1 day'],
        [$clienteIds[0], $empresaIds[3], 100, 0, 100, 'Compra no supermercado - R$ 50,00', 'ganho', '0 day'],
        
        // João Pedro Oliveira (clienteIds[1])
        [$clienteIds[1], $empresaIds[2], 80, 0, 80, 'Abastecimento no posto - R$ 40,00', 'ganho', '-3 day'],
        [$clienteIds[1], $empresaIds[4], 60, 30, 30, 'Compra de roupas - R$ 30,00', 'ganho', '-2 day'],
        
        // Ana Carolina Lima (clienteIds[2])
        [$clienteIds[2], $empresaIds[0], 40, 0, 40, 'Almoço no restaurante - R$ 20,00', 'ganho', '-1 day'],
        [$clienteIds[2], $empresaIds[1], 25, 0, 25, 'Medicamentos - R$ 12,50', 'ganho', '0 day']
    ];
    
    foreach ($pontos as $ponto) {
        $data = date('Y-m-d H:i:s', strtotime($ponto[7]));
        $stmt = $pdo->prepare("INSERT INTO pontos (user_id, empresa_id, pontos_ganhos, pontos_utilizados, saldo_atual, descricao, tipo_transacao, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ponto[0], $ponto[1], $ponto[2], $ponto[3], $ponto[4], $ponto[5], $ponto[6], $data, $data]);
    }
    
    echo "🎁 Inserindo PROMOÇÕES ATIVAS...\n";
    
    // 6. PROMOÇÕES ATIVAS
    $promocoes = [
        [$empresaIds[0], '10% OFF Almoço Executivo', 'Desconto de 10% no almoço executivo de segunda a sexta', 50, 10, 0, '+30 day'],
        [$empresaIds[1], 'R$ 5 OFF Medicamentos', 'R$ 5,00 de desconto em medicamentos acima de R$ 20,00', 25, 0, 5.00, '+15 day'],
        [$empresaIds[2], 'Combustível com Desconto', '5% de desconto no combustível', 100, 5, 0, '+7 day'],
        [$empresaIds[3], '15% OFF Compras acima de R$ 50', 'Desconto de 15% em compras acima de R$ 50,00', 75, 15, 0, '+20 day'],
        [$empresaIds[4], 'R$ 10 OFF Roupas', 'R$ 10,00 de desconto em roupas acima de R$ 80,00', 60, 0, 10.00, '+25 day']
    ];
    
    foreach ($promocoes as $promocao) {
        $validoAte = date('Y-m-d H:i:s', strtotime($promocao[6]));
        $stmt = $pdo->prepare("INSERT INTO promocoes (empresa_id, titulo, descricao, pontos_necessarios, desconto_porcentagem, desconto_valor, valido_ate, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', NOW(), NOW())");
        $stmt->execute([$promocao[0], $promocao[1], $promocao[2], $promocao[3], $promocao[4], $promocao[5], $validoAte]);
    }
    
    echo "🔔 Inserindo NOTIFICAÇÕES...\n";
    
    // 7. NOTIFICAÇÕES PARA DEMONSTRAÇÃO
    $notificacoes = [
        [$clienteIds[0], 'Bem-vindo ao Tem de Tudo!', 'Sua conta foi criada com sucesso! Comece a acumular pontos agora.', 'boas-vindas', 0, '0 day'],
        [$clienteIds[0], 'Pontos Ganhos!', 'Você ganhou 50 pontos no Restaurante Sabor da Casa', 'pontos', 0, '-2 day'],
        [$clienteIds[1], 'Promoção Especial!', 'Nova promoção no Posto Shell Centro: 5% de desconto!', 'promocao', 1, '-1 day'],
        [$clienteIds[2], 'Lembrete', 'Você tem pontos acumulados! Que tal usar em alguma promoção?', 'lembrete', 0, '0 day']
    ];
    
    foreach ($notificacoes as $notif) {
        $data = date('Y-m-d H:i:s', strtotime($notif[5]));
        $stmt = $pdo->prepare("INSERT INTO notificacoes (user_id, titulo, mensagem, tipo, lida, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$notif[0], $notif[1], $notif[2], $notif[3], $notif[4], $data, $data]);
    }
    
    echo "⚙️ Inserindo CONFIGURAÇÕES...\n";
    
    // 8. CONFIGURAÇÕES DE PONTUAÇÃO
    $configuracoes = [
        ['pontos_por_real', '2', 'Quantos pontos são ganhos por real gasto'],
        ['pontos_minimos_uso', '25', 'Quantidade mínima de pontos para usar'],
        ['valor_ponto', '0.50', 'Valor em reais de cada ponto'],
        ['bonus_cadastro', '100', 'Pontos de bônus ao se cadastrar']
    ];
    
    foreach ($configuracoes as $config) {
        $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, descricao, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()");
        $stmt->execute($config);
    }
    
    echo "\n✅ DADOS DE DEMONSTRAÇÃO INSERIDOS COM SUCESSO!\n\n";
    echo "🔑 CREDENCIAIS PARA TESTE:\n";
    echo "========================================\n";
    echo "👨‍💼 ADMINISTRADOR:\n";
    echo "   Email: admin@temdetudo.com\n";
    echo "   Senha: 123456\n\n";
    
    echo "👥 CLIENTES:\n";
    foreach ($clientes as $cliente) {
        echo "   Email: {$cliente[1]} - Senha: 123456\n";
    }
    echo "\n";
    
    echo "🏢 EMPRESAS:\n";
    foreach ($empresas as $empresa) {
        echo "   Email: {$empresa[1]} - Senha: 123456\n";
    }
    echo "\n";
    
    // Verificar dados inseridos
    $queries = [
        'USUÁRIOS' => "SELECT COUNT(*) as total FROM users",
        'EMPRESAS' => "SELECT COUNT(*) as total FROM empresas",
        'PONTOS' => "SELECT COUNT(*) as total FROM pontos", 
        'PROMOÇÕES' => "SELECT COUNT(*) as total FROM promocoes WHERE status = 'ativo'",
        'NOTIFICAÇÕES' => "SELECT COUNT(*) as total FROM notificacoes"
    ];
    
    echo "📊 RESUMO DOS DADOS:\n";
    echo "========================================\n";
    foreach ($queries as $nome => $query) {
        $result = $pdo->query($query)->fetch();
        echo "$nome: {$result['total']}\n";
    }
    
    echo "\n🚀 SISTEMA PRONTO PARA DEMONSTRAÇÃO!\n";
    echo "Acesse: http://seu-dominio.com/entrar.html\n";
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão com banco: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    exit(1);
}
?>