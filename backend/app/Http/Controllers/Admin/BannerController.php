<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Listar todos os banners (Admin)
     */
    public function index()
    {
        $banners = Banner::orderBy('position')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'banners' => $banners
        ]);
    }

    /**
     * Criar novo banner
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB
            'link' => 'nullable|url|max:500',
            'active' => 'boolean',
            'position' => 'integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload da imagem
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('banners', 'public');
            }

            $banner = Banner::create([
                'title' => $request->title,
                'image_url' => $imagePath ? Storage::url($imagePath) : null,
                'link' => $request->link,
                'active' => $request->input('active', true),
                'position' => $request->input('position', 0),
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'payload' => $request->payload,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Banner criado com sucesso',
                'banner' => $banner
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir banner específico
     */
    public function show($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'banner' => $banner
        ]);
    }

    /**
     * Atualizar banner
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|url|max:500',
            'active' => 'boolean',
            'position' => 'integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload de nova imagem (se fornecida)
            if ($request->hasFile('image')) {
                // Deletar imagem antiga
                if ($banner->image_url) {
                    $oldPath = str_replace('/storage/', '', $banner->image_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $imagePath = $request->file('image')->store('banners', 'public');
                $banner->image_url = Storage::url($imagePath);
            }

            // Atualizar outros campos
            if ($request->has('title')) $banner->title = $request->title;
            if ($request->has('link')) $banner->link = $request->link;
            if ($request->has('active')) $banner->active = $request->active;
            if ($request->has('position')) $banner->position = $request->position;
            if ($request->has('starts_at')) $banner->starts_at = $request->starts_at;
            if ($request->has('ends_at')) $banner->ends_at = $request->ends_at;
            if ($request->has('payload')) $banner->payload = $request->payload;

            $banner->save();

            return response()->json([
                'success' => true,
                'message' => 'Banner atualizado com sucesso',
                'banner' => $banner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar banner
     */
    public function destroy($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner não encontrado'
            ], 404);
        }

        try {
            // Deletar imagem do storage
            if ($banner->image_url) {
                $path = str_replace('/storage/', '', $banner->image_url);
                Storage::disk('public')->delete($path);
            }

            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Banner deletado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar banner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternar status ativo/inativo
     */
    public function toggleStatus($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner não encontrado'
            ], 404);
        }

        $banner->active = !$banner->active;
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Status do banner atualizado',
            'banner' => $banner
        ]);
    }

    /**
     * Reordenar banners
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:banners,id',
            'banners.*.position' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->banners as $bannerData) {
                Banner::where('id', $bannerData['id'])->update([
                    'position' => $bannerData['position']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banners reordenados com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao reordenar banners: ' . $e->getMessage()
            ], 500);
        }
    }
}
