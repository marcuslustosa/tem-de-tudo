<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SimpleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desabilitar foreign keys (SQLite)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Limpar tabelas
        DB::table('coupons')->delete();
        DB::table('promocoes')->delete();
        DB::table('check_ins')->delete();
        DB::table('pontos')->delete();
        DB::table('empresas')->delete();
        DB::table('users')->delete();
        
        // Reabilitar foreign keys
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        
        $this->command->info('🔄 Populando banco de dados...');
        
        // ============================================
        // 3 ACESSOS PRINCIPAIS DO SISTEMA
        // ============================================
        
        // 1. ADMIN REAL - Gerencia perfis das empresas, administrador do sistema
        $adminReal = User::updateOrCreate(
            ['email' => 'admin@temdetudo.com'], // Condição de busca
            [
                'name' => 'Administrador Sistema',
                'password' => Hash::make('admin123'),
                'perfil' => 'admin',
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
        
        $this->command->info('✅ 3 acessos criados/atualizados: 1 admin real + 2 fictícios para simulação');
        
        $this->command->info('');
        $this->command->info('🎯 SISTEMA CRIADO COM SUCESSO!');
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
    }
}