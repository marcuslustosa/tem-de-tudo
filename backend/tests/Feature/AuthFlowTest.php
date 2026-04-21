<?php

namespace Tests\Feature;

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
}
