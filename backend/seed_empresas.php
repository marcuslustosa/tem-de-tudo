<?php
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Empresa;

try {
    echo "Iniciando população do banco...\n";

    // Criar usuário administrador se não existir
    $admin = User::firstOrCreate([
        'email' => 'admin@temdetudo.com'
    ], [
        'name' => 'Administrador',
        'password' => bcrypt('123456'),
        'perfil' => 'admin',
        'pontos' => 0,
        'nivel' => 'Gold'
    ]);

    echo "Usuário admin criado/encontrado: ID {$admin->id}\n";

    // Dados das empresas para popular
    $empresas = [
        [
            'nome' => 'Sabor e Arte',
            'endereco' => 'Rua das Flores, 123 - Centro, São Paulo - SP',
            'telefone' => '(11) 3333-4444',
            'cnpj' => '11.111.111/0001-01',
            'logo' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400',
            'descricao' => 'Restaurante brasileiro com pratos tradicionais',
            'categoria' => 'alimentacao',
            'points_multiplier' => 1.5,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Bella Napoli',
            'endereco' => 'Av. Paulista, 456 - Bela Vista, São Paulo - SP',
            'telefone' => '(11) 5555-6666',
            'cnpj' => '22.222.222/0001-02',
            'logo' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400',
            'descricao' => 'Pizzaria artesanal com ingredientes frescos',
            'categoria' => 'alimentacao',
            'points_multiplier' => 2.0,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'FitLife Academia',
            'endereco' => 'Rua da Saúde, 789 - Liberdade, São Paulo - SP',
            'telefone' => '(11) 7777-8888',
            'cnpj' => '33.333.333/0001-03',
            'logo' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400',
            'descricao' => 'Academia completa com aparelhos modernos',
            'categoria' => 'saude',
            'points_multiplier' => 1.0,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Beleza Total',
            'endereco' => 'Rua Augusta, 321 - Consolação, São Paulo - SP',
            'telefone' => '(11) 9999-0000',
            'cnpj' => '44.444.444/0001-04',
            'logo' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400',
            'descricao' => 'Salão de beleza com serviços completos',
            'categoria' => 'beleza',
            'points_multiplier' => 1.2,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Café & Cia',
            'endereco' => 'Rua Oscar Freire, 654 - Jardins, São Paulo - SP',
            'telefone' => '(11) 1111-2222',
            'cnpj' => '55.555.555/0001-05',
            'logo' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400',
            'descricao' => 'Cafeteria premium com grãos especiais',
            'categoria' => 'alimentacao',
            'points_multiplier' => 1.5,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Pet Shop Amigo Fiel',
            'endereco' => 'Rua dos Animais, 987 - Vila Madalena, São Paulo - SP',
            'telefone' => '(11) 3333-4455',
            'cnpj' => '66.666.666/0001-06',
            'logo' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400',
            'descricao' => 'Pet Shop com produtos e serviços para pets',
            'categoria' => 'servicos',
            'points_multiplier' => 1.0,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Farmácia Saúde Plus',
            'endereco' => 'Av. Faria Lima, 111 - Itaim Bibi, São Paulo - SP',
            'telefone' => '(11) 5555-7788',
            'cnpj' => '77.777.777/0001-07',
            'logo' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?w=400',
            'descricao' => 'Farmácia 24h com medicamentos e conveniência',
            'categoria' => 'saude',
            'points_multiplier' => 1.3,
            'ativo' => true,
            'owner_id' => $admin->id
        ],
        [
            'nome' => 'Burger Gourmet',
            'endereco' => 'Rua da Liberdade, 222 - Liberdade, São Paulo - SP',
            'telefone' => '(11) 9988-7766',
            'cnpj' => '88.888.888/0001-08',
            'logo' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400',
            'descricao' => 'Hamburgueria artesanal com ingredientes selecionados',
            'categoria' => 'alimentacao',
            'points_multiplier' => 2.0,
            'ativo' => true,
            'owner_id' => $admin->id
        ]
    ];

    // Inserir empresas
    foreach ($empresas as $empresaData) {
        $empresa = Empresa::firstOrCreate(
            ['cnpj' => $empresaData['cnpj']], 
            $empresaData
        );
        echo "Empresa criada/encontrada: {$empresa->nome} (ID: {$empresa->id})\n";
    }

    echo "\nPopulação concluída com sucesso!\n";
    echo "Total de empresas: " . Empresa::count() . "\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}