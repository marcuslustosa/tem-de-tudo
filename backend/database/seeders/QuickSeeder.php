<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class QuickSeeder extends Seeder
{
    /**
     * Seed para popular dados básicos rapidamente
     */
    public function run(): void
    {
        $this->command->info('🔄 Populando banco...');
        
        $senha = Hash::make('senha123');
        
        // Criar 2 clientes
        $maria = User::firstOrCreate(
            ['email' => 'maria@email.com'],
            [
                'name' => 'Maria Silva',
                'password' => $senha,
                'perfil' => 'cliente',
                'telefone' => '(11) 98765-4321',
                'pontos' => 195
            ]
        );
        
        $joao = User::firstOrCreate(
            ['email' => 'joao@email.com'],
            [
                'name' => 'João Santos',
                'password' => $senha,
                'perfil' => 'cliente',
                'telefone' => '(11) 91234-5678',
                'pontos' => 120
            ]
        );
        
        // Criar 2 empresas (usuários)
        $saborArte = User::firstOrCreate(
            ['email' => 'saborearte@email.com'],
            [
                'name' => 'Restaurante Sabor & Arte',
                'password' => $senha,
                'perfil' => 'empresa',
                'telefone' => '(11) 3456-7890'
            ]
        );
        
        $bellaNapoli = User::firstOrCreate(
            ['email' => 'bellanapoli@email.com'],
            [
                'name' => 'Pizzaria Bella Napoli',
                'password' => $senha,
                'perfil' => 'empresa',
                'telefone' => '(11) 3789-0123'
            ]
        );
        
        // Criar 2 admins
        User::firstOrCreate(
            ['email' => 'admin@temdetudo.com'],
            [
                'name' => 'Admin Sistema',
                'password' => $senha,
                'perfil' => 'admin',
                'telefone' => '(11) 99999-9999'
            ]
        );
        
        User::firstOrCreate(
            ['email' => 'gerente@temdetudo.com'],
            [
                'name' => 'Gerente Operacional',
                'password' => $senha,
                'perfil' => 'admin',
                'telefone' => '(11) 98888-8888'
            ]
        );
        
        $this->command->info('✅ 6 usuários criados');
        
        // Criar empresas (estabelecimentos)
        Empresa::firstOrCreate(
            ['cnpj' => '12.345.678/0001-90'],
            [
                'owner_id' => $saborArte->id,
                'nome' => 'Restaurante Sabor & Arte',
                'descricao' => 'Culinária brasileira com toque contemporâneo.',
                'endereco' => 'Rua das Flores, 123 - Centro, São Paulo - SP',
                'telefone' => '(11) 3456-7890',
                'ativo' => true
            ]
        );
        
        Empresa::firstOrCreate(
            ['cnpj' => '98.765.432/0001-10'],
            [
                'owner_id' => $bellaNapoli->id,
                'nome' => 'Pizzaria Bella Napoli',
                'descricao' => 'As melhores pizzas artesanais da região.',
                'endereco' => 'Av. Paulista, 456 - Bela Vista, São Paulo - SP',
                'telefone' => '(11) 3789-0123',
                'ativo' => true
            ]
        );
        
        Empresa::firstOrCreate(
            ['cnpj' => '11.222.333/0001-44'],
            [
                'owner_id' => $maria->id,
                'nome' => 'Salão Beleza Pura',
                'descricao' => 'Serviços de cabelo, maquiagem e estética.',
                'endereco' => 'Rua Augusta, 789 - Consolação, São Paulo - SP',
                'telefone' => '(11) 3333-4444',
                'ativo' => true
            ]
        );
        
        Empresa::firstOrCreate(
            ['cnpj' => '22.333.444/0001-55'],
            [
                'owner_id' => $joao->id,
                'nome' => 'Academia FitPower',
                'descricao' => 'Academia completa com musculação e aeróbica.',
                'endereco' => 'Rua dos Esportes, 321 - Mooca, São Paulo - SP',
                'telefone' => '(11) 2222-3333',
                'ativo' => true
            ]
        );
        
        $this->command->info('✅ 4 empresas criadas');
        
        $this->command->info('');
        $this->command->info('==================================');
        $this->command->info('✅ BANCO POPULADO!');
        $this->command->info('==================================');
        $this->command->info('🔑 Logins (senha: senha123):');
        $this->command->info('   - maria@email.com (Cliente - 195 pts)');
        $this->command->info('   - joao@email.com (Cliente - 120 pts)');
        $this->command->info('   - saborearte@email.com (Empresa)');
        $this->command->info('   - bellanapoli@email.com (Empresa)');
        $this->command->info('   - admin@temdetudo.com (Admin)');
        $this->command->info('   - gerente@temdetudo.com (Admin)');
        $this->command->info('==================================');
    }
}
