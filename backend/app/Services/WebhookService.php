<?php

namespace App\Services;

use App\Models\WebhookSaida;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispara todos os webhooks cadastrados para o evento informado.
     *
     * @param  string    $evento   Ex: 'checkin', 'resgate', 'nivel_up', 'badge'
     * @param  array     $payload  Dados do evento
     * @param  int|null  $empresaId  Limitar ao webhook da empresa (opcional)
     */
    public function disparar(string $evento, array $payload, ?int $empresaId = null): void
    {
        $query = WebhookSaida::where('ativo', true)
            ->whereJsonContains('eventos', $evento);

        if ($empresaId !== null) {
            $query->where(fn ($q) => $q->where('empresa_id', $empresaId)->orWhereNull('empresa_id'));
        }

        $webhooks = $query->get();

        foreach ($webhooks as $webhook) {
            $this->enviar($webhook, $evento, $payload);
        }
    }

    private function enviar(WebhookSaida $webhook, string $evento, array $payload): void
    {
        $body = [
            'evento'     => $evento,
            'timestamp'  => now()->toIso8601String(),
            'payload'    => $payload,
        ];

        $headers = ['Content-Type' => 'application/json'];

        if ($webhook->segredo) {
            $assinatura = hash_hmac('sha256', json_encode($body), $webhook->segredo);
            $headers['X-TDT-Signature'] = "sha256={$assinatura}";
        }

        $statusHttp = null;
        $resposta   = null;
        $sucesso    = false;

        try {
            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->post($webhook->url, $body);

            $statusHttp = $response->status();
            $resposta   = substr($response->body(), 0, 500);
            $sucesso    = $response->successful();
        } catch (\Throwable $e) {
            $resposta = $e->getMessage();
            Log::warning("Webhook falhou [{$webhook->url}]: {$e->getMessage()}");
        }

        WebhookLog::create([
            'webhook_id'  => $webhook->id,
            'evento'      => $evento,
            'payload'     => $body,
            'status_http' => $statusHttp,
            'resposta'    => $resposta,
            'sucesso'     => $sucesso,
            'tentativas'  => 1,
            'enviado_em'  => now(),
        ]);
    }
}
