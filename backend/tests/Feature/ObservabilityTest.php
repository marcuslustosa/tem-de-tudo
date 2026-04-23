<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_pong(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertOk()->assertJson(['status' => 'pong']);
    }

    public function test_health_endpoint_returns_system_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => ['database', 'disk', 'memory'],
            'details' => ['database', 'disk_free', 'memory_usage', 'php_version', 'laravel_version'],
        ]);
    }

    public function test_metrics_endpoint_returns_system_metrics(): void
    {
        $response = $this->getJson('/api/metrics');

        $response->assertOk()->assertJsonStructure([
            'timestamp',
            'metrics' => [
                'users_total',
                'companies_total',
                'transactions_today',
                'points_reserved',
                'memory_bytes',
                'disk_free_bytes',
            ],
        ]);
    }

    public function test_metrics_endpoint_respects_rate_limit(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/metrics');
            if ($i < 60) {
                $response->assertOk();
            } else {
                $response->assertStatus(429);
            }
        }
    }

    public function test_request_logger_middleware_adds_response_time_header(): void
    {
        if (!app()->environment('production')) {
            $this->markTestSkipped('RequestLogger so roda automaticamente em production.');
        }

        $response = $this->getJson('/api/ping');
        $response->assertHeader('X-Response-Time');
    }

    public function test_monitor_system_command_runs_successfully(): void
    {
        $this->artisan('monitor:system')->assertExitCode(0);
    }

    public function test_monitor_system_handles_expired_reservations_without_crash(): void
    {
        $user = User::factory()->create(['pontos' => 0]);
        $company = Empresa::factory()->create();

        app(LedgerService::class)->credit($user->id, 500, 'Initial');
        $redemptionService = app(RedemptionService::class);
        $intent = $redemptionService->requestRedemption($user->id, $company->id, 100);

        \DB::table('redemption_intents')->where('intent_id', $intent->intent_id)->update([
            'expires_at' => now()->subMinutes(1),
        ]);

        $this->artisan('monitor:system')->assertExitCode(0);
    }

    public function test_exception_handler_accepts_report_calls(): void
    {
        Log::spy();

        $e = new \Exception('Test exception');
        app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->report($e);

        $this->assertTrue(true);
    }
}

