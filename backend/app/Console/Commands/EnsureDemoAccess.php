<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EnsureDemoAccess extends Command
{
    protected $signature = 'app:ensure-demo-access {--sync-passwords : Atualiza a senha mesmo quando o usuario ja existe}';
    protected $description = 'Garante 1 acesso de demo por perfil (admin, empresa, cliente) para handoff';

    public function handle(): int
    {
        if (!$this->isDemoAccessEnabled()) {
            $this->info('DEMO_ACCESS_ENABLED=false: provisionamento de acessos demo desativado.');
            return self::SUCCESS;
        }

        $syncPasswords = (bool) $this->option('sync-passwords');

        $admin = $this->upsertUser(
            email: (string) env('DEMO_ADMIN_EMAIL', 'admin.demo@temdetudo.com'),
            name: (string) env('DEMO_ADMIN_NAME', 'Admin Demo'),
            perfil: 'admin',
            password: (string) env('DEMO_ADMIN_PASSWORD', 'DemoAdmin@2026!'),
            telefone: '(11) 99999-1001',
            syncPassword: $syncPasswords
        );

        $empresaUser = $this->upsertUser(
            email: (string) env('DEMO_EMPRESA_EMAIL', 'empresa.demo@temdetudo.com'),
            name: (string) env('DEMO_EMPRESA_NAME', 'Empresa Demo'),
            perfil: 'empresa',
            password: (string) env('DEMO_EMPRESA_PASSWORD', 'DemoEmpresa@2026!'),
            telefone: '(11) 99999-1002',
            syncPassword: $syncPasswords
        );

        $cliente = $this->upsertUser(
            email: (string) env('DEMO_CLIENTE_EMAIL', 'cliente.demo@temdetudo.com'),
            name: (string) env('DEMO_CLIENTE_NAME', 'Cliente Demo'),
            perfil: 'cliente',
            password: (string) env('DEMO_CLIENTE_PASSWORD', 'DemoCliente@2026!'),
            telefone: '(11) 99999-1003',
            syncPassword: $syncPasswords
        );

        $empresaId = $this->ensureEmpresaForOwner($empresaUser);

        $this->line('');
        $this->info('Acessos de demo garantidos com sucesso:');
        $this->line("- Admin:   {$admin->email}");
        $this->line("- Empresa: {$empresaUser->email}");
        $this->line("- Cliente: {$cliente->email}");
        if ($empresaId !== null) {
            $this->line("- Empresa vinculada ID: {$empresaId}");
        }

        if (!$syncPasswords) {
            $this->warn('Senha nao foi forçada para usuarios preexistentes (--sync-passwords nao informado).');
        }

        return self::SUCCESS;
    }

    private function isDemoAccessEnabled(): bool
    {
        $value = env('DEMO_ACCESS_ENABLED', true);
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function upsertUser(
        string $email,
        string $name,
        string $perfil,
        string $password,
        string $telefone,
        bool $syncPassword
    ): User {
        $user = User::query()->firstOrNew(['email' => strtolower(trim($email))]);
        $isNew = !$user->exists;

        $user->name = $name;
        $user->perfil = $perfil;
        $user->status = 'ativo';
        $user->telefone = $telefone;
        $user->is_active = true;

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        if (Schema::hasColumn('users', 'terms_accepted_at') && !$user->terms_accepted_at) {
            $user->terms_accepted_at = now();
        }

        if (Schema::hasColumn('users', 'privacy_policy_accepted_at') && !$user->privacy_policy_accepted_at) {
            $user->privacy_policy_accepted_at = now();
        }

        if (Schema::hasColumn('users', 'data_processing_consent_at') && !$user->data_processing_consent_at) {
            $user->data_processing_consent_at = now();
        }

        if (Schema::hasColumn('users', 'marketing_consent')) {
            $user->marketing_consent = false;
        }

        if (Schema::hasColumn('users', 'consent_version') && empty($user->consent_version)) {
            $user->consent_version = (string) config('privacy.default_consent_version', 'v1');
        }

        if ($isNew || $syncPassword) {
            $user->password = Hash::make($password);
        }

        if ($perfil === 'admin' && Schema::hasColumn('users', 'permissions')) {
            $permissions = [
                'manage_system',
                'manage_users',
                'view_reports',
                'manage_companies',
                'manage_promotions',
            ];
            $user->permissions = json_encode($permissions);
        }

        $user->save();

        $label = strtoupper($perfil);
        if ($isNew) {
            $this->info("{$label}: criado ({$user->email})");
        } else {
            $this->info("{$label}: atualizado ({$user->email})");
        }

        return $user;
    }

    private function ensureEmpresaForOwner(User $owner): ?int
    {
        if (!Schema::hasTable('empresas')) {
            return null;
        }

        $defaults = [
            'nome' => (string) env('DEMO_EMPRESA_RAZAO', 'Empresa Demo Tem de Tudo'),
            'ramo' => 'varejo',
            'descricao' => 'Conta de demonstracao para validacao visual e funcional.',
            'endereco' => 'Av. Paulista, 1000 - Sao Paulo/SP',
            'telefone' => '(11) 4000-9000',
            'cnpj' => (string) env('DEMO_EMPRESA_CNPJ', '98.765.432/0001-10'),
            'ativo' => true,
            'status' => 'ativo',
            'points_multiplier' => 1.0,
            'logo' => '/img/logo.png',
        ];

        $empresa = Empresa::query()->where('owner_id', $owner->id)->first();
        if ($empresa) {
            $empresa->fill($defaults);
            $empresa->save();
            return (int) $empresa->id;
        }

        $empresa = new Empresa();
        $empresa->owner_id = $owner->id;
        $empresa->fill($defaults);
        $empresa->save();

        return (int) $empresa->id;
    }
}
