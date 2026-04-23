<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseCompleteSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('coupons')) {
            DB::table('coupons')->delete();
        }
        if (Schema::hasTable('promocoes')) {
            DB::table('promocoes')->delete();
        }
        if (Schema::hasTable('check_ins')) {
            DB::table('check_ins')->delete();
        }
        if (Schema::hasTable('pontos')) {
            DB::table('pontos')->delete();
        }
        if (Schema::hasTable('empresas')) {
            DB::table('empresas')->delete();
        }
        DB::table('users')->delete();

        $admin = User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@temdetudo.com',
            'password' => Hash::make('admin123'),
            'perfil' => 'admin',
            'status' => 'ativo',
            'telefone' => '(11) 99999-9999',
            'pontos' => 0,
        ]);

        $cliente = User::create([
            'name' => 'Cliente Simulacao',
            'email' => 'cliente@teste.com',
            'password' => Hash::make('123456'),
            'perfil' => 'cliente',
            'status' => 'ativo',
            'telefone' => '(11) 98765-4321',
            'pontos' => 250,
        ]);

        $empresaOwner = User::create([
            'name' => 'Empresa Simulacao LTDA',
            'email' => 'empresa@teste.com',
            'password' => Hash::make('123456'),
            'perfil' => 'empresa',
            'status' => 'ativo',
            'telefone' => '(11) 3456-7890',
            'pontos' => 0,
        ]);

        $empresa = Empresa::create([
            'owner_id' => $empresaOwner->id,
            'nome' => 'Empresa Simulacao LTDA',
            'descricao' => 'Empresa de demonstracao para o fluxo de fidelidade.',
            'ramo' => 'alimentacao',
            'endereco' => 'Rua das Simulacoes, 123 - Sao Paulo/SP',
            'telefone' => '(11) 3456-7890',
            'cnpj' => '12.345.678/0001-90',
            'ativo' => true,
            'points_multiplier' => 1.0,
        ]);

        if (Schema::hasTable('promocoes')) {
            DB::table('promocoes')->insert([
                [
                    'empresa_id' => $empresa->id,
                    'titulo' => '30% OFF Promocao Demo',
                    'descricao' => 'Promocao ficticia para demonstracao.',
                    'desconto' => 30,
                    'status' => 'ativa',
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'empresa_id' => $empresa->id,
                    'titulo' => 'Produto Gratis (Demo)',
                    'descricao' => 'Exemplo de resgate por pontos.',
                    'desconto' => 20,
                    'status' => 'ativa',
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('pontos')) {
            DB::table('pontos')->insert([
                'user_id' => $cliente->id,
                'empresa_id' => $empresa->id,
                'pontos' => 250,
                'descricao' => 'Saldo inicial para demonstracao',
                'tipo' => 'ganho',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Seed completo executado com sucesso.');
        $this->command?->info("Admin: {$admin->email} / admin123");
        $this->command?->info("Cliente: {$cliente->email} / 123456");
        $this->command?->info("Empresa: {$empresaOwner->email} / 123456");
    }
}

