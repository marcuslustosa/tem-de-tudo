<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_verify_frontend_assets_command_succeeds_with_fix_flag(): void
    {
        $exitCode = Artisan::call('app:verify-frontend-assets', [
            '--fix' => true,
        ]);

        $this->assertSame(0, $exitCode);
    }
}
