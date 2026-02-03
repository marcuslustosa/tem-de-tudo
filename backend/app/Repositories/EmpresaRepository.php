<?php

namespace App\Repositories;

use App\Models\Empresa;
use Illuminate\Support\Collection;

class EmpresaRepository
{
    /**
     * Listar todas empresas ativas
     */
    public function getActive(?string $categoria = null, ?string $busca = null): Collection
    {
        $query = Empresa::where('ativo', true);

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        if ($busca) {
            $query->where(function($q) use ($busca) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                  ->orWhere('descricao', 'ILIKE', "%{$busca}%");
            });
        }

        return $query->orderBy('nome')->get();
    }

    /**
     * Buscar empresa por ID
     */
    public function findById(int $id): ?Empresa
    {
        return Empresa::find($id);
    }

    /**
     * Criar empresa
     */
    public function create(array $data): Empresa
    {
        return Empresa::create($data);
    }

    /**
     * Atualizar empresa
     */
    public function update(Empresa $empresa, array $data): bool
    {
        return $empresa->update($data);
    }

    /**
     * Buscar empresas do owner
     */
    public function getByOwner(int $ownerId): Collection
    {
        return Empresa::where('owner_id', $ownerId)->get();
    }

    /**
     * Verificar se CNPJ existe
     */
    public function cnpjExists(string $cnpj, ?int $excludeId = null): bool
    {
        $query = Empresa::where('cnpj', $cnpj);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}
