<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminContentController extends Controller
{
    private function hasContentTables(): bool
    {
        return Schema::hasTable('banners') && Schema::hasTable('categorias');
    }

    private function fallbackFile(): string
    {
        return 'content-fallback.json';
    }

    private function fallbackDefault(): array
    {
        return [
            'banners' => [
                [
                    'id' => 1,
                    'title' => 'Semana de Pontos em Dobro',
                    'image_url' => '/assets/images/company2.jpg',
                    'link' => '/recompensas.html',
                    'active' => true,
                    'position' => 1,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ],
                [
                    'id' => 2,
                    'title' => 'Novos Parceiros na Plataforma',
                    'image_url' => '/assets/images/company3.jpg',
                    'link' => '/parceiros_tem_de_tudo.html',
                    'active' => true,
                    'position' => 2,
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString(),
                ],
            ],
            'categorias' => [
                ['id' => 1, 'name' => 'Restaurantes', 'slug' => 'restaurantes', 'active' => true, 'position' => 1, 'created_at' => now()->toISOString(), 'updated_at' => now()->toISOString()],
                ['id' => 2, 'name' => 'Beleza', 'slug' => 'beleza', 'active' => true, 'position' => 2, 'created_at' => now()->toISOString(), 'updated_at' => now()->toISOString()],
                ['id' => 3, 'name' => 'Saude', 'slug' => 'saude', 'active' => true, 'position' => 3, 'created_at' => now()->toISOString(), 'updated_at' => now()->toISOString()],
            ],
        ];
    }

    private function readFallback(): array
    {
        $disk = Storage::disk('local');
        $file = $this->fallbackFile();

        if (!$disk->exists($file)) {
            $seed = $this->fallbackDefault();
            $disk->put($file, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $seed;
        }

        $raw = $disk->get($file);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            $seed = $this->fallbackDefault();
            $disk->put($file, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $seed;
        }

        $decoded['banners'] = array_values(array_filter($decoded['banners'] ?? [], fn($x) => is_array($x)));
        $decoded['categorias'] = array_values(array_filter($decoded['categorias'] ?? [], fn($x) => is_array($x)));

        return $decoded;
    }

    private function writeFallback(array $payload): void
    {
        $payload['banners'] = array_values($payload['banners'] ?? []);
        $payload['categorias'] = array_values($payload['categorias'] ?? []);
        Storage::disk('local')->put(
            $this->fallbackFile(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function normalizeBool($value, bool $default = true): bool
    {
        if ($value === null) return $default;
        if (is_bool($value)) return $value;

        $raw = strtolower(trim((string) $value));
        if (in_array($raw, ['1', 'true', 'sim', 'yes', 'ativo', 'ativa'], true)) return true;
        if (in_array($raw, ['0', 'false', 'nao', 'no', 'inativo', 'inativa'], true)) return false;

        return $default;
    }

    private function nextId(array $items): int
    {
        $ids = array_map(fn($item) => (int) ($item['id'] ?? 0), $items);
        return empty($ids) ? 1 : (max($ids) + 1);
    }

    public function index()
    {
        if ($this->hasContentTables()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'banners' => Banner::orderBy('position')->orderByDesc('id')->get(),
                    'categorias' => Categoria::orderBy('position')->orderByDesc('id')->get(),
                ],
            ]);
        }

        $fallback = $this->readFallback();
        return response()->json([
            'success' => true,
            'message' => 'Conteudo carregado via fallback local (sem tabelas de banners/categorias).',
            'data' => [
                'banners' => $fallback['banners'],
                'categorias' => $fallback['categorias'],
                'partial' => false,
                'source' => 'fallback_file',
            ],
        ]);
    }

    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image_url' => 'nullable|string|max:2048',
            'link' => 'nullable|string|max:2048',
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'payload' => 'nullable|array',
        ]);

        if ($this->hasContentTables()) {
            $banner = Banner::create(array_merge([
                'active' => true,
                'position' => 0,
            ], $validated));

            return response()->json([
                'success' => true,
                'message' => 'Banner criado com sucesso.',
                'data' => $banner,
            ], 201);
        }

        $fallback = $this->readFallback();
        $banner = [
            'id' => $this->nextId($fallback['banners']),
            'title' => $validated['title'],
            'image_url' => $validated['image_url'] ?? '',
            'link' => $validated['link'] ?? '',
            'active' => $this->normalizeBool($validated['active'] ?? true, true),
            'position' => (int) ($validated['position'] ?? 0),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'payload' => $validated['payload'] ?? null,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];
        $fallback['banners'][] = $banner;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Banner criado com sucesso (fallback local).',
            'data' => $banner,
        ], 201);
    }

    public function updateBanner(Request $request, $bannerId)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image_url' => 'nullable|string|max:2048',
            'link' => 'nullable|string|max:2048',
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'payload' => 'nullable|array',
        ]);

        if ($this->hasContentTables()) {
            $banner = Banner::find($bannerId);
            if (!$banner) {
                return response()->json(['success' => false, 'message' => 'Banner nao encontrado.'], 404);
            }

            $banner->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Banner atualizado com sucesso.',
                'data' => $banner->fresh(),
            ]);
        }

        $fallback = $this->readFallback();
        $index = collect($fallback['banners'])->search(fn($item) => (string) ($item['id'] ?? '') === (string) $bannerId);
        if ($index === false) {
            return response()->json(['success' => false, 'message' => 'Banner nao encontrado.'], 404);
        }

        $current = $fallback['banners'][$index];
        $updated = array_merge($current, $validated, [
            'active' => array_key_exists('active', $validated) ? $this->normalizeBool($validated['active'], true) : $this->normalizeBool($current['active'] ?? true, true),
            'updated_at' => now()->toISOString(),
        ]);

        $fallback['banners'][$index] = $updated;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Banner atualizado com sucesso (fallback local).',
            'data' => $updated,
        ]);
    }

    public function destroyBanner($bannerId)
    {
        if ($this->hasContentTables()) {
            $banner = Banner::find($bannerId);
            if (!$banner) {
                return response()->json(['success' => false, 'message' => 'Banner nao encontrado.'], 404);
            }

            $banner->delete();
            return response()->json([
                'success' => true,
                'message' => 'Banner removido com sucesso.',
            ]);
        }

        $fallback = $this->readFallback();
        $remaining = array_values(array_filter(
            $fallback['banners'],
            fn($item) => (string) ($item['id'] ?? '') !== (string) $bannerId
        ));

        if (count($remaining) === count($fallback['banners'])) {
            return response()->json(['success' => false, 'message' => 'Banner nao encontrado.'], 404);
        }

        $fallback['banners'] = $remaining;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Banner removido com sucesso (fallback local).',
        ]);
    }

    public function storeCategoria(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
        ]);

        if ($this->hasContentTables()) {
            $slug = $validated['slug'] ?? Str::slug($validated['name']);
            if (Categoria::where('slug', $slug)->exists()) {
                return response()->json(['success' => false, 'message' => 'Slug ja existe.'], 422);
            }

            $categoria = Categoria::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'active' => $validated['active'] ?? true,
                'position' => $validated['position'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoria criada com sucesso.',
                'data' => $categoria,
            ], 201);
        }

        $fallback = $this->readFallback();
        $slug = trim($validated['slug'] ?? '') ?: Str::slug($validated['name']);
        if (collect($fallback['categorias'])->contains(fn($item) => ($item['slug'] ?? '') === $slug)) {
            return response()->json(['success' => false, 'message' => 'Slug ja existe.'], 422);
        }

        $categoria = [
            'id' => $this->nextId($fallback['categorias']),
            'name' => $validated['name'],
            'slug' => $slug,
            'active' => $this->normalizeBool($validated['active'] ?? true, true),
            'position' => (int) ($validated['position'] ?? 0),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $fallback['categorias'][] = $categoria;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Categoria criada com sucesso (fallback local).',
            'data' => $categoria,
        ], 201);
    }

    public function updateCategoria(Request $request, $categoriaId)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
        ]);

        if ($this->hasContentTables()) {
            $categoria = Categoria::find($categoriaId);
            if (!$categoria) {
                return response()->json(['success' => false, 'message' => 'Categoria nao encontrada.'], 404);
            }

            $slug = $validated['slug'] ?? ($validated['name'] ?? null ? Str::slug($validated['name']) : null);
            if ($slug && Categoria::where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Slug ja existe.'], 422);
            }

            if ($slug) {
                $validated['slug'] = $slug;
            }

            $categoria->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Categoria atualizada com sucesso.',
                'data' => $categoria->fresh(),
            ]);
        }

        $fallback = $this->readFallback();
        $index = collect($fallback['categorias'])->search(fn($item) => (string) ($item['id'] ?? '') === (string) $categoriaId);
        if ($index === false) {
            return response()->json(['success' => false, 'message' => 'Categoria nao encontrada.'], 404);
        }

        $current = $fallback['categorias'][$index];
        $candidateSlug = trim((string) ($validated['slug'] ?? ''));
        if (!$candidateSlug && isset($validated['name'])) {
            $candidateSlug = Str::slug($validated['name']);
        }
        if ($candidateSlug && collect($fallback['categorias'])->contains(function ($item) use ($candidateSlug, $categoriaId) {
            return (string) ($item['id'] ?? '') !== (string) $categoriaId && ($item['slug'] ?? '') === $candidateSlug;
        })) {
            return response()->json(['success' => false, 'message' => 'Slug ja existe.'], 422);
        }

        if ($candidateSlug) {
            $validated['slug'] = $candidateSlug;
        }

        $updated = array_merge($current, $validated, [
            'active' => array_key_exists('active', $validated) ? $this->normalizeBool($validated['active'], true) : $this->normalizeBool($current['active'] ?? true, true),
            'updated_at' => now()->toISOString(),
        ]);

        $fallback['categorias'][$index] = $updated;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Categoria atualizada com sucesso (fallback local).',
            'data' => $updated,
        ]);
    }

    public function destroyCategoria($categoriaId)
    {
        if ($this->hasContentTables()) {
            $categoria = Categoria::find($categoriaId);
            if (!$categoria) {
                return response()->json(['success' => false, 'message' => 'Categoria nao encontrada.'], 404);
            }

            $categoria->delete();
            return response()->json([
                'success' => true,
                'message' => 'Categoria removida com sucesso.',
            ]);
        }

        $fallback = $this->readFallback();
        $remaining = array_values(array_filter(
            $fallback['categorias'],
            fn($item) => (string) ($item['id'] ?? '') !== (string) $categoriaId
        ));

        if (count($remaining) === count($fallback['categorias'])) {
            return response()->json(['success' => false, 'message' => 'Categoria nao encontrada.'], 404);
        }

        $fallback['categorias'] = $remaining;
        $this->writeFallback($fallback);

        return response()->json([
            'success' => true,
            'message' => 'Categoria removida com sucesso (fallback local).',
        ]);
    }
}
