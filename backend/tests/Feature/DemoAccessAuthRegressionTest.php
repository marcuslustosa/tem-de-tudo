<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccessAuthRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_access_command_provisions_users_that_can_login(): void
    {
        putenv('DEMO_ACCESS_ENABLED=true');
        putenv('DEMO_FORCE_PASSWORD_RESET=true');

        $exitCode = Artisan::call('app:ensure-demo-access', [
            '--sync-passwords' => true,
        ]);
        $this->assertSame(0, $exitCode);

        $this->postJson('/api/admin/login', [
            'email' => 'admin.demo@temdetudo.com',
            'password' => 'DemoAdmin@2026!',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/auth/login', [
            'email' => 'empresa.demo@temdetudo.com',
            'password' => 'DemoEmpresa@2026!',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.perfil', 'empresa');

        $this->postJson('/api/auth/login', [
            'email' => 'cliente.demo@temdetudo.com',
            'password' => 'DemoCliente@2026!',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.perfil', 'cliente');
    }

    public function test_admin_create_user_accepts_senha_field_and_creates_empresa(): void
    {
        $admin = User::factory()->create([
            'perfil' => 'admin',
            'status' => 'ativo',
            'password' => Hash::make('Admin@123'),
        ]);

        $token = $admin->createToken('admin-create-user')->plainTextToken;
        $password = 'Senha@Empresa#2026!';

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/create-user', [
                'name' => 'Empresa via Admin',
                'email' => 'empresa.via.admin@example.com',
                'perfil' => 'empresa',
                'senha' => $password,
                'telefone' => '11999999999',
                'cnpj' => '12.345.678/0001-99',
                'endereco' => 'Rua Teste, 100',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $userId = (int) DB::table('users')->where('email', 'empresa.via.admin@example.com')->value('id');
        $this->assertGreaterThan(0, $userId);
        $this->assertNotNull(DB::table('empresas')->where('owner_id', $userId)->value('id'));

        $this->postJson('/api/auth/login', [
            'email' => 'empresa.via.admin@example.com',
            'password' => $password,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.perfil', 'empresa');
    }

    public function test_cliente_register_and_login_with_special_char_password(): void
    {
        $password = 'S3nh@!Teste#2026';

        $this->postJson('/api/auth/register', [
            'perfil' => 'cliente',
            'name' => 'Cliente Especial',
            'email' => 'cliente.especial@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => true,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->postJson('/api/auth/login', [
            'email' => 'cliente.especial@example.com',
            'password' => $password,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.perfil', 'cliente');
    }
}

