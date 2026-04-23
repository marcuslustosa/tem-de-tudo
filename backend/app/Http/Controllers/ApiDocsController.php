<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ApiDocsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'versions' => ['v1', 'v2'],
                'default' => 'v2',
                'specs' => [
                    'v1' => '/api/docs/openapi/v1',
                    'v2' => '/api/docs/openapi/v2',
                ],
            ],
        ]);
    }

    public function show(string $version): Response
    {
        $normalizedVersion = strtolower(trim($version));
        if (!in_array($normalizedVersion, ['v1', 'v2'], true)) {
            abort(404, 'Versao OpenAPI nao suportada');
        }

        $path = base_path(sprintf('openapi/%s.yaml', $normalizedVersion));
        if (!File::exists($path)) {
            abort(404, 'Especificacao nao encontrada');
        }

        return response(File::get($path), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}

