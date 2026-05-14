<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresas')) {
            return;
        }

        if (!Schema::hasColumn('empresas', 'status')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('status', 30)->default('active')->after('ativo');
            });
        }

        $empresas = DB::table('empresas')
            ->select('id', 'status', 'ativo')
            ->orderBy('id')
            ->get();

        foreach ($empresas as $empresa) {
            DB::table('empresas')
                ->where('id', $empresa->id)
                ->update([
                    'status' => $this->normalizeStatus($empresa->status ?? null, $empresa->ativo ?? null),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('empresas') || !Schema::hasColumn('empresas', 'status')) {
            return;
        }

        $empresas = DB::table('empresas')
            ->select('id', 'status', 'ativo')
            ->orderBy('id')
            ->get();

        foreach ($empresas as $empresa) {
            $legacyStatus = match ($this->normalizeStatus($empresa->status ?? null, $empresa->ativo ?? null)) {
                'pending' => 'pendente',
                'active' => 'ativo',
                'suspended' => 'inativo',
                'rejected' => 'rejeitado',
                default => 'ativo',
            };

            DB::table('empresas')
                ->where('id', $empresa->id)
                ->update(['status' => $legacyStatus]);
        }
    }

    private function normalizeStatus($status, $ativo): string
    {
        $normalized = strtolower(trim((string) ($status ?? '')));

        return match ($normalized) {
            'pending', 'pendente' => 'pending',
            'active', 'ativo', 'ativa', 'approved' => 'active',
            'suspended', 'suspenso', 'suspensa', 'inactive', 'inativo', 'inativa', 'bloqueado' => 'suspended',
            'rejected', 'rejeitado', 'rejeitada' => 'rejected',
            default => (bool) ($ativo ?? true) ? 'active' : 'suspended',
        };
    }
};
