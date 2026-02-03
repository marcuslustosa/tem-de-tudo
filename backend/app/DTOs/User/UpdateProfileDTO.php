<?php

namespace App\DTOs\User;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $telefone = null,
        public readonly ?string $data_nascimento = null,
        public readonly ?string $avatar = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? $data['nome'] ?? null,
            email: $data['email'] ?? null,
            telefone: $data['telefone'] ?? null,
            data_nascimento: $data['data_nascimento'] ?? null,
            avatar: $data['avatar'] ?? null
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'data_nascimento' => $this->data_nascimento,
            'avatar' => $this->avatar,
        ], fn($value) => $value !== null);
    }
}
