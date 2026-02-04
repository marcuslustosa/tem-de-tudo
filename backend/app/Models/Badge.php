<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'icone',
        'cor',
        'condicao_tipo', // pontos, checkins, dias_consecutivos, valor_gasto, empresas_visitadas
        'condicao_valor',
        'ativo',
        'ordem'
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'condicao_valor' => 'integer',
        'ordem' => 'integer'
    ];

    // Relacionamento com usuários que possuem este badge
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
                    ->withTimestamps()
                    ->withPivot('conquistado_em');
    }

    // Badges disponíveis
    public static function getDefaultBadges()
    {
        return [
            [
                'nome' => 'Primeiro Check-in',
                'descricao' => 'Fez seu primeiro check-in!',
                'icone' => '🎯',
                'cor' => '#10b981',
                'condicao_tipo' => 'checkins',
                'condicao_valor' => 1,
                'ordem' => 1
            ],
            [
                'nome' => 'Fiel Cliente',
                'descricao' => 'Visitou 10 empresas diferentes',
                'icone' => '⭐',
                'cor' => '#f59e0b',
                'condicao_tipo' => 'empresas_visitadas',
                'condicao_valor' => 10,
                'ordem' => 2
            ],
            [
                'nome' => 'Colecionador de Pontos',
                'descricao' => 'Acumulou 1000 pontos',
                'icone' => '💰',
                'cor' => '#8b5cf6',
                'condicao_tipo' => 'pontos',
                'condicao_valor' => 1000,
                'ordem' => 3
            ],
            [
                'nome' => 'Constante',
                'descricao' => '7 dias consecutivos com check-in',
                'icone' => '🔥',
                'cor' => '#ef4444',
                'condicao_tipo' => 'dias_consecutivos',
                'condicao_valor' => 7,
                'ordem' => 4
            ],
            [
                'nome' => 'Grande Comprador',
                'descricao' => 'Gastou mais de R$ 500',
                'icone' => '💎',
                'cor' => '#3b82f6',
                'condicao_tipo' => 'valor_gasto',
                'condicao_valor' => 50000, // em centavos
                'ordem' => 5
            ],
            [
                'nome' => 'VIP Ouro',
                'descricao' => 'Alcançou o nível Ouro',
                'icone' => '👑',
                'cor' => '#f59e0b',
                'condicao_tipo' => 'nivel',
                'condicao_valor' => 3, // 3 = ouro
                'ordem' => 6
            ]
        ];
    }

    // Verifica se um usuário conquistou este badge
    public function verificarConquista(User $user)
    {
        switch ($this->condicao_tipo) {
            case 'pontos':
                return $user->pontos >= $this->condicao_valor;
            
            case 'checkins':
                return $user->checkins()->count() >= $this->condicao_valor;
            
            case 'empresas_visitadas':
                return $user->checkins()->distinct('empresa_id')->count() >= $this->condicao_valor;
            
            case 'dias_consecutivos':
                return $this->verificarDiasConsecutivos($user);
            
            case 'valor_gasto':
                return $user->transacoes()->where('tipo', 'compra')->sum('valor') >= $this->condicao_valor;
            
            case 'nivel':
                return $user->nivel >= $this->condicao_valor;
            
            default:
                return false;
        }
    }

    private function verificarDiasConsecutivos(User $user)
    {
        $checkins = $user->checkins()
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->groupBy(function($item) {
                            return $item->created_at->format('Y-m-d');
                        });
        
        $dias_consecutivos = 0;
        $data_atual = now();
        
        for ($i = 0; $i < 30; $i++) { // verificar últimos 30 dias
            $data_verificar = $data_atual->copy()->subDays($i)->format('Y-m-d');
            
            if ($checkins->has($data_verificar)) {
                $dias_consecutivos++;
            } else {
                break;
            }
        }
        
        return $dias_consecutivos >= $this->condicao_valor;
    }
}