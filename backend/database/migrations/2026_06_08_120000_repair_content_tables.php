<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de reparo: garante a existencia das tabelas de conteudo
 * (banners e categorias) em ambientes onde a migration original nao
 * chegou a cria-las (estado parcial em producao). Idempotente e segura.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('image_url')->nullable();
                $table->string('link')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('position')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['active', 'position']);
            });
        }

        if (!Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('active')->default(true);
                $table->integer('position')->default(0);
                $table->timestamps();

                $table->index(['active', 'position']);
            });
        }

        $now = now();

        // Categorias base (apenas se a tabela estiver vazia)
        if (Schema::hasTable('categorias') && DB::table('categorias')->count() === 0) {
            DB::table('categorias')->insert([
                ['name' => 'Restaurantes', 'slug' => 'restaurantes', 'active' => true, 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Beleza', 'slug' => 'beleza', 'active' => true, 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Saúde', 'slug' => 'saude', 'active' => true, 'position' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Mercado', 'slug' => 'mercado', 'active' => true, 'position' => 4, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Farmácia', 'slug' => 'farmacia', 'active' => true, 'position' => 5, 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Serviços', 'slug' => 'servicos', 'active' => true, 'position' => 6, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Banners base (apenas se a tabela estiver vazia)
        if (Schema::hasTable('banners') && DB::table('banners')->count() === 0) {
            DB::table('banners')->insert([
                ['title' => 'Semana de Pontos em Dobro', 'image_url' => '/assets/images/company2.jpg', 'link' => '/recompensas.html', 'active' => true, 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['title' => 'Novos Parceiros na Plataforma', 'image_url' => '/assets/images/company3.jpg', 'link' => '/parceiros_tem_de_tudo.html', 'active' => true, 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        // No-op proposital: migration de reparo nao deve derrubar tabelas em uso.
    }
};
