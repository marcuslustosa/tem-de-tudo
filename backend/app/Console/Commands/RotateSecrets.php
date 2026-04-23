<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RotateSecrets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'secrets:rotate {--force : Force rotation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerar novos secrets para rotação de segurança (JWT, VAPID, API keys)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Rotacionar secrets de segurança? Isso pode invalidar tokens ativos.')) {
            $this->info('Rotação cancelada.');
            return 0;
        }

        $this->info('🔄 Iniciando rotação de secrets...');

        $novosSecrets = [];

        // 1. JWT Secret
        $this->line('Gerando novo JWT_SECRET...');
        $novosSecrets['JWT_SECRET'] = base64_encode(random_bytes(32));
        $novosSecrets['JWT_SECRET_OLD'] = env('JWT_SECRET'); // Backup do antigo

        // 2. APP_KEY (Laravel)
        $this->line('Gerando novo APP_KEY...');
        $novosSecrets['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));
        $novosSecrets['APP_KEY_OLD'] = env('APP_KEY');

        // 3. VAPID Keys para Push Notifications
        $this->line('Gerando novos VAPID keys...');
        $vapidKeys = $this->generateVAPIDKeys();
        $novosSecrets['VAPID_PUBLIC_KEY'] = $vapidKeys['public'];
        $novosSecrets['VAPID_PRIVATE_KEY'] = $vapidKeys['private'];
        $novosSecrets['VAPID_PUBLIC_KEY_OLD'] = env('VAPID_PUBLIC_KEY');
        $novosSecrets['VAPID_PRIVATE_KEY_OLD'] = env('VAPID_PRIVATE_KEY');

        // 4. Encryption Key
        $this->line('Gerando nova encryption key...');
        $novosSecrets['ENCRYPTION_KEY'] = Str::random(32);
        $novosSecrets['ENCRYPTION_KEY_OLD'] = env('ENCRYPTION_KEY');

        // Exibir novos secrets
        $this->newLine();
        $this->info('✅ Novos secrets gerados com sucesso!');
        $this->newLine();

        $this->warn('⚠️  IMPORTANTE: Adicione estas variáveis ao .env (ou secrets manager):');
        $this->newLine();

        foreach ($novosSecrets as $key => $value) {
            if (!str_ends_with($key, '_OLD')) {
                $this->line("<fg=yellow>$key</>=<fg=green>$value</>");
            }
        }

        $this->newLine();
        $this->warn('⚠️  Secrets antigos (backup):');
        foreach ($novosSecrets as $key => $value) {
            if (str_ends_with($key, '_OLD')) {
                $this->line("<fg=cyan>$key</>=<fg=gray>$value</>");
            }
        }

        // Log da operação
        Log::info('Secrets rotacionados', [
            'command' => 'secrets:rotate',
            'timestamp' => now(),
            'generated_keys' => array_keys(array_filter($novosSecrets, fn($k) => !str_ends_with($k, '_OLD'), ARRAY_FILTER_USE_KEY))
        ]);

        $this->newLine();
        $this->info('📝 Log gravado em storage/logs/laravel.log');
        $this->newLine();
        $this->warn('⏰ Após atualizar o .env, execute: php artisan config:cache');

        return 0;
    }

    /**
     * Gerar VAPID keys para Web Push
     */
    private function generateVAPIDKeys(): array
    {
        // Simulação simplificada - em produção usar minishlink/web-push
        return [
            'public' => 'BP' . Str::random(86), // Base64 URL-safe
            'private' => Str::random(43)
        ];
    }
}
