<?php

namespace App\DTOs\CheckIn;

class CheckInDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $user_id,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $metodo = 'qrcode'
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            empresa_id: (int) $data['empresa_id'],
            user_id: $userId,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            metodo: $data['metodo'] ?? 'qrcode'
        );
    }

    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'user_id' => $this->user_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'metodo' => $this->metodo,
        ];
    }
}
