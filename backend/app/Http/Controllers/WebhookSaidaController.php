<?php

namespace App\Http\Controllers;

use App\Models\WebhookSaida;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WebhookSaidaController extends Controller
{
    /** Lista webhooks da empresa / todos (admin) */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $query = WebhookSaida::query();

        if ($empresa) {
            $query->where('empresa_id', $empresa->id);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /** Cadastra novo webhook */
    public function store(Request $request)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $request->validate([
            'url'     => 'required|url',
            'eventos' => 'required|array|min:1',
            'eventos.*' => 'in:checkin,resgate,nivel_up,badge,nps,desafio_concluido',
        ]);

        $webhook = WebhookSaida::create([
            'empresa_id' => $empresa?->id,
            'url'        => $request->url,
            'segredo'    => Str::random(40),
            'eventos'    => $request->eventos,
            'ativo'      => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => array_merge($webhook->toArray(), ['segredo' => $webhook->segredo]), // mostrar só na criação
        ], 201);
    }

    /** Atualiza webhook */
    public function update(Request $request, $id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $webhook = WebhookSaida::where('id', $id)
            ->where(fn ($q) => $empresa ? $q->where('empresa_id', $empresa->id) : $q)
            ->firstOrFail();

        $request->validate([
            'url'     => 'sometimes|url',
            'eventos' => 'sometimes|array|min:1',
            'ativo'   => 'sometimes|boolean',
        ]);

        $webhook->update($request->only(['url', 'eventos', 'ativo']));

        return response()->json(['success' => true, 'data' => $webhook]);
    }

    /** Remove webhook */
    public function destroy($id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $webhook = WebhookSaida::where('id', $id)
            ->where(fn ($q) => $empresa ? $q->where('empresa_id', $empresa->id) : $q)
            ->firstOrFail();

        $webhook->delete();
        return response()->json(['success' => true]);
    }

    /** Histórico de envios */
    public function logs($id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $webhook = WebhookSaida::where('id', $id)
            ->where(fn ($q) => $empresa ? $q->where('empresa_id', $empresa->id) : $q)
            ->firstOrFail();

        $logs = WebhookLog::where('webhook_id', $webhook->id)
            ->orderByDesc('enviado_em')
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $logs]);
    }

    /** Gera novo segredo para o webhook */
    public function rotacionarSegredo($id)
    {
        $user    = Auth::user();
        $empresa = $user->empresa;

        $webhook = WebhookSaida::where('id', $id)
            ->where(fn ($q) => $empresa ? $q->where('empresa_id', $empresa->id) : $q)
            ->firstOrFail();

        $segredo = Str::random(40);
        $webhook->update(['segredo' => $segredo]);

        return response()->json(['success' => true, 'segredo' => $segredo]);
    }
}
