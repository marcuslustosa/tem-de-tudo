<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 SEED MASSIVO - SISTEMA COMPLETO\n";
echo "==================================\n\n";

try {
    // LIMPAR DADOS EXISTENTES
    echo "🗑️  Limpando dados antigos...\n";
    DB::table('avaliacoes')->delete();
    DB::table('pontos')->delete();
    DB::table('qr_codes')->delete();
    DB::table('promocoes')->delete();
    DB::table('empresas')->delete();
    DB::table('users')->delete();
    echo "✅ Dados limpos!\n\n";
    
    // CRIAR 3 ADMINS
    echo "👨‍💼 Criando administradores...\n";
    $admins = [];
    $adminData = [
        ['name' => 'Admin Master', 'email' => 'admin@sistema.com'],
        ['name' => 'Admin Suporte', 'email' => 'suporte@sistema.com'],
        ['name' => 'Admin Gestor', 'email' => 'gestor@sistema.com']
    ];
    
    foreach ($adminData as $admin) {
        $adminId = DB::table('users')->insertGetId([
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => Hash::make('admin123'),
            'perfil' => 'admin',
            'telefone' => '(11) 3000-0000',
            'pontos' => 0,
            'status' => 'ativo',
            'created_at' => now()->subDays(365),
            'updated_at' => now()
        ]);
        $admins[] = $adminId;
    }
    echo "✅ 3 administradores criados!\n\n";

    // CRIAR 50 CLIENTES
    echo "👥 Criando 50 clientes...\n";
    $nomes = ['Ana', 'Bruno', 'Carlos', 'Diana', 'Eduardo', 'Fernanda', 'Gabriel', 'Helena', 'Igor', 'Julia'];
    $sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Costa', 'Pereira', 'Rodrigues', 'Almeida', 'Nascimento', 'Lima'];
    
    $clientes = [];
    for ($i = 1; $i <= 50; $i++) {
        $nome = $nomes[array_rand($nomes)] . ' ' . $sobrenomes[array_rand($sobrenomes)];
        $email = 'cliente' . $i . '@email.com';
        
        $clienteId = DB::table('users')->insertGetId([
            'name' => $nome,
            'email' => $email,
            'password' => Hash::make('senha123'),
            'perfil' => 'cliente',
            'telefone' => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
            'pontos' => 0, // Será calculado depois
            'status' => 'ativo',
            'data_nascimento' => date('Y-m-d', strtotime('-' . rand(18, 60) . ' years')),
            'created_at' => now()->subDays(rand(1, 180)),
            'updated_at' => now()
        ]);
        
        $clientes[] = $clienteId;
    }
    echo "✅ 50 clientes criados!\n\n";

    // CRIAR 20 EMPRESAS
    echo "🏢 Criando 20 empresas...\n";
    $empresasData = [
        ['nome' => 'Restaurante Sabor da Terra', 'ramo' => 'restaurante', 'multiplier' => 1.5, 'foto' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400'],
        ['nome' => 'Academia FitLife', 'ramo' => 'academia', 'multiplier' => 2.0, 'foto' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400'],
        ['nome' => 'Café Aroma & Sabor', 'ramo' => 'cafeteria', 'multiplier' => 1.2, 'foto' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400'],
        ['nome' => 'Pet Shop Bicho Feliz', 'ramo' => 'pet_shop', 'multiplier' => 1.3, 'foto' => 'https://images.unsplash.com/photo-1548681528-6a5c45b66b42?w=400'],
        ['nome' => 'Salão Beleza Pura', 'ramo' => 'salao', 'multiplier' => 1.8, 'foto' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400'],
        ['nome' => 'Mercado Bom Preço', 'ramo' => 'mercado', 'multiplier' => 1.0, 'foto' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=400'],
        ['nome' => 'Farmácia Saúde Total', 'ramo' => 'farmacia', 'multiplier' => 1.4, 'foto' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=400'],
        ['nome' => 'Pizzaria Bella Napoli', 'ramo' => 'restaurante', 'multiplier' => 1.5, 'foto' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400'],
        ['nome' => 'Churrascaria Boi na Brasa', 'ramo' => 'restaurante', 'multiplier' => 1.6, 'foto' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400'],
        ['nome' => 'Hamburgueria Top Burger', 'ramo' => 'restaurante', 'multiplier' => 1.3, 'foto' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400'],
        ['nome' => 'Sushi Bar Sakura', 'ramo' => 'restaurante', 'multiplier' => 1.7, 'foto' => 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=400'],
        ['nome' => 'Padaria Pão Quente', 'ramo' => 'padaria', 'multiplier' => 1.1, 'foto' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400'],
        ['nome' => 'Lanchonete da Esquina', 'ramo' => 'lanchonete', 'multiplier' => 1.2, 'foto' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400'],
        ['nome' => 'Sorveteria Gelato Italiano', 'ramo' => 'sorveteria', 'multiplier' => 1.4, 'foto' => 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400'],
        ['nome' => 'Açaí & Cia', 'ramo' => 'sorveteria', 'multiplier' => 1.3, 'foto' => 'https://images.unsplash.com/photo-1488900128323-21503983a07e?w=400'],
        ['nome' => 'Lavanderia Express Clean', 'ramo' => 'lavanderia', 'multiplier' => 1.2, 'foto' => 'https://images.unsplash.com/photo-1582735689369-4fe89db7114c?w=400'],
        ['nome' => 'Auto Center Speed', 'ramo' => 'auto_center', 'multiplier' => 1.8, 'foto' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=400'],
        ['nome' => 'Ótica Visão Clara', 'ramo' => 'otica', 'multiplier' => 1.5, 'foto' => 'https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=400'],
        ['nome' => 'Livraria Ler & Saber', 'ramo' => 'livraria', 'multiplier' => 1.3, 'foto' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400'],
        ['nome' => 'Papelaria Office Plus', 'ramo' => 'papelaria', 'multiplier' => 1.1, 'foto' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?w=400']
    ];
    
    $empresas = [];
    foreach ($empresasData as $index => $emp) {
        $ownerId = DB::table('users')->insertGetId([
            'name' => $emp['nome'],
            'email' => 'empresa' . ($index + 1) . '@email.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'empresa',
            'telefone' => '(11) 3' . rand(100, 999) . '-' . rand(1000, 9999),
            'status' => 'ativo',
            'created_at' => now()->subDays(rand(30, 365)),
            'updated_at' => now()
        ]);
        
        $empresaId = DB::table('empresas')->insertGetId([
            'owner_id' => $ownerId,
            'nome' => $emp['nome'],
            'cnpj' => sprintf('%02d.%03d.%03d/%04d-%02d', rand(10,99), rand(100,999), rand(100,999), rand(1000,9999), rand(10,99)),
            'ramo' => $emp['ramo'],
            'endereco' => 'Rua ' . chr(rand(65, 90)) . ', ' . rand(100, 9999) . ' - São Paulo, SP',
            'telefone' => '(11) 3' . rand(100, 999) . '-' . rand(1000, 9999),
            'descricao' => 'Estabelecimento de qualidade em ' . $emp['ramo'],
            'logo' => $emp['foto'],
            'ativo' => DB::raw('TRUE'),
            'avaliacao_media' => 0,
            'total_avaliacoes' => 0,
            'points_multiplier' => $emp['multiplier'],
            'created_at' => now()->subDays(rand(30, 365)),
            'updated_at' => now()
        ]);
        
        $empresas[] = [
            'id' => $empresaId,
            'nome' => $emp['nome'],
            'multiplier' => $emp['multiplier']
        ];
    }
    echo "✅ 20 empresas criadas!\n\n";

    // CRIAR QR CODES (3 por empresa)
    echo "📱 Criando QR Codes...\n";
    $locations = ['Entrada', 'Caixa', 'Balcão', 'Recepção', 'Atendimento'];
    foreach ($empresas as $empresa) {
        for ($i = 0; $i < 3; $i++) {
            DB::table('qr_codes')->insert([
                'empresa_id' => $empresa['id'],
                'name' => 'QR Code ' . $locations[$i],
                'code' => 'EMP' . $empresa['id'] . '_' . strtoupper($locations[$i]),
                'location' => $locations[$i],
                'active' => DB::raw('TRUE'),
                'active_offers' => '[]',
                'usage_count' => rand(50, 500),
                'last_used_at' => now()->subDays(rand(0, 7)),
                'created_at' => now()->subDays(rand(10, 100)),
                'updated_at' => now()
            ]);
        }
    }
    echo "✅ 60 QR Codes criados!\n\n";

    // CRIAR PROMOÇÕES (2-4 por empresa)
    echo "🎁 Criando promoções...\n";
    $titulosPromocoes = [
        '10% de desconto',
        '15% OFF',
        '20% de desconto',
        '25% OFF na primeira compra',
        '30% de desconto',
        'Compre 1 leve 2',
        '50% OFF em produtos selecionados',
        'Frete grátis',
        'Desconto especial'
    ];
    
    $totalPromocoes = 0;
    foreach ($empresas as $empresa) {
        $numPromocoes = rand(2, 4);
        for ($i = 0; $i < $numPromocoes; $i++) {
            $desconto = [10, 15, 20, 25, 30, 40, 50][array_rand([10, 15, 20, 25, 30, 40, 50])];
            
            DB::table('promocoes')->insert([
                'empresa_id' => $empresa['id'],
                'titulo' => $desconto . '% de desconto',
                'descricao' => 'Promoção válida para todos os produtos/serviços',
                'desconto' => $desconto,
                'imagem' => 'promocao_' . rand(1, 10) . '.jpg',
                'data_inicio' => now()->subDays(rand(1, 30)),
                'ativo' => rand(0, 10) > 2 ? DB::raw('TRUE') : DB::raw('FALSE'),
                'status' => 'ativa',
                'visualizacoes' => rand(50, 500),
                'resgates' => rand(5, 50),
                'usos' => rand(10, 100),
                'created_at' => now()->subDays(rand(5, 60)),
                'updated_at' => now()
            ]);
            $totalPromocoes++;
        }
    }
    echo "✅ {$totalPromocoes} promoções criadas!\n\n";

    // CRIAR TRANSAÇÕES DE PONTOS (muito ativas)
    echo "💰 Criando transações de pontos...\n";
    $totalTransacoes = 0;
    
    foreach ($clientes as $clienteId) {
        // Cada cliente interage com 3-8 empresas
        $empresasAleatorias = array_rand($empresas, rand(3, 8));
        if (!is_array($empresasAleatorias)) $empresasAleatorias = [$empresasAleatorias];
        
        foreach ($empresasAleatorias as $empIndex) {
            $empresa = $empresas[$empIndex];
            
            // Múltiplas visitas (5-20 por empresa)
            $numVisitas = rand(5, 20);
            for ($v = 0; $v < $numVisitas; $v++) {
                $pontosBase = 100;
                $pontosGanhos = $pontosBase * $empresa['multiplier'];
                
                DB::table('pontos')->insert([
                    'user_id' => $clienteId,
                    'empresa_id' => $empresa['id'],
                    'pontos' => $pontosGanhos,
                    'tipo' => 'ganho',
                    'descricao' => 'QR Code escaneado - ' . $empresa['nome'],
                    'created_at' => now()->subDays(rand(0, 90)),
                    'updated_at' => now()
                ]);
                $totalTransacoes++;
            }
            
            // Alguns resgates
            if (rand(0, 10) > 5) {
                $numResgates = rand(1, 3);
                for ($r = 0; $r < $numResgates; $r++) {
                    DB::table('pontos')->insert([
                        'user_id' => $clienteId,
                        'empresa_id' => $empresa['id'],
                        'pontos' => rand(100, 500),
                        'tipo' => 'resgate',
                        'descricao' => 'Promoção resgatada',
                        'created_at' => now()->subDays(rand(0, 60)),
                        'updated_at' => now()
                    ]);
                    $totalTransacoes++;
                }
            }
        }
    }
    echo "✅ {$totalTransacoes} transações criadas!\n\n";

    // ATUALIZAR SALDO DOS CLIENTES
    echo "💳 Calculando saldos...\n";
    foreach ($clientes as $clienteId) {
        $totalGanho = DB::table('pontos')
            ->where('user_id', $clienteId)
            ->where('tipo', 'ganho')
            ->sum('pontos');
        
        $totalGasto = DB::table('pontos')
            ->where('user_id', $clienteId)
            ->where('tipo', 'resgate')
            ->sum('pontos');
        
        DB::table('users')
            ->where('id', $clienteId)
            ->update(['pontos' => $totalGanho - $totalGasto]);
    }
    echo "✅ Saldos calculados!\n\n";

    // CRIAR AVALIAÇÕES
    echo "⭐ Criando avaliações...\n";
    $totalAvaliacoes = 0;
    
    foreach ($empresas as $empresa) {
        // 10-30 avaliações por empresa
        $numAvaliacoes = rand(10, 30);
        $clientesAleatorios = (array)array_rand(array_flip($clientes), min($numAvaliacoes, count($clientes)));
        
        $somaEstrelas = 0;
        foreach ($clientesAleatorios as $clienteId) {
            $estrelas = rand(3, 5); // Maioria boas
            $somaEstrelas += $estrelas;
            
            $comentarios = [
                'Excelente atendimento!',
                'Muito bom, recomendo!',
                'Ótimo lugar, voltarei sempre.',
                'Atendimento impecável.',
                'Adorei a experiência!',
                'Perfeito, sem reclamações.',
                'Muito bom, mas pode melhorar.',
                'Legal, mas esperava mais.',
                'Bom, mas nada excepcional.'
            ];
            
            DB::table('avaliacoes')->insert([
                'user_id' => $clienteId,
                'empresa_id' => $empresa['id'],
                'estrelas' => $estrelas,
                'comentario' => $comentarios[array_rand($comentarios)],
                'created_at' => now()->subDays(rand(0, 120)),
                'updated_at' => now()
            ]);
            $totalAvaliacoes++;
        }
        
        // Atualizar média da empresa
        $media = $somaEstrelas / $numAvaliacoes;
        DB::table('empresas')
            ->where('id', $empresa['id'])
            ->update([
                'avaliacao_media' => round($media, 1),
                'total_avaliacoes' => $numAvaliacoes
            ]);
    }
    echo "✅ {$totalAvaliacoes} avaliações criadas!\n\n";

    // RESUMO FINAL
    echo "\n";
    echo "╔══════════════════════════════════════════╗\n";
    echo "║       🎉 SEED CONCLUÍDO COM SUCESSO!     ║\n";
    echo "╚══════════════════════════════════════════╝\n\n";
    
    echo "📊 RESUMO:\n";
    echo "   �‍💼 3 administradores\n";
    echo "   👥 50 clientes\n";
    echo "   🏢 20 empresas\n";
    echo "   📱 60 QR Codes\n";
    echo "   🎁 {$totalPromocoes} promoções\n";
    echo "   💰 {$totalTransacoes} transações\n";
    echo "   ⭐ {$totalAvaliacoes} avaliações\n\n";
    
    echo "🔑 CREDENCIAIS:\n";
    echo "   👨‍💼 ADMIN:\n";
    echo "      admin@sistema.com / admin123\n";
    echo "      suporte@sistema.com / admin123\n";
    echo "      gestor@sistema.com / admin123\n\n";
    echo "   👥 CLIENTES:\n";
    echo "      cliente1@email.com até cliente50@email.com\n";
    echo "      Senha: senha123\n\n";
    echo "   🏢 EMPRESAS:\n";
    echo "      empresa1@email.com até empresa20@email.com\n";
    echo "      Senha: senha123\n\n";
    
    echo "✅ Sistema totalmente populado e pronto para uso!\n\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
