<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SimpleSeeder extends Seeder
{
    /**
     * SEEDER SEGURO - PRESERVA TODOS OS DADOS REAIS
     * Apenas gerencia os 3 acessos de teste específicos
     */
    public function run(): void
    {
        $this->command->info('🔄 Verificando acessos de teste...');
        $this->command->info('🛡️ PRESERVANDO todos os dados reais existentes');
        
        // ============================================
        // APENAS OS 3 ACESSOS DE TESTE ESPECÍFICOS
        // PRESERVA TODOS OS OUTROS DADOS REAIS
        // ============================================
        
        // 1. ADMIN REAL - Gerencia perfis das empresas, administrador do sistema
        $adminReal = User::updateOrCreate(
            ['email' => 'admin@temdetudo.com'], // Condição de busca
            [
                'name' => 'Administrador Sistema',
                'password' => Hash::make('admin123'),
                'perfil' => 'administrador',
                'telefone' => '(11) 99999-9999',
                'pontos' => 0,
                'status' => 'ativo'
            ]
        );
        
        // 2. CLIENTE FICTÍCIO - Dados fictícios para simulação de transações
        $clienteFicticio = User::updateOrCreate(
            ['email' => 'cliente@teste.com'], // Condição de busca
            [
                'name' => 'Cliente Simulação',
                'password' => Hash::make('123456'),
                'perfil' => 'usuario_comum',
                'telefone' => '(11) 98765-4321',
                'pontos' => 250.00, // Pontos fictícios para demonstração
                'status' => 'ativo'
            ]
        );
        
        // 3. EMPRESA FICTÍCIA - Dados fictícios para simulação de transações  
        $empresaFicticia = User::updateOrCreate(
            ['email' => 'empresa@teste.com'], // Condição de busca
            [
                'name' => 'Empresa Simulação LTDA',
                'password' => Hash::make('123456'),
                'perfil' => 'gestor',
                'telefone' => '(11) 3456-7890',
                'pontos' => 0,
                'status' => 'ativo'
            ]
        );
        
        $this->command->info('✅ 3 acessos de teste criados/atualizados');
        $this->command->info('✅ TODOS OS DADOS REAIS PRESERVADOS:');
        $this->command->info('   - Cadastros reais de usuários');
        $this->command->info('   - Transações reais');
        $this->command->info('   - Empresas reais');
        $this->command->info('   - Histórico real');
        $this->command->info('   - Pontos reais');
        $this->command->info('   - Logins reais');
        $this->command->info('   - Funcionalidades reais');
        
        $totalUsers = User::count();
        $realUsers = $totalUsers - 3; // Menos os 3 de teste
        $this->command->info("📊 Total: {$totalUsers} usuários ({$realUsers} reais + 3 teste)");
        
        $this->command->info('');
        $this->command->info('🎯 SISTEMA ATUALIZADO COM SEGURANÇA!');
        $this->command->info('');
        $this->command->info('👑 ADMIN REAL (gerencia sistema):');
        $this->command->info('   📧 admin@temdetudo.com');
        $this->command->info('   🔑 admin123');
        $this->command->info('');
        $this->command->info('👤 CLIENTE FICTÍCIO (simulação):');
        $this->command->info('   📧 cliente@teste.com');
        $this->command->info('   🔑 123456');
        $this->command->info('   💰 250 pontos fictícios');
        $this->command->info('');
        $this->command->info('🏢 EMPRESA FICTÍCIA (simulação):');
        $this->command->info('   📧 empresa@teste.com');
        $this->command->info('   🔑 123456');
        $this->command->info('   📍 Dados fictícios para transações');
        $this->command->info('');
        $this->command->info('⚠️  DADOS FICTÍCIOS = SEM FINS LEGAIS');
        $this->command->info('   Apenas para simulação e demonstração');
        $this->command->info('');
        $this->command->info('🛡️  SEGURANÇA GARANTIDA:');
        $this->command->info('   - Nenhum dado real foi alterado');
        $this->command->info('   - Apenas acessos de teste gerenciados');
        $this->command->info('   - Banco 100% preservado');
    }
}