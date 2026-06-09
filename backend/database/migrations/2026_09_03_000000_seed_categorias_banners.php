<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Popula categorias/banners base no banco (produção começou com as tabelas vazias).
 * Idempotente (só insere se a tabela estiver vazia) e pg-safe (boolean como 'true' no pgsql).
 * Bulletproof: nunca lança (não derruba o deploy).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            $pg = DB::connection()->getDriverName() === 'pgsql';
            $true = $pg ? 'true' : true;
            $now = now();

            if (Schema::hasTable('categorias') && (int) DB::table('categorias')->count() === 0) {
                $nomes = [
                    'Restaurantes' => 'restaurantes',
                    'Lanchonetes' => 'lanchonetes',
                    'Cafeterias' => 'cafeterias',
                    'Padarias' => 'padarias',
                    'Docerias' => 'docerias',
                    'Mercados' => 'mercados',
                    'Farmácias' => 'farmacias',
                    'Beleza' => 'beleza',
                    'Saúde' => 'saude',
                    'Academias' => 'academias',
                    'Pet Shops' => 'pet-shops',
                    'Moda' => 'moda',
                    'Serviços' => 'servicos',
                ];
                $rows = [];
                $pos = 1;
                foreach ($nomes as $name => $slug) {
                    $rows[] = [
                        'name' => $name,
                        'slug' => $slug,
                        'active' => $true,
                        'position' => $pos++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('categorias')->insert($rows);
            }

            if (Schema::hasTable('banners') && (int) DB::table('banners')->count() === 0) {
                DB::table('banners')->insert([
                    ['title' => 'Semana de Pontos em Dobro', 'image_url' => '/assets/images/company2.jpg', 'link' => '/recompensas.html', 'active' => $true, 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
                    ['title' => 'Novos Parceiros na Plataforma', 'image_url' => '/assets/images/company3.jpg', 'link' => '/parceiros_tem_de_tudo.html', 'active' => $true, 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('seed_categorias_banners falhou: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // No-op proposital.
    }
};
