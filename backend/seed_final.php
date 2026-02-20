<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "🌱 POPULANDO BANCO TEM DE TUDO COM DADOS FICTÍCIOS\n";
echo "===============================================\n\n";

// Limpar dados existentes
echo "🗑️  Limpando dados antigos...\n";
DB::table('avaliacoes')->delete();
DB::table('pontos')->delete();
DB::table('check_ins')->delete();
DB::table('qr_codes')->delete();
DB::table('promocoes')->delete();
DB::table('empresas')->delete();
DB::table('users')->where('email', '!=', 'admin@temdetudo.com.br')->delete();
echo "✅ Dados limpos!\n\n";

// 1. CRIAR USUÁRIOS CLIENTES
echo "👥 Criando clientes...\n";

$clientes = [
    ['name' => 'Maria Silva Santos', 'email' => 'maria.silva@temdetudo.com.br', 'telefone' => '11987654321'],
    ['name' => 'João Pedro Costa', 'email' => 'joao.pedro@temdetudo.com.br', 'telefone' => '11987654322'],
    ['name' => 'Ana Carolina Lima', 'email' => 'ana.carolina@temdetudo.com.br', 'telefone' => '11987654323'],
    ['name' => 'Pedro Henrique Souza', 'email' => 'pedro.henrique@temdetudo.com.br', 'telefone' => '11987654324'],
    ['name' => 'Julia Fernanda Alves', 'email' => 'julia.fernanda@temdetudo.com.br', 'telefone' => '11987654325'],
];

$clientesIds = [];
foreach ($clientes as $cliente) {
    $id = DB::table('users')->insertGetId([
        'name' => $cliente['name'],
        'email' => $cliente['email'],
        'password' => Hash::make('senha123'),
        'perfil' => 'cliente',
        'telefone' => $cliente['telefone'],
        'pontos' => rand(100, 5000),
        'status' => 'ativo',
        'created_at' => now()->subDays(rand(1, 60)),
        'updated_at' => now()
    ]);
    $clientesIds[] = $id;
    echo "  ✅ {$cliente['name']} (ID: {$id}, Pontos: " . DB::table('users')->where('id', $id)->value('pontos') . ")\n";
}

echo "\n";

// 2. CRIAR EMPRESAS
echo "🏢 Criando empresas...\n";

$empresasData = [
    ['nome' => 'Restaurante Sabor da Terra', 'email' => 'contato@sabordaterra.com', 'cnpj' => '12345678000101', 'telefone' => '1133334444', 'ramo' => 'Restaurante'],
    ['nome' => 'Academia FitLife', 'email' => 'contato@fitlife.com', 'cnpj' => '12345678000102', 'telefone' => '1133334445', 'ramo' => 'Academia'],
    ['nome' => 'Café Aroma & Sabor', 'email' => 'contato@aromesabor.com', 'cnpj' => '12345678000103', 'telefone' => '1133334446', 'ramo' => 'Cafeteria'],
    ['nome' => 'Pet Shop Bicho Feliz', 'email' => 'contato@bichofeliz.com', 'cnpj' => '12345678000104', 'telefone' => '1133334447', 'ramo' => 'Pet Shop'],
    ['nome' => 'Salão Beleza Pura', 'email' => 'contato@belezapura.com', 'cnpj' => '12345678000105', 'telefone' => '1133334448', 'ramo' => 'Salão de Beleza'],
    ['nome' => 'Mercado Bom Preço', 'email' => 'contato@bompreco.com', 'cnpj' => '12345678000106', 'telefone' => '1133334449', 'ramo' => 'Supermercado'],
    ['nome' => 'Farmácia Saúde Total', 'email' => 'contato@saudetotal.com', 'cnpj' => '12345678000107', 'telefone' => '1133334450', 'ramo' => 'Farmácia'],
];

