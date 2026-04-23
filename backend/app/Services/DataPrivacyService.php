<?php

namespace App\Services;

use App\Models\DataPrivacyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataPrivacyService
{
    public function requestExport(User $user, array $context = []): DataPrivacyRequest
    {
        $request = DataPrivacyRequest::create([
            'user_id' => $user->id,
            'request_type' => DataPrivacyRequest::TYPE_EXPORT,
            'status' => DataPrivacyRequest::STATUS_PENDING,
            'requested_ip' => $context['ip'] ?? null,
            'requested_user_agent' => $context['user_agent'] ?? null,
            'requested_at' => now(),
        ]);

        return $this->processExport($request);
    }

    public function processExport(DataPrivacyRequest $request): DataPrivacyRequest
    {
        if ($request->status === DataPrivacyRequest::STATUS_COMPLETED && $request->file_path) {
            return $request;
        }

        $request->update([
            'status' => DataPrivacyRequest::STATUS_PROCESSING,
        ]);

        try {
            $user = $request->user()->firstOrFail();
            $payload = $this->buildUserExportPayload($user);

            $filePath = sprintf(
                'privacy-exports/user-%d/export-%s.json',
                $user->id,
                Str::uuid()->toString()
            );

            Storage::disk($this->exportDisk())->put(
                $filePath,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $request->update([
                'status' => DataPrivacyRequest::STATUS_COMPLETED,
                'file_path' => $filePath,
                'payload' => [
                    'file_size_bytes' => Storage::disk($this->exportDisk())->size($filePath),
                    'generated_at' => now()->toIso8601String(),
                ],
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao processar exportacao LGPD', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => DataPrivacyRequest::STATUS_FAILED,
                'payload' => [
                    'error' => $e->getMessage(),
                ],
                'processed_at' => now(),
            ]);
        }

        return $request->fresh();
    }

    public function updateConsent(User $user, array $data, array $context = []): User
    {
        $now = now();
        $updates = [
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? $user->marketing_consent ?? false),
            'consent_version' => (string) ($data['consent_version'] ?? config('privacy.default_consent_version', 'v1')),
        ];

        if (array_key_exists('terms_accepted', $data)) {
            $updates['terms_accepted_at'] = $data['terms_accepted'] ? ($user->terms_accepted_at ?? $now) : null;
        }

        if (array_key_exists('privacy_policy_accepted', $data)) {
            $updates['privacy_policy_accepted_at'] = $data['privacy_policy_accepted']
                ? ($user->privacy_policy_accepted_at ?? $now)
                : null;
        }

        if (array_key_exists('data_processing_consent', $data)) {
            $updates['data_processing_consent_at'] = $data['data_processing_consent']
                ? ($user->data_processing_consent_at ?? $now)
                : null;
        }

        $user->forceFill($updates)->save();

        DataPrivacyRequest::create([
            'user_id' => $user->id,
            'request_type' => DataPrivacyRequest::TYPE_CONSENT_UPDATE,
            'status' => DataPrivacyRequest::STATUS_COMPLETED,
            'payload' => [
                'updated_fields' => array_keys($updates),
                'values' => $updates,
            ],
            'requested_ip' => $context['ip'] ?? null,
            'requested_user_agent' => $context['user_agent'] ?? null,
            'requested_at' => $now,
            'processed_at' => $now,
        ]);

        return $user->fresh();
    }

    public function deleteAccount(User $user, ?string $reason = null, array $context = []): DataPrivacyRequest
    {
        $request = DataPrivacyRequest::create([
            'user_id' => $user->id,
            'request_type' => DataPrivacyRequest::TYPE_DELETE_ACCOUNT,
            'status' => DataPrivacyRequest::STATUS_PROCESSING,
            'reason' => $reason,
            'requested_ip' => $context['ip'] ?? null,
            'requested_user_agent' => $context['user_agent'] ?? null,
            'requested_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($user) {
                $freshUser = User::lockForUpdate()->findOrFail($user->id);

                if ($freshUser->status === 'deleted') {
                    $freshUser->tokens()->delete();
                    return;
                }

                $freshUser->forceFill([
                    'name' => 'Usuario Removido',
                    'email' => sprintf('deleted_%d_%s@removed.local', $freshUser->id, now()->timestamp),
                    'password' => Hash::make(Str::random(40)),
                    'telefone' => null,
                    'data_nascimento' => null,
                    'fcm_token' => null,
                    'referral_code' => null,
                    'referred_by' => null,
                    'status' => 'deleted',
                    'is_active' => false,
                    'remember_token' => null,
                    'terms_accepted_at' => null,
                    'privacy_policy_accepted_at' => null,
                    'data_processing_consent_at' => null,
                    'marketing_consent' => false,
                ])->save();

                $freshUser->tokens()->delete();
            });

            $request->update([
                'status' => DataPrivacyRequest::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao anonimizar conta do usuario', [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => DataPrivacyRequest::STATUS_FAILED,
                'payload' => [
                    'error' => $e->getMessage(),
                ],
                'processed_at' => now(),
            ]);

            throw $e;
        }

        return $request->fresh();
    }

    private function buildUserExportPayload(User $user): array
    {
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'perfil' => $user->perfil,
                'telefone' => $user->telefone,
                'status' => $user->status,
                'created_at' => optional($user->created_at)->toIso8601String(),
                'updated_at' => optional($user->updated_at)->toIso8601String(),
            ],
            'consents' => [
                'terms_accepted_at' => optional($user->terms_accepted_at)->toIso8601String(),
                'privacy_policy_accepted_at' => optional($user->privacy_policy_accepted_at)->toIso8601String(),
                'data_processing_consent_at' => optional($user->data_processing_consent_at)->toIso8601String(),
                'marketing_consent' => (bool) ($user->marketing_consent ?? false),
                'consent_version' => $user->consent_version,
            ],
            'datasets' => [],
        ];

        $payload['datasets']['pontos'] = $this->tableRows('pontos', function () use ($user) {
            return DB::table('pontos')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit((int) config('privacy.export_row_limit', 5000))
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        });

        $payload['datasets']['ledger'] = $this->tableRows('ledger', function () use ($user) {
            return DB::table('ledger')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit((int) config('privacy.export_row_limit', 5000))
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        });

        $payload['datasets']['pagamentos'] = $this->tableRows('pagamentos', function () use ($user) {
            return DB::table('pagamentos')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit((int) config('privacy.export_row_limit', 5000))
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        });

        $payload['datasets']['redemption_intents'] = $this->tableRows('redemption_intents', function () use ($user) {
            return DB::table('redemption_intents')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit((int) config('privacy.export_row_limit', 5000))
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        });

        if (method_exists($user, 'empresa')) {
            $company = $user->empresa()->first();
            if ($company) {
                $payload['datasets']['empresa'] = [
                    'id' => $company->id,
                    'nome' => $company->nome,
                    'cnpj' => $company->cnpj,
                    'telefone' => $company->telefone,
                    'ativo' => (bool) $company->ativo,
                ];
            }
        }

        return $payload;
    }

    private function tableRows(string $table, callable $callback): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return $callback();
    }

    public function exportDisk(): string
    {
        return (string) config('privacy.export_disk', config('filesystems.default', 'local'));
    }
}
