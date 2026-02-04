<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela de usuários (clientes, empresas e admins)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('tipo', ['cliente', 'empresa', 'admin'])->default('cliente');
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->decimal('pontos', 10, 2)->default(0);
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('tipo');
            $table->index('cpf');
            $table->index('cnpj');
        });

        // Tabela de empresas (estabelecimentos)
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('categoria', 100)->nullable();
            $table->text('endereco')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('horario_funcionamento')->nullable();
            $table->string('foto_url', 500)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('categoria');
            $table->index('ativo');
        });

        // Tabela de check-ins (registro de visitas)
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->decimal('pontos_ganhos', 10, 2)->default(10);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('metodo', ['qrcode', 'manual', 'automatico'])->default('manual');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('empresa_id');
            $table->index('created_at');
        });

        // Tabela de promoções (ofertas e descontos)
        Schema::create('promocoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->integer('pontos_necessarios');
            $table->decimal('desconto_percentual', 5, 2)->nullable();
            $table->decimal('desconto_valor', 10, 2)->nullable();
            $table->date('validade_inicio')->nullable();
            $table->date('validade_fim')->nullable();
            $table->integer('quantidade_disponivel')->nullable();
            $table->integer('quantidade_resgatada')->default(0);
            $table->boolean('ativo')->default(true);
            $table->string('imagem_url', 500)->nullable();
            $table->timestamps();
            
            $table->index('empresa_id');
            $table->index('ativo');
            $table->index('validade_fim');
        });

        // Tabela de cupons (cupons resgatados pelos clientes)
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('promocao_id')->constrained()->onDelete('cascade');
            $table->string('codigo', 50)->unique();
            $table->boolean('usado')->default(false);
            $table->timestamp('data_uso')->nullable();
            $table->date('validade')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('promocao_id');
            $table->index('codigo');
            $table->index('usado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupons');
        Schema::dropIfExists('promocoes');
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('empresas');
        Schema::dropIfExists('users');
    }
};
