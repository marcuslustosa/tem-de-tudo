<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'cpf' => $this->cpf,
            'data_nascimento' => $this->data_nascimento,
            'perfil' => $this->perfil,
            'pontos' => $this->pontos ?? 0,
            'avatar' => $this->avatar,
            'ativo' => $this->ativo,
            'email_verificado' => !is_null($this->email_verified_at),
            'membro_desde' => $this->created_at?->format('d/m/Y'),
            'ultimo_acesso' => $this->ultimo_login?->format('d/m/Y H:i'),
        ];
    }
}