$empresasIds = [];
foreach ($empresasData as $empresa) {
    // Criar usuário empresa
    $userId = DB::table('users')->insertGetId([
        'name' => $empresa['nome'],
        'email' => $empresa['email'],
        'password' => Hash::make('senha123'),
        'perfil' => 'empresa',
        'telefone' => $empresa['telefone'],
        'status' => 'ativo',
        'created_at' => now()->subDays(rand(30, 90)),
        'updated_at' => now()
    ]);

    // Criar empresa
    $empresaId = DB::table('empresas')->insertGetId([
        'owner_id' => $userId,
        'nome' => $empresa['nome'],
        'cnpj' => $empresa['cnpj'],
        'telefone' => $empresa['telefone'],
        'endereco' => 'Rua ' . $empresa['nome'] . ', 123 - São Paulo - SP',
        'ramo' => $empresa['ramo'],
        'descricao' => 'Descrição da empresa ' . $empresa['nome'],
        'points_multiplier' => rand(1, 3),
        'avaliacao_media' => (string)(rand(35, 50) / 10),
        'total_avaliacoes' => rand(10, 100),
        'ativo' => DB::raw('TRUE'),
        'created_at' => now()->subDays(rand(30, 90)),
        'updated_at' => now()
    ]);

    $empresasIds[] = ['user_id' => $userId, 'empresa_id' => $empresaId];
    echo "  ✅ {$empresa['nome']} (ID: {$empresaId})\n";
}

echo "\n";

// 3. CRIAR PROMOÇÕES
echo "🎁 Criando promoções...\n";

$promocoes = [
    ['titulo' => 'Desconto 20% no Almoço', 'desconto' => 20],
    ['titulo' => 'Café Grátis na Compra', 'desconto' => 100],
    ['titulo' => 'Sobremesa Cortesia', 'desconto' => 0],
    ['titulo' => '1 Mês Grátis Academia', 'desconto' => 100],
    ['titulo' => 'Corte + Barba R$ 30', 'desconto' => 50],
    ['titulo' => '10% em Compras Acima R$ 100', 'desconto' => 10],
    ['titulo' => 'Frete Grátis', 'desconto' => 100],
];

foreach ($empresasIds as $index => $empresa) {
    if ($index < count($promocoes)) {
        $promo = $promocoes[$index];
        DB::table('promocoes')->insert([
            'empresa_id' => $empresa['empresa_id'],
            'titulo' => $promo['titulo'],
            'descricao' => 'Aproveite esta oferta exclusiva! Válido por tempo limitado.',
            'imagem' => 'promocao_' . ($index + 1) . '.jpg',
            'desconto' => $promo['desconto'],
            'data_inicio' => now(),
            'ativo' => DB::raw('TRUE'),
            'status' => 'ativa',
            'visualizacoes' => rand(50, 500),
            'resgates' => rand(5, 50),
            'usos' => rand(10, 100),
            'created_at' => now()->subDays(rand(1, 15)),
            'updated_at' => now()
        ]);
        echo "  ✅ {$promo['titulo']}\n";
    }
}

echo "\n";

// 4. CRIAR TRANSAÇÕES DE PONTOS
echo "💰 Criando transações de pontos...\n";

$totalTransacoes = 0;
foreach ($clientesIds as $clienteId) {
    $numTransacoes = rand(8, 20);
    for ($i = 0; $i < $numTransacoes; $i++) {
        $empresa = $empresasIds[array_rand($empresasIds)];
        $tipo = rand(0, 2) > 0 ? 'ganho' : 'resgate'; // Mais ganhos que resgates
        $pontos = $tipo === 'ganho' ? rand(50, 500) : rand(100, 1000);
        
        DB::table('pontos')->insert([
            'user_id' => $clienteId,
            'empresa_id' => $empresa['empresa_id'],
            'tipo' => $tipo,
            'pontos' => $pontos,
            'descricao' => $tipo === 'ganho' ? 'Compra realizada' : 'Resgate de benefício',
            'created_at' => now()->subDays(rand(1, 60)),
            'updated_at' => now()
        ]);
        $totalTransacoes++;
    }
}
echo "  ✅ {$totalTransacoes} transações criadas\n\n";

