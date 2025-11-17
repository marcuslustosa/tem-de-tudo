<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::all(['id', 'name', 'services']);
        return response()->json($empresas);
    }

    /**
     * Listar empresas para cadastro de funcionários (público)
     */
    public function listEmpresas()
    {
        try {
            $empresas = Empresa::where('ativo', true)
                ->select('id', 'nome', 'endereco', 'telefone')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $empresas
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao listar empresas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar empresas'
            ], 500);
        }
    }

    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);
        return response()->json($empresa);
    }
}
