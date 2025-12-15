<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Ponto;
use App\Models\Coupon;
use App\Models\CheckIn;
use App\Models\QRCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando população de dados fictícios para usuários e perfis...');

        // Limpar dados anteriores
        DB::table('pontos')->delete();
        DB::table('coupons')->delete();
        DB::table('check_ins')->delete();
        DB::table('qr_codes')->delete();

        // Buscar usuários já existentes
        $clientes = User::where('perfil', 'cliente')->get();
        $empresasUsers = User::where('perfil', 'empresa')->get();

        // Criar empresas se não existirem
        $empresas = Empresa::all();
        if ($empresas->isEmpty()) {
            foreach ($empresasUsers as $empresaUser) {
                $cnpj = sprintf('%02d.%03d.%03d/%04d-%02d', 
                    rand(10, 99), rand(100, 999), rand(100, 999), 
                    rand(1000, 9999), rand(10, 99));

                Empresa::create([
                    'nome' => $empresaUser->name,
                    'endereco' => 'Rua Exemplo, ' . rand(1, 1000) . ' - São Paulo, SP',
                    'telefone' => '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'cnpj' => $cnpj,
                    'logo' => null,
                    'descricao' => 'Empresa parceira do programa de fidelidade',
                    'points_multiplier' => 1.00,
                    'ativo' => true,
                    'owner_id' => $empresaUser->id,
                ]);
            }
            $empresas = Empresa::all();
        }

        // Criar QR Codes para empresas
        foreach ($empresas as $empresa) {
            for ($i = 1; $i <= 3; $i++) {
                QRCode::create([
                    'empresa_id' => $empresa->id,
                    'name' => 'QR Code ' . $i . ' - ' . $empresa->nome,
                    'code' => QRCode::gerarCodigoUnico($empresa->id),
                    'location' => ['Caixa ' . $i, 'Mesa ' . $i][rand(0, 1)],
                    'active' => true,
                    'active_offers' => json_encode(['bonus_checkin' => 10]),
                    'usage_count' => rand(0, 50),
                    'last_used_at' => now()->subDays(rand(0, 7)),
                ]);
            }
        }

        // Criar pontos e check-ins fictícios para clientes
        foreach ($clientes as $cliente) {
            $empresaId = $empresas->isNotEmpty() ? $empresas->random()->id : null;
            $qrCode = QRCode::where('empresa_id', $empresaId)->first();

            // Criar alguns check-ins
            for ($i = 1; $i <= rand(3, 8); $i++) {
                $valorCompra = rand(50, 500);
                $pontosCalculados = (int)($valorCompra * 0.1);

                $checkIn = CheckIn::create([
                    'user_id' => $cliente->id,
                    'empresa_id' => $empresaId,
                    'qr_code_id' => $qrCode?->id,
                    'valor_compra' => $valorCompra,
                    'pontos_calculados' => $pontosCalculados,
                    'foto_cupom' => null,
                    'latitude' => -23.5505199 + (rand(-1000, 1000) / 100000),
                    'longitude' => -46.6333094 + (rand(-1000, 1000) / 100000),
                    'observacoes' => 'Check-in automático #' . $i,
                    'status' => ['pending', 'approved', 'approved', 'approved'][rand(0, 3)],
                    'codigo_validacao' => strtoupper(substr(md5(uniqid()), 0, 8)),
                    'aprovado_em' => now()->subDays(rand(0, 30)),
                    'aprovado_por' => null,
                    'bonus_applied' => (bool)rand(0, 1),
                    'created_at' => now()->subDays(rand(0, 60)),
                ]);

                // Criar pontos para check-in aprovado
                if ($checkIn->status === 'approved') {
                    Ponto::create([
                        'user_id' => $cliente->id,
                        'empresa_id' => $empresaId,
                        'checkin_id' => $checkIn->id,
                        'pontos' => $pontosCalculados,
                        'descricao' => 'Pontos por check-in',
                        'tipo' => 'earn',
                        'created_at' => $checkIn->aprovado_em,
                    ]);
                }
            }

            // Atualizar total de pontos do cliente
            $totalPontos = Ponto::where('user_id', $cliente->id)->sum('pontos');
            $cliente->update(['pontos' => $totalPontos]);

            // Criar cupons fictícios
            for ($i = 1; $i <= rand(2, 5); $i++) {
                $custoPontos = [100, 200, 500, 1000][rand(0, 3)];
                $desconto = [5, 10, 15, 20][rand(0, 3)];

                Coupon::create([
                    'user_id' => $cliente->id,
                    'empresa_id' => $empresaId,
                    'checkin_id' => null,
                    'codigo' => 'CUPOM-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'tipo' => 'discount',
                    'descricao' => 'Desconto de ' . $desconto . '% em compras',
                    'custo_pontos' => $custoPontos,
                    'valor_desconto' => null,
                    'porcentagem_desconto' => $desconto,
                    'status' => ['active', 'active', 'active', 'used'][rand(0, 3)],
                    'expira_em' => now()->addDays(30 + rand(0, 60)),
                    'usado_em' => null,
                    'dados_extra' => json_encode(['categoria' => 'promoção']),
                ]);
            }
        }

        $this->command->info('Dados fictícios gerados com sucesso!');
        $this->command->info('Total de empresas: ' . $empresas->count());
        $this->command->info('Total de clientes: ' . $clientes->count());
        $this->command->info('Total de QR Codes: ' . QRCode::count());
        $this->command->info('Total de check-ins: ' . CheckIn::count());
        $this->command->info('Total de pontos: ' . Ponto::count());
        $this->command->info('Total de cupons: ' . Coupon::count());
    }
}
