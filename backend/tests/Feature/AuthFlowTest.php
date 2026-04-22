<?php

namespace Tests\Feature;

use App\Services\LoyaltyProgramService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_register_login_me_and_logout_flow(): void
    {
        $email = 'cliente.flow@example.com';
        $password = 'senha123';

        $registerResponse = $this->postJson('/api/auth/register', [
            'perfil' => 'cliente',
            'name' => 'Cliente Flow',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => true,
        ]);

        $registerResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $email)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'email', 'perfil'],
                ],
            ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.perfil', 'cliente')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email', 'perfil'],
            ]);

        $token = (string) $loginResponse->json('token');
        $this->assertNotSame('', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $email);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_register_token_can_access_me_endpoint(): void
    {
        $email = 'cliente.token@example.com';
        $password = 'senha123';

        $registerResponse = $this->postJson('/api/auth/register', [
            'perfil' => 'cliente',
            'name' => 'Cliente Token',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => true,
        ]);

        $registerResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $token = (string) $registerResponse->json('token');
        $this->assertNotSame('', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $email);
    }

    public function test_register_rejects_duplicate_email_case_insensitive(): void
    {
        $password = 'senha123';
        $payload = [
            'perfil' => 'cliente',
            'name' => 'Cliente Duplicado',
            'email' => 'duplicado@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => true,
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->postJson('/api/auth/register', array_merge($payload, [
            'email' => 'DUPLICADO@EXAMPLE.COM',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'usuario.teste@example.com',
            'password' => Hash::make('senha-correta'),
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'usuario.teste@example.com',
            'password' => 'senha-errada',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_empresa_register_requires_admin_token_and_accepts_admin_bearer_on_public_route(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin.flow@example.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'admin',
            'status' => 'ativo',
        ]);

        $payload = [
            'perfil' => 'empresa',
            'name' => 'Empresa Flow',
            'email' => 'empresa.flow@example.com',
            'telefone' => '11999999999',
            'cnpj' => '12.345.678/0001-99',
            'endereco' => 'Rua Teste, 100',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'terms' => true,
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $token = $admin->createToken('test-admin-register')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/register', $payload)
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.perfil', 'empresa')
            ->assertJsonPath('data.user.email', 'empresa.flow@example.com');
    }

    public function test_register_respects_cliente_registration_flag_from_settings(): void
    {
        $this->mock(LoyaltyProgramService::class, function ($mock) {
            $mock->shouldReceive('isMaintenanceMode')->andReturn(false);
            $mock->shouldReceive('isClienteRegistrationAllowed')->andReturn(false);
            $mock->shouldReceive('isEmpresaRegistrationAllowed')->andReturn(true);
        });

        $response = $this->postJson('/api/auth/register', [
            'perfil' => 'cliente',
            'name' => 'Cliente Bloqueado',
            'email' => 'cliente.bloqueado@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'terms' => true,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
