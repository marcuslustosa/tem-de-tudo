<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\I9PlusDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeployAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_demo_access_command_creates_expected_profiles(): void
    {
        config([
            'app.env' => 'testing',
        ]);

        putenv('DEMO_ACCESS_ENABLED=true');
        putenv('DEMO_FORCE_PASSWORD_RESET=true');

        $exitCode = Artisan::call('app:ensure-demo-access', [
            '--sync-passwords' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@demo.local',
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'malagueta@demo.local',
            'perfil' => 'empresa',
            'status' => 'ativo',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@demo.local',
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $empresaUserId = User::query()->where('email', 'malagueta@demo.local')->value('id');
        $this->assertNotNull($empresaUserId);

        $this->assertNotNull(
            DB::table('empresas')->where('owner_id', $empresaUserId)->value('id')
        );
    }

    public function test_ensure_demo_access_command_is_idempotent_after_i9plus_demo_seed(): void
    {
        putenv('DEMO_ACCESS_ENABLED=true');
        putenv('DEMO_FORCE_PASSWORD_RESET=true');
        putenv('DEMO_EMPRESA_EMAIL=malagueta@demo.local');
        putenv('DEMO_EMPRESA_RAZAO=Malagueta Galpao');
        putenv('DEMO_EMPRESA_CNPJ=11.111.111/0001-11');

        $this->seed(I9PlusDemoSeeder::class);

        $exitCode = Artisan::call('app:ensure-demo-access', [
            '--sync-passwords' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, Empresa::query()->where('cnpj', '11.111.111/0001-11')->count());
        $this->assertDatabaseHas('users', [
            'email' => 'cliente.push@demo.local',
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);
    }

    public function test_verify_frontend_assets_command_succeeds_with_fix_flag(): void
    {
        $exitCode = Artisan::call('app:verify-frontend-assets', [
            '--fix' => true,
        ]);

        $this->assertSame(0, $exitCode);
    }
}
