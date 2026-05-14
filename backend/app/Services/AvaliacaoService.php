<?php

namespace App\Services;

use App\Models\Avaliacao;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use DomainException;

class AvaliacaoService
{
    public function publicPayload(Empresa $empresa, int $limit = 10): array
    {
        $items = Avaliacao::query()
            ->with(['user:id,name'])
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get();

        return [
            'empresa' => $this->serializeEmpresaResumo($empresa),
            'summary' => $this->summary($empresa),
            'items' => $items->map(fn (Avaliacao $avaliacao) => $this->serializeReview($avaliacao))->values()->all(),
        ];
    }

    public function customerPayload(User $customer, ?int $empresaId = null): array
    {
        $query = Avaliacao::query()
            ->with(['empresa:id,nome,logo,status,ativo'])
            ->where('user_id', $customer->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($empresaId !== null) {
            $query->where('empresa_id', $empresaId);
        }

        $items = $query->get();

        return [
            'items' => $items->map(function (Avaliacao $avaliacao) {
                return $this->serializeReview($avaliacao, includeCompany: true, includeUser: false);
            })->values()->all(),
            'total' => $items->count(),
        ];
    }

    public function companyPayload(Empresa $empresa, int $limit = 50): array
    {
        $items = Avaliacao::query()
            ->with(['user:id,name,email,telefone'])
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get();

        return [
            'empresa' => $this->serializeEmpresaResumo($empresa),
            'summary' => $this->summary($empresa),
            'items' => $items->map(function (Avaliacao $avaliacao) {
                return $this->serializeReview($avaliacao, includeCompany: false, includeUser: true);
            })->values()->all(),
        ];
    }

    public function createCustomerReview(Empresa $empresa, User $customer, array $attributes): Avaliacao
    {
        $this->assertCustomerCanReview($empresa, $customer);

        if ($this->findCustomerReview($empresa, $customer)) {
            throw new DomainException('Voce ja avaliou esta empresa. Edite a avaliacao existente.');
        }

        $avaliacao = Avaliacao::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => $customer->id,
            'estrelas' => (int) $attributes['estrelas'],
            'comentario' => $this->normalizeComment($attributes['comentario'] ?? null),
        ]);

        $empresa->refresh()->atualizarAvaliacaoMedia();

        return $avaliacao->fresh(['user:id,name', 'empresa:id,nome,logo,status,ativo']);
    }

    public function upsertCustomerReview(Empresa $empresa, User $customer, array $attributes): Avaliacao
    {
        $existing = $this->findCustomerReview($empresa, $customer);

        if ($existing) {
            return $this->updateCustomerReview($empresa, $customer, $attributes);
        }

        return $this->createCustomerReview($empresa, $customer, $attributes);
    }

    public function updateCustomerReview(Empresa $empresa, User $customer, array $attributes): Avaliacao
    {
        $this->assertCustomerCanReview($empresa, $customer);

        $avaliacao = $this->findCustomerReview($empresa, $customer);
        if (!$avaliacao) {
            throw new DomainException('Voce ainda nao avaliou esta empresa.');
        }

        $avaliacao->fill([
            'estrelas' => (int) $attributes['estrelas'],
            'comentario' => $this->normalizeComment($attributes['comentario'] ?? null),
        ])->save();

        $empresa->refresh()->atualizarAvaliacaoMedia();

        return $avaliacao->fresh(['user:id,name', 'empresa:id,nome,logo,status,ativo']);
    }

    public function findCustomerReview(Empresa $empresa, User $customer): ?Avaliacao
    {
        return Avaliacao::query()
            ->with(['user:id,name', 'empresa:id,nome,logo,status,ativo'])
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->first();
    }

    public function summary(Empresa $empresa): array
    {
        $empresa->refresh();

        return [
            'average' => round((float) ($empresa->avaliacao_media ?? 0), 1),
            'total' => (int) ($empresa->total_avaliacoes ?? 0),
            'distribution' => $this->distribution($empresa),
        ];
    }

    public function distribution(Empresa $empresa): array
    {
        $counts = Avaliacao::query()
            ->selectRaw('estrelas, COUNT(*) as total')
            ->where('empresa_id', $empresa->id)
            ->groupBy('estrelas')
            ->pluck('total', 'estrelas');

        $distribution = [];
        for ($star = 5; $star >= 1; $star -= 1) {
            $distribution[] = [
                'star' => $star,
                'total' => (int) ($counts[$star] ?? 0),
            ];
        }

        return $distribution;
    }

    public function assertCustomerCanReview(Empresa $empresa, User $customer): void
    {
        if ($this->normalizePerfil($customer->perfil ?? $customer->role ?? $customer->tipo ?? null) !== 'cliente') {
            throw new DomainException('Apenas clientes vinculados podem avaliar esta empresa.');
        }

        if (!$empresa->isPubliclyVisible()) {
            throw new DomainException('A empresa nao esta apta para receber avaliacoes no momento.');
        }

        $isLinked = InscricaoEmpresa::query()
            ->where('empresa_id', $empresa->id)
            ->where('user_id', $customer->id)
            ->exists();

        if (!$isLinked) {
            throw new DomainException('Voce precisa estar vinculado a empresa para avaliar.');
        }
    }

    public function serializeReview(
        Avaliacao $avaliacao,
        bool $includeCompany = false,
        bool $includeUser = true
    ): array {
        $payload = [
            'id' => $avaliacao->id,
            'empresa_id' => (int) $avaliacao->empresa_id,
            'user_id' => (int) $avaliacao->user_id,
            'nota' => (int) $avaliacao->estrelas,
            'estrelas' => (int) $avaliacao->estrelas,
            'comentario' => $avaliacao->comentario,
            'created_at' => optional($avaliacao->created_at)->toIso8601String(),
            'updated_at' => optional($avaliacao->updated_at)->toIso8601String(),
        ];

        if ($includeUser) {
            $payload['cliente'] = [
                'id' => (int) ($avaliacao->user?->id ?? $avaliacao->user_id),
                'nome' => $avaliacao->user?->name,
                'email' => $avaliacao->user?->email,
                'telefone' => $avaliacao->user?->telefone,
            ];
        }

        if ($includeCompany) {
            $payload['empresa'] = $avaliacao->empresa ? [
                'id' => (int) $avaliacao->empresa->id,
                'nome' => $avaliacao->empresa->nome,
                'logo' => $avaliacao->empresa->logo ?: '/assets/images/company1.jpg',
                'status' => $avaliacao->empresa->operationalStatus(),
                'ativo' => (bool) $avaliacao->empresa->ativo,
                'public_page_url' => '/detalhe_do_parceiro.html?id=' . $avaliacao->empresa->id,
            ] : null;
        }

        return $payload;
    }

    private function serializeEmpresaResumo(Empresa $empresa): array
    {
        return [
            'id' => (int) $empresa->id,
            'nome' => $empresa->nome,
            'avaliacao_media' => round((float) ($empresa->avaliacao_media ?? 0), 1),
            'total_avaliacoes' => (int) ($empresa->total_avaliacoes ?? 0),
            'status' => $empresa->operationalStatus(),
            'ativo' => (bool) $empresa->ativo,
        ];
    }

    private function normalizeComment(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizePerfil($value): string
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['cliente', 'customer'], true)) {
            return 'cliente';
        }

        if (in_array($normalized, ['empresa', 'company'], true)) {
            return 'empresa';
        }

        if (in_array($normalized, ['admin', 'administrador'], true)) {
            return 'admin';
        }

        return $normalized;
    }
}
