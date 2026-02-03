<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromocaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'pontos_necessarios' => $this->pontos_necessarios,
            'percentual_desconto' => $this->percentual_desconto,
            'valor_desconto' => $this->valor_desconto,
            'tipo_recompensa' => $this->tipo_recompensa,
            'data_inicio' => $this->data_inicio?->format('d/m/Y'),
            'data_fim' => $this->data_fim?->format('d/m/Y'),
            'ativo' => $this->ativo,
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
        ];
    }
}
