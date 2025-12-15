<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Promocao;
use App\Models\CheckIn;
use Carbon\Carbon;

class EmpresaPromocaoController extends Controller
{
    /**
     * Listar promoções da empresa
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $promocoes = Promocao::where('empresa_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($promo) {
                    return [
                        'id' => $promo->id,
                        'titulo' => $promo->titulo,
                        'descricao' => $promo->descricao,
                        'desconto' => $promo->desconto,
                        'pontos_necessarios' => $promo->pontos_necessarios ?? 0,
                        'data_inicio' => $promo->data_inicio,
                        'validade' => $promo->validade,
                        'imagem' => $promo->imagem,
                        'status' => $promo->status,
                        'visualizacoes' => $promo->visualizacoes ?? 0,
                        'resgates' => $promo->resgates ?? 0,
                        'usos' => $promo->usos ?? 0
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $promocoes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar promoções: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar nova promoção
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $request->validate([
                'titulo' => 'required|string|max:60',
                'descricao' => 'required|string|max:200',
                'desconto' => 'required|numeric|min:1|max:100',
                'data_inicio' => 'required|date',
                'validade' => 'required|date|after:data_inicio',
                'pontos_necessarios' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:ativa,pausada',
                'imagem' => 'nullable|image|max:2048' // 2MB max
            ]);

            $imagemPath = null;
            if ($request->hasFile('imagem')) {
                $imagemPath = $request->file('imagem')->store('promocoes', 'public');
            }

            $promocao = Promocao::create([
                'empresa_id' => $user->id,
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'desconto' => $request->desconto,
                'pontos_necessarios' => $request->pontos_necessarios ?? 0,
                'data_inicio' => $request->data_inicio,
                'validade' => $request->validade,
                'imagem' => $imagemPath ? '/storage/' . $imagemPath : null,
                'status' => $request->status ?? 'ativa',
                'visualizacoes' => 0,
                'resgates' => 0,
                'usos' => 0
            ]);

            // Se marcou para notificar, enviar notificações
            if ($request->notificar) {
                // TODO: Implementar envio de notificações push
            }

            return response()->json([
                'success' => true,
                'message' => 'Promoção criada com sucesso!',
                'data' => $promocao
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar promoção
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $promocao = Promocao::where('id', $id)
                ->where('empresa_id', $user->id)
                ->firstOrFail();

            $request->validate([
                'titulo' => 'required|string|max:60',
                'descricao' => 'required|string|max:200',
                'desconto' => 'required|numeric|min:1|max:100',
                'data_inicio' => 'required|date',
                'validade' => 'required|date',
                'pontos_necessarios' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:ativa,pausada',
                'imagem' => 'nullable|image|max:2048'
            ]);

            if ($request->hasFile('imagem')) {
                // Deletar imagem antiga
                if ($promocao->imagem) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $promocao->imagem));
                }
                $imagemPath = $request->file('imagem')->store('promocoes', 'public');
                $promocao->imagem = '/storage/' . $imagemPath;
            }

            $promocao->update([
                'titulo' => $request->titulo,
                'descricao' => $request->descricao,
                'desconto' => $request->desconto,
                'pontos_necessarios' => $request->pontos_necessarios ?? 0,
                'data_inicio' => $request->data_inicio,
                'validade' => $request->validade,
                'status' => $request->status ?? $promocao->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promoção atualizada com sucesso!',
                'data' => $promocao
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pausar promoção
     */
    public function pausar(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $promocao = Promocao::where('id', $id)
                ->where('empresa_id', $user->id)
                ->firstOrFail();

            $promocao->update(['status' => 'pausada']);

            return response()->json([
                'success' => true,
                'message' => 'Promoção pausada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao pausar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar promoção
     */
    public function ativar(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $promocao = Promocao::where('id', $id)
                ->where('empresa_id', $user->id)
                ->firstOrFail();

            $promocao->update(['status' => 'ativa']);

            return response()->json([
                'success' => true,
                'message' => 'Promoção ativada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar promoção
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $promocao = Promocao::where('id', $id)
                ->where('empresa_id', $user->id)
                ->firstOrFail();

            // Deletar imagem se existir
            if ($promocao->imagem) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $promocao->imagem));
            }

            $promocao->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promoção excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir promoção: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar check-in via QR Code
     */
    public function registrarCheckin(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $request->validate([
                'qr_code' => 'required|string'
            ]);

            // Decodificar QR Code (formato: user_id ou hash)
            $clienteId = $this->decodificarQRCode($request->qr_code);

            if (!$clienteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code inválido'
                ], 400);
            }

            $cliente = User::where('id', $clienteId)
                ->where('perfil', 'cliente')
                ->first();

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não encontrado'
                ], 404);
            }

            // Registrar check-in
            $pontos = 10; // Padrão: 10 pontos por check-in
            
            CheckIn::create([
                'user_id' => $clienteId,
                'empresa_id' => $user->id,
                'pontos' => $pontos,
                'data' => now()
            ]);

            // Adicionar pontos ao cliente
            $cliente->pontos += $pontos;
            $cliente->save();

            return response()->json([
                'success' => true,
                'message' => 'Check-in registrado com sucesso!',
                'cliente' => [
                    'nome' => $cliente->name,
                    'pontos_totais' => $cliente->pontos
                ],
                'pontos_ganhos' => $pontos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar clientes da empresa
     */
    public function clientes(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $clientes = CheckIn::where('empresa_id', $user->id)
                ->select('user_id')
                ->distinct()
                ->with(['user:id,name,email,pontos'])
                ->get()
                ->map(function ($checkin) use ($user) {
                    $cliente = $checkin->user;
                    $ultimoCheckin = CheckIn::where('user_id', $cliente->id)
                        ->where('empresa_id', $user->id)
                        ->latest('data')
                        ->first();
                    
                    $totalCheckins = CheckIn::where('user_id', $cliente->id)
                        ->where('empresa_id', $user->id)
                        ->count();

                    return [
                        'id' => $cliente->id,
                        'nome' => $cliente->name,
                        'email' => $cliente->email,
                        'pontos' => $cliente->pontos,
                        'ultimo_checkin_dias' => $ultimoCheckin ? Carbon::parse($ultimoCheckin->data)->diffInDays(now()) : 999,
                        'total_checkins' => $totalCheckins
                    ];
                });

            $hoje = now();
            $stats = [
                'total' => $clientes->count(),
                'hoje' => CheckIn::where('empresa_id', $user->id)
                    ->whereDate('data', $hoje)
                    ->distinct('user_id')
                    ->count(),
                'mes' => CheckIn::where('empresa_id', $user->id)
                    ->whereMonth('data', $hoje->month)
                    ->whereYear('data', $hoje->year)
                    ->distinct('user_id')
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas para notificações
     */
    public function notificacoesStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $clientesIds = CheckIn::where('empresa_id', $user->id)
                ->distinct('user_id')
                ->pluck('user_id');

            $todos = $clientesIds->count();
            
            $ativos = CheckIn::where('empresa_id', $user->id)
                ->where('data', '>=', now()->subDays(30))
                ->distinct('user_id')
                ->count();

            $inativos = $todos - $ativos;

            $vip = User::whereIn('id', $clientesIds)
                ->where('pontos', '>=', 100)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'todos' => $todos,
                    'ativos' => $ativos,
                    'inativos' => $inativos,
                    'vip' => $vip
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar notificações push
     */
    public function enviarNotificacao(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || $user->perfil !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autorizado'
                ], 403);
            }

            $request->validate([
                'titulo' => 'required|string|max:50',
                'mensagem' => 'required|string|max:150',
                'target' => 'required|in:todos,ativos,inativos,vip'
            ]);

            // Buscar clientes alvo
            $query = CheckIn::where('empresa_id', $user->id)
                ->distinct('user_id');

            if ($request->target === 'ativos') {
                $query->where('data', '>=', now()->subDays(30));
            } elseif ($request->target === 'inativos') {
                $inativos = CheckIn::where('empresa_id', $user->id)
                    ->where('data', '<', now()->subDays(30))
                    ->distinct('user_id')
                    ->pluck('user_id');
                $query->whereIn('user_id', $inativos);
            }

            $clientesIds = $query->pluck('user_id');

            if ($request->target === 'vip') {
                $clientesIds = User::whereIn('id', $clientesIds)
                    ->where('pontos', '>=', 100)
                    ->pluck('id');
            }

            // TODO: Implementar envio real de push notifications via Firebase
            $enviados = $clientesIds->count();

            return response()->json([
                'success' => true,
                'message' => 'Notificações enviadas com sucesso!',
                'enviados' => $enviados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar notificações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decodificar QR Code do cliente
     */
    private function decodificarQRCode($qrCode)
    {
        // Formato simples: user_id direto
        if (is_numeric($qrCode)) {
            return (int) $qrCode;
        }

        // Formato com prefixo: CLIENTE-123
        if (str_starts_with($qrCode, 'CLIENTE-')) {
            return (int) str_replace('CLIENTE-', '', $qrCode);
        }

        // Formato base64
        try {
            $decoded = base64_decode($qrCode);
            if (is_numeric($decoded)) {
                return (int) $decoded;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