// 5. CRIAR QR CODES
echo "📱 Criando QR Codes...\n";

foreach ($empresasIds as $empresa) {
    for ($i = 1; $i <= 3; $i++) {
        $code = strtoupper(Str::random(16));
        DB::table('qr_codes')->insert([
            'empresa_id' => $empresa['empresa_id'],
            'name' => 'QR Code ' . $i,
            'code' => $code,
            'location' => $i === 1 ? 'Entrada Principal' : ($i === 2 ? 'Caixa' : 'Balcão'),
            'active' => DB::raw('TRUE'),
            'active_offers' => '[]',
            'usage_count' => rand(10, 500),
            'last_used_at' => now()->subHours(rand(1, 72)),
            'created_at' => now()->subDays(rand(1, 30)),
            'updated_at' => now()
        ]);
    }
}
echo "  ✅ " . (count($empresasIds) * 3) . " QR Codes criados\n\n";

// 6. CRIAR AVALIAÇÕES
echo "⭐ Criando avaliações...\n";

$comentarios = [
    'Excelente atendimento! Muito satisfeito.',
    'Ótima experiência, recomendo!',
    'Bom serviço, preço justo.',
    'Adorei! Voltarei mais vezes.',
    'Qualidade excepcional!',
    'Ambiente agradável e limpo.',
    'Atendimento rápido e eficiente.',
    'Superou minhas expectativas!',
    'Muito bom, vale a pena!',
    'Produto de qualidade!',
];

$totalAvaliacoes = 0;
foreach ($empresasIds as $empresa) {
    // Cada cliente avalia uma vez cada empresa (sem repetir)
    $clientesParaAvaliar = $clientesIds;
    shuffle($clientesParaAvaliar);
    $numAvaliacoes = min(rand(3, 5), count($clientesParaAvaliar));
    
    for ($i = 0; $i < $numAvaliacoes; $i++) {
        $clienteId = $clientesParaAvaliar[$i];
        DB::table('avaliacoes')->insert([
            'empresa_id' => $empresa['empresa_id'],
            'user_id' => $clienteId,
            'estrelas' => rand(3, 5),
            'comentario' => $comentarios[array_rand($comentarios)],
            'created_at' => now()->subDays(rand(1, 60)),
            'updated_at' => now()
        ]);
        $totalAvaliacoes++;
    }
}
echo "  ✅ {$totalAvaliacoes} avaliações criadas\n\n";

// RESUMO FINAL
echo "📊 RESUMO FINAL\n";
echo "===============\n";
echo "✅ " . count($clientes) . " clientes criados\n";
echo "✅ " . count($empresasData) . " empresas criadas\n";
echo "✅ " . count($promocoes) . " promoções criadas\n";
echo "✅ {$totalTransacoes} transações criadas\n";
echo "✅ " . (count($empresasIds) * 3) . " QR Codes criados\n";
echo "✅ {$totalAvaliacoes} avaliações criadas\n\n";

echo "🎉 BANCO POPULADO COM SUCESSO!\n\n";

echo "📝 CREDENCIAIS PARA TESTE:\n";
echo "==========================\n\n";

echo "👤 ADMIN:\n";
echo "   Email: admin@temdetudo.com\n";
echo "   Senha: admin123\n\n";

echo "👥 CLIENTES:\n";
foreach ($clientes as $cliente) {
    echo "   {$cliente['name']}: {$cliente['email']} / senha123\n";
}
echo "\n";

echo "🏢 EMPRESAS:\n";
foreach ($empresasData as $empresa) {
    echo "   {$empresa['nome']}: {$empresa['email']} / senha123\n";
}
echo "\n";
