<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    private function settingsFile(): string
    {
        return 'admin-settings.json';
    }

    private function defaultSettings(): array
    {
        return [
            'platform_name' => 'Tem de Tudo',
            'support_email' => 'contato@temdetudo.com',
            'support_whatsapp' => '(11) 99999-0000',
            'points_base_per_real' => 1.0,
            'points_expiration_days' => 365,
            'allow_register_cliente' => true,
            'allow_register_empresa' => true,
            'push_enabled' => true,
            'maintenance_mode' => false,
        ];
    }

    private function normalizeBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $raw = strtolower(trim((string) $value));
        if (in_array($raw, ['1', 'true', 'sim', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($raw, ['0', 'false', 'nao', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private function readSettings(): array
    {
        $disk = Storage::disk('local');
        $file = $this->settingsFile();
        $defaults = $this->defaultSettings();

        if (!$disk->exists($file)) {
            $disk->put($file, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $defaults;
        }

        $raw = $disk->get($file);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $disk->put($file, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    private function writeSettings(array $settings): void
    {
        Storage::disk('local')->put(
            $this->settingsFile(),
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->readSettings(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name' => 'required|string|max:120',
            'support_email' => 'required|email|max:160',
            'support_whatsapp' => 'nullable|string|max:40',
            'points_base_per_real' => 'required|numeric|min:0|max:100',
            'points_expiration_days' => 'required|integer|min:1|max:3650',
            'allow_register_cliente' => 'required',
            'allow_register_empresa' => 'required',
            'push_enabled' => 'required',
            'maintenance_mode' => 'required',
        ]);

        $current = $this->readSettings();
        $updated = array_merge($current, [
            'platform_name' => trim((string) $validated['platform_name']),
            'support_email' => strtolower(trim((string) $validated['support_email'])),
            'support_whatsapp' => trim((string) ($validated['support_whatsapp'] ?? '')),
            'points_base_per_real' => (float) $validated['points_base_per_real'],
            'points_expiration_days' => (int) $validated['points_expiration_days'],
            'allow_register_cliente' => $this->normalizeBool($validated['allow_register_cliente'], true),
            'allow_register_empresa' => $this->normalizeBool($validated['allow_register_empresa'], true),
            'push_enabled' => $this->normalizeBool($validated['push_enabled'], true),
            'maintenance_mode' => $this->normalizeBool($validated['maintenance_mode'], false),
            'updated_at' => now()->toISOString(),
        ]);

        $this->writeSettings($updated);

        return response()->json([
            'success' => true,
            'message' => 'Configuracoes salvas com sucesso.',
            'data' => $updated,
        ]);
    }
}

