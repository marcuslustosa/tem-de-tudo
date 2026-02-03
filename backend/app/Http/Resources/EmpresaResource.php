<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'endereco' => $this->endereco,
            'telefone' => $this->telefone,
            'cnpj' => $this->when($request->user()?->perfil === 'admin', $this->cnpj),
            'logo' => $this->logo,
            'pontos_checkin' => 10,
            'multiplicador' => $this->points_multiplier ?? 1.0,
            'ativo' => $this->ativo,
            'avaliacao_media' => $this->avaliacao_media ?? 0,
            'total_avaliacoes' => $this->total_avaliacoes ?? 0,
            'criado_em' => $this->created_at?->format('d/m/Y'),
        ];
    }
}
