<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    /**
     * Roda migrations e seeders manualmente
     * Acesse: /api/setup-database?secret=temdetudo2024
     */
    public function setupDatabase(Request $request)
    {
        // Verificar senha de segurança
        if ($request->get('secret') !== 'temdetudo2024') {
            return response()->json([
                'error' => 'Acesso negado. Informe o parâmetro ?secret=temdetudo2024'
            ], 403);
        }

        $output = [];
        $output[] = "========================================";
        $output[] = "🚀 SETUP DATABASE - RENDER";
        $output[] = "========================================";
        $output[] = "";

        try {
            // 1. Verificar conexão
            $output[] = "📡 Testando conexão com banco...";
            DB::connection()->getPdo();
            $output[] = "✅ Conexão OK: " . config('database.default');
            $output[] = "";

            // 2. Rodar migrations
            $output[] = "📦 Executando migrations...";
            Artisan::call('migrate', ['--force' => true]);
            $output[] = Artisan::output();
            $output[] = "✅ Migrations concluídas";
            $output[] = "";

            // 3. Rodar seeders
            $output[] = "🌱 Executando seeders...";
            Artisan::call('db:seed', ['--force' => true, '--class' => 'Database\\Seeders\\DatabaseSeeder']);
            $output[] = Artisan::output();
            $output[] = "✅ Seeders concluídos";
            $output[] = "";

            // 4. Verificar usuários criados
            $output[] = "📊 Verificando usuários criados...";
            $totalUsers = DB::table('users')->count();
            $admin = DB::table('users')->where('email', 'admin@temdetudo.com')->first();
            $cliente = DB::table('users')->where('email', 'cliente@teste.com')->first();
            $cliente1 = DB::table('users')->where('email', 'cliente1@email.com')->first();
            
            $output[] = "Total de usuários: {$totalUsers}";
            $output[] = "Admin (admin@temdetudo.com): " . ($admin ? 'EXISTE ✅' : 'NÃO EXISTE ❌');
            $output[] = "Cliente (cliente@teste.com): " . ($cliente ? 'EXISTE ✅' : 'NÃO EXISTE ❌');
            $output[] = "Cliente1 (cliente1@email.com): " . ($cliente1 ? 'EXISTE ✅' : 'NÃO EXISTE ❌');
            $output[] = "";

            // 5. Limpar caches
            $output[] = "🧹 Limpando caches...";
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            $output[] = "✅ Caches limpos";
            $output[] = "";

            $output[] = "========================================";
            $output[] = "✅ SETUP CONCLUÍDO COM SUCESSO!";
            $output[] = "========================================";
            $output[] = "";
            $output[] = "📋 CREDENCIAIS DE ACESSO:";
            $output[] = "Admin:   admin@temdetudo.com / admin123";
            $output[] = "Cliente: cliente@teste.com / 123456";
            $output[] = "Empresa: empresa@teste.com / 123456";
            $output[] = "Clientes: cliente1-50@email.com / senha123";

            return response()->json([
                'success' => true,
                'message' => 'Setup concluído com sucesso',
                'output' => $output,
                'total_users' => $totalUsers
            ]);

        } catch (\Exception $e) {
            $output[] = "";
            $output[] = "❌ ERRO: " . $e->getMessage();
            $output[] = "Arquivo: " . $e->getFile();
            $output[] = "Linha: " . $e->getLine();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => $output
            ], 500);
        }
    }
}
