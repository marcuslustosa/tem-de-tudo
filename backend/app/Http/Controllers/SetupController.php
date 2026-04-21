<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SetupController extends Controller
{
    /**
     * Runs maintenance database tasks (migrations/optional seed) in a controlled way.
     */
    public function setupDatabase(Request $request)
    {
        if (!$this->isSetupAllowed()) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint indisponivel neste ambiente.',
            ], 403);
        }

        if (!$this->hasValidToken($request)) {
            Log::warning('Setup database unauthorized attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token invalido.',
            ], 403);
        }

        $output = [];

        try {
            $output[] = 'Limpando caches...';
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            $output[] = 'Caches limpos.';

            $output[] = 'Testando conexao com banco...';
            DB::connection()->getPdo();
            $output[] = 'Conexao com banco OK.';

            $output[] = 'Executando migrations...';
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $output[] = trim(Artisan::output());
            $output[] = 'Migrations concluidas.';

            if (DB::getDriverName() === 'pgsql') {
                $output[] = 'Aplicando ajustes de compatibilidade PostgreSQL...';
                $this->applyPostgresCompatibilityFixes();
                $output[] = 'Ajustes PostgreSQL finalizados.';
            }

            if ($this->shouldRunSeeder()) {
                $output[] = 'Executando seeders...';
                Artisan::call('db:seed', [
                    '--force' => true,
                    '--class' => 'Database\\Seeders\\DatabaseSeeder',
                    '--no-interaction' => true,
                ]);
                $output[] = trim(Artisan::output());
                $output[] = 'Seeders concluidos.';
            } else {
                $output[] = 'Seeders desativados (SETUP_DATABASE_RUN_SEED=false).';
            }

            $totalUsers = DB::table('users')->count();

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Setup concluido com sucesso.',
                'total_users' => $totalUsers,
                'output' => array_values(array_filter($output)),
            ]);
        } catch (\Throwable $e) {
            Log::error('Setup database failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Falha ao executar setup do banco.',
            ], 500);
        }
    }

    private function isSetupAllowed(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return $this->envBool('ALLOW_SETUP_ENDPOINT', false);
    }

    private function hasValidToken(Request $request): bool
    {
        $expectedToken = (string) env('SETUP_DATABASE_TOKEN', '');

        // In local/testing, token is optional for convenience.
        if ($expectedToken === '' && app()->environment(['local', 'testing'])) {
            return true;
        }

        if ($expectedToken === '') {
            return false;
        }

        $provided = (string) ($request->header('X-Setup-Token') ?? $request->input('token', ''));

        return $provided !== '' && hash_equals($expectedToken, $provided);
    }

    private function shouldRunSeeder(): bool
    {
        return $this->envBool('SETUP_DATABASE_RUN_SEED', false);
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = env($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function applyPostgresCompatibilityFixes(): void
    {
        // Keep compatibility for legacy data types that might appear in old databases.
        if (Schema::hasTable('empresas')) {
            DB::statement("ALTER TABLE empresas ALTER COLUMN ativo TYPE BOOLEAN USING CASE WHEN ativo::text IN ('1', 't', 'true', 'y', 'yes') THEN TRUE ELSE FALSE END");
            DB::statement('ALTER TABLE empresas ALTER COLUMN points_multiplier TYPE DOUBLE PRECISION USING points_multiplier::double precision');
        }

        if (Schema::hasTable('qr_codes')) {
            DB::statement("ALTER TABLE qr_codes ALTER COLUMN active TYPE BOOLEAN USING CASE WHEN active::text IN ('1', 't', 'true', 'y', 'yes') THEN TRUE ELSE FALSE END");
        }

        if (Schema::hasTable('check_ins')) {
            DB::statement("ALTER TABLE check_ins ALTER COLUMN bonus_applied TYPE BOOLEAN USING CASE WHEN bonus_applied::text IN ('1', 't', 'true', 'y', 'yes') THEN TRUE ELSE FALSE END");
        }

        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'is_active')) {
            DB::statement("ALTER TABLE users ALTER COLUMN is_active TYPE BOOLEAN USING CASE WHEN is_active::text IN ('1', 't', 'true', 'y', 'yes') THEN TRUE ELSE FALSE END");
        }

        $notificationColumns = [
            'email_notifications',
            'points_notifications',
            'security_notifications',
            'promotional_notifications',
        ];

        foreach ($notificationColumns as $column) {
            if (Schema::hasColumn('users', $column)) {
                DB::statement("ALTER TABLE users ALTER COLUMN {$column} TYPE BOOLEAN USING CASE WHEN {$column}::text IN ('1', 't', 'true', 'y', 'yes') THEN TRUE ELSE FALSE END");
            }
        }
    }
}
