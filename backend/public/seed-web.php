<?php
/**
 * Interface Web para Popular Dados de Demonstração
 * Tema de Tudo - Sistema de Fidelidade
 * 
 * Acesse via navegador: /seed-web.php
 * Para popular o banco com dados fictícios de demonstração
 */

// Configurações de segurança
$allowedIPs = ['127.0.0.1', '::1', 'localhost']; // Apenas localhost por segurança
$currentIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Verificar se está sendo executado localmente (por segurança)
if (!in_array($currentIP, $allowedIPs) && !str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')) {
    die('❌ Este script só pode ser executado em ambiente local por segurança.');
}

$action = $_GET['action'] ?? '';
$status = '';
$error = '';

if ($action === 'populate') {
    try {
        // Incluir autoload se existir
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        // Carregar variáveis de ambiente se existir
        if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }
        
        // Configuração do banco (ajuste conforme necessário)
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_DATABASE'] ?? 'tem_de_tudo';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        
        // Conectar ao banco
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Hash da senha padrão: 123456
        $senhaHash = password_hash('123456', PASSWORD_DEFAULT);
        
        // Desabilitar checagem de chaves estrangeiras temporariamente
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Limpar dados existentes
        $pdo->exec("DELETE FROM pontos WHERE id > 0");
        $pdo->exec("DELETE FROM promocoes WHERE id > 0");  
        $pdo->exec("DELETE FROM notificacoes WHERE id > 0");
        $pdo->exec("DELETE FROM empresas WHERE id > 0");
        $pdo->exec("DELETE FROM users WHERE id > 0");
        
        // Inserir Admin
        $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, type, perfil, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())");
        $stmt->execute(['Carlos Eduardo Administrador', 'admin@temdetudo.com', $senhaHash, 'admin', 'admin']);
        $adminId = $pdo->lastInsertId();
        
        // Inserir Clientes
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
        
        // Inserir Empresas (usuários)
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
        
        // Inserir detalhes das empresas
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
        
        // Inserir pontos dos clientes
        $pontos = [
            [$clienteIds[0], $empresaIds[0], 50, 0, 50, 'Compra no restaurante - R$ 25,00', 'ganho', '-2 day'],
            [$clienteIds[0], $empresaIds[1], 30, 0, 30, 'Compra na farmácia - R$ 15,00', 'ganho', '-1 day'],
            [$clienteIds[0], $empresaIds[3], 100, 0, 100, 'Compra no supermercado - R$ 50,00', 'ganho', '0 day'],
            [$clienteIds[1], $empresaIds[2], 80, 0, 80, 'Abastecimento no posto - R$ 40,00', 'ganho', '-3 day'],
            [$clienteIds[1], $empresaIds[4], 60, 30, 30, 'Compra de roupas - R$ 30,00', 'ganho', '-2 day'],
            [$clienteIds[2], $empresaIds[0], 40, 0, 40, 'Almoço no restaurante - R$ 20,00', 'ganho', '-1 day'],
            [$clienteIds[2], $empresaIds[1], 25, 0, 25, 'Medicamentos - R$ 12,50', 'ganho', '0 day']
        ];
        
        foreach ($pontos as $ponto) {
            $data = date('Y-m-d H:i:s', strtotime($ponto[7]));
            $stmt = $pdo->prepare("INSERT INTO pontos (user_id, empresa_id, pontos_ganhos, pontos_utilizados, saldo_atual, descricao, tipo_transacao, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ponto[0], $ponto[1], $ponto[2], $ponto[3], $ponto[4], $ponto[5], $ponto[6], $data, $data]);
        }
        
        // Inserir promoções
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
        
        // Inserir notificações
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
        
        // Reabilitar checagem de chaves estrangeiras
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $status = 'success';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌱 Popular Dados - Tem de Tudo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #6F1AB6, #8B5CF6); color: white; min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .card { background: white; color: #333; padding: 2rem; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); margin-bottom: 2rem; }
        .status { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; font-weight: 600; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn { background: #6F1AB6; color: white; padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn:hover { background: #5A1494; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .info-item { background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; }
        .info-number { font-size: 1.5rem; font-weight: 700; color: #6F1AB6; }
        .info-label { font-size: 0.9rem; color: #6c757d; }
        .credentials { background: #e9ecef; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .credential { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .credential:last-child { margin-bottom: 0; }
        .credential strong { color: #495057; }
        .credential code { background: #6F1AB6; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; }
        .actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; }
        .warning-box { background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .warning-box strong { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Popular Dados de Demonstração</h1>
            <p>Interface Web para inserir dados fictícios no sistema</p>
        </div>

        <?php if ($status === 'success'): ?>
        <div class="status success">
            <i class="fas fa-check-circle"></i>
            <strong>✅ Dados inseridos com sucesso!</strong><br>
            O sistema agora possui dados completos para demonstração dos 3 perfis.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="status error">
            <i class="fas fa-exclamation-circle"></i>
            <strong>❌ Erro ao inserir dados:</strong><br>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($action !== 'populate'): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>⚠️ ATENÇÃO:</strong> Este processo irá <strong>APAGAR</strong> todos os dados existentes e inserir dados fictícios de demonstração. Use apenas em ambiente de desenvolvimento ou demonstração!
        </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-database"></i> Dados que serão inseridos:</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-number">1</div>
                    <div class="info-label">Administrador</div>
                </div>
                <div class="info-item">
                    <div class="info-number">5</div>
                    <div class="info-label">Clientes</div>
                </div>
                <div class="info-item">
                    <div class="info-number">5</div>
                    <div class="info-label">Empresas</div>
                </div>
                <div class="info-item">
                    <div class="info-number">180+</div>
                    <div class="info-label">Pontos</div>
                </div>
                <div class="info-item">
                    <div class="info-number">5</div>
                    <div class="info-label">Promoções</div>
                </div>
                <div class="info-item">
                    <div class="info-number">10+</div>
                    <div class="info-label">Notificações</div>
                </div>
            </div>

            <div class="credentials">
                <h3>🔑 Credenciais de Acesso:</h3>
                <div class="credential">
                    <span><strong>👨‍💼 Admin:</strong></span>
                    <span>admin@temdetudo.com / <code>123456</code></span>
                </div>
                <div class="credential">
                    <span><strong>👥 Cliente Principal:</strong></span>
                    <span>maria@email.com / <code>123456</code></span>
                </div>
                <div class="credential">
                    <span><strong>🏢 Empresa Principal:</strong></span>
                    <span>contato@sabordacasa.com / <code>123456</code></span>
                </div>
            </div>

            <div class="actions">
                <?php if ($action !== 'populate'): ?>
                <a href="?action=populate" class="btn" onclick="return confirm('⚠️ CONFIRMA que deseja APAGAR todos os dados existentes e inserir dados de demonstração?\\n\\nEsta ação não pode ser desfeita!')">
                    <i class="fas fa-play"></i>
                    Executar Inserção de Dados
                </a>
                <?php endif; ?>
                
                <a href="demo-dados.html" class="btn">
                    <i class="fas fa-info-circle"></i>
                    Ver Guia Completo
                </a>
                
                <a href="entrar.html" class="btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Testar Sistema
                </a>
            </div>
        </div>

        <?php if ($status === 'success'): ?>
        <div class="card">
            <h2><i class="fas fa-rocket"></i> Próximos Passos:</h2>
            <ol style="line-height: 2;">
                <li><strong>Teste o Admin:</strong> Entre com <code>admin@temdetudo.com</code> e explore o dashboard</li>
                <li><strong>Teste Cliente:</strong> Entre com <code>maria@email.com</code> e veja os pontos acumulados</li>
                <li><strong>Teste Empresa:</strong> Entre com <code>contato@sabordacasa.com</code> e veja as promoções</li>
                <li><strong>Demonstre:</strong> Mostre o fluxo completo para o cliente</li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>