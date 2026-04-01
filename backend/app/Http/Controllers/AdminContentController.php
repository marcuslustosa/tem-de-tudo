<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminContentController extends Controller
{
    private function hasContentTables(): bool
    {
        return Schema::hasTable('banners') && Schema::hasTable('categorias');
    }

    public function index()
    {
        if (!$this->hasContentTables()) {
            return response()->json([
                'success' => true,
                'message' => 'Estrutura de banners/categorias ainda nao migrada neste ambiente.',
                'data' => [
                    'banners' => [],
                    'categorias' => [],
                    'partial' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => Banner::orderBy('position')->orderByDesc('id')->get(),
                'categorias' => Categoria::orderBy('position')->orderByDesc('id')->get(),
            ],
        ]);
    }

    public function storeBanner(Request $request)
    {
        if (!Schema::hasTable('banners')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de banners indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

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

    public function updateBanner(Request $request, Banner $banner)
    {
        if (!Schema::hasTable('banners')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de banners indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

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

        $banner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Banner atualizado com sucesso.',
            'data' => $banner->fresh(),
        ]);
    }

    public function destroyBanner(Banner $banner)
    {
        if (!Schema::hasTable('banners')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de banners indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner removido com sucesso.',
        ]);
    }

    public function storeCategoria(Request $request)
    {
        if (!Schema::hasTable('categorias')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de categorias indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categorias,slug',
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
        ]);

        $categoria = Categoria::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'active' => $validated['active'] ?? true,
            'position' => $validated['position'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoria criada com sucesso.',
            'data' => $categoria,
        ], 201);
    }

    public function updateCategoria(Request $request, Categoria $categoria)
    {
        if (!Schema::hasTable('categorias')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de categorias indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categorias,slug,' . $categoria->id,
            'active' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['name']) && !isset($validated['slug']) && !$categoria->slug) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $categoria->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Categoria atualizada com sucesso.',
            'data' => $categoria->fresh(),
        ]);
    }

    public function destroyCategoria(Categoria $categoria)
    {
        if (!Schema::hasTable('categorias')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabela de categorias indisponivel neste ambiente. Execute as migrations.',
            ], 503);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria removida com sucesso.',
        ]);
    }
}
