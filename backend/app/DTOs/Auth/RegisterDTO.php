<?php

namespace App\DTOs\Auth;

class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $telefone = null,
        public readonly ?string $cpf = null,
        public readonly ?string $data_nascimento = null,
        public readonly string $perfil = 'usuario_comum'
    ) {}

    /**
     * Cria DTO a partir de array de dados
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? $data['nome'],
            email: $data['email'],
            password: $data['password'] ?? $data['senha'],
            telefone: $data['telefone'] ?? null,
            cpf: $data['cpf'] ?? null,
            data_nascimento: $data['data_nascimento'] ?? null,
            perfil: $data['perfil'] ?? 'usuario_comum'
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'telefone' => $this->telefone,
            'cpf' => $this->cpf,
            'data_nascimento' => $this->data_nascimento,
            'perfil' => $this->perfil,
        ];
    }
}
