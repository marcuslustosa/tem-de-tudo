<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\BonusAniversario;
use App\Models\Cupom;
use App\Models\InscricaoEmpresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessarBonusAniversario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:aniversario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa automaticamente os bônus de aniversário para clientes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎂 Iniciando processamento de bônus de aniversário...');
        
        $hoje = Carbon::today();
        $totalProcessados = 0;
        $totalCupons = 0;

        try {
            // Buscar clientes fazendo aniversário hoje (dia e mês, ignorar ano)
            $aniversariantes = User::where('perfil', 'cliente')
                ->whereNotNull('data_nascimento')
                ->whereRaw('EXTRACT(MONTH FROM data_nascimento) = ?', [$hoje->month])
                ->whereRaw('EXTRACT(DAY FROM data_nascimento) = ?', [$hoje->day])
                ->get();

            $this->info("📋 Encontrados {$aniversariantes->count()} aniversariantes do dia");

            foreach ($aniversariantes as $cliente) {
                $totalProcessados++;
                
                // Buscar empresas que o cliente está inscrito
                $inscricoes = InscricaoEmpresa::where('user_id', $cliente->id)
                    ->whereTrue('ativo')
                    ->get();

                foreach ($inscricoes as $inscricao) {
                    // Verificar se a empresa tem bônus de aniversário configurado
                    $bonusConfig = BonusAniversario::where('empresa_id', $inscricao->empresa_id)
                        ->whereTrue('ativo')
                        ->first();

                    if (!$bonusConfig) {
                        continue;
                    }

                    // Verificar se já foi gerado cupom de aniversário este ano
                    $jaRecebeu = Cupom::where('user_id', $cliente->id)
                        ->where('empresa_id', $inscricao->empresa_id)
                        ->where('tipo', 'bonus_aniversario')
                        ->whereYear('created_at', $hoje->year)
                        ->exists();

                    if ($jaRecebeu) {
                        $this->warn("⚠️  Cliente {$cliente->name} já recebeu bônus da empresa {$inscricao->empresa_id} este ano");
                        continue;
                    }

                    // Criar cupom de aniversário
                    $validadeDias = $bonusConfig->validade_dias ?? 30;
                    $dataValidade = $hoje->copy()->addDays($validadeDias);

                    $cupom = Cupom::create([
                        'codigo' => 'ANIV-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                        'user_id' => $cliente->id,
                        'empresa_id' => $inscricao->empresa_id,
                        'tipo' => 'bonus_aniversario',
                        'tipo_desconto' => $bonusConfig->tipo_bonus,
                        'valor_desconto' => $bonusConfig->valor_bonus,
                        'descricao' => $bonusConfig->mensagem ?? "🎂 Feliz Aniversário! Presente especial para você",
                        'validade' => $dataValidade,
                        'usado' => false,
                        'ativo' => true
                    ]);

                    $totalCupons++;
                    $this->info("✅ Cupom de aniversário criado para {$cliente->name} - Empresa {$inscricao->empresa_id}");

                    // Log da operação
                    Log::info('Bônus de aniversário processado', [
                        'cliente_id' => $cliente->id,
                        'cliente_nome' => $cliente->name,
                        'empresa_id' => $inscricao->empresa_id,
                        'cupom_id' => $cupom->id,
                        'tipo_bonus' => $bonusConfig->tipo_bonus,
                        'valor' => $bonusConfig->valor_bonus,
                        'validade' => $dataValidade->format('Y-m-d')
                    ]);
                }
            }

            $this->info("🎉 Processamento concluído!");
            $this->info("📊 Total de aniversariantes: {$totalProcessados}");
            $this->info("🎁 Total de cupons gerados: {$totalCupons}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erro ao processar bônus de aniversário: " . $e->getMessage());
            Log::error('Erro no processamento de bônus de aniversário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
