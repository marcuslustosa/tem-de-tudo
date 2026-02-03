<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
            'pontos_ganhos' => $this->pontos,
            'metodo' => $this->metodo ?? 'qrcode',
            'data' => $this->created_at?->format('d/m/Y'),
            'hora' => $this->created_at?->format('H:i'),
            'timestamp' => $this->created_at?->toIso8601String(),
        ];
    }
}
