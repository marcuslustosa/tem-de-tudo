<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\QRCode;
use App\Models\Empresa;
use App\Models\CheckIn;
use App\Models\User;
use App\Models\Ponto;

class QRCodeController extends Controller
{
    /**
     * Escanear QR Code e obter informações
     */
    public function scanQR(Request $request, string $qrCode): JsonResponse
    {
        try {
            $qr = QRCode::with('empresa')
                ->where('code', $qrCode)
                ->first();

            if (!$qr) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code não encontrado'
                ], 404);
            }

            if (!$qr->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code inativo ou estabelecimento fechado'
                ], 400);
            }

            // Verificar se o usuário já fez check-in hoje
            $user = Auth::user();
            $checkinHoje = CheckIn::where('user_id', $user->id)
                ->where('empresa_id', $qr->empresa_id)
                ->whereDate('created_at', today())
                ->first();

            $canCheckin = !$checkinHoje;
            $activeOffers = $qr->getActiveOffers();

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => [
                        'id' => $qr->id,
                        'name' => $qr->name,
                        'location' => $qr->location
                    ],
                    'empresa' => [
                        'id' => $qr->empresa->id,
                        'nome' => $qr->empresa->nome,
                        'categoria' => $qr->empresa->categoria,
                        'logo_url' => $qr->empresa->logo_url,
                        'points_multiplier' => $qr->empresa->points_multiplier
                    ],
                    'can_checkin' => $canCheckin,
                    'checkin_today' => $checkinHoje,
                    'active_offers' => $activeOffers,
                    'points_info' => [
                        'base_rule' => 'R$ 1,00 = 1 ponto',
                        'multiplier' => $qr->empresa->points_multiplier,
                        'bonus_offers' => $activeOffers
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fazer check-in via QR Code
     */
    public function checkinViaQR(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'qr_code' => 'required|string|size:32',
                'valor_compra' => 'required|numeric|min:0.01',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'observacoes' => 'nullable|string|max:500'
            ]);

            $user = Auth::user();
            $qr = QRCode::with('empresa')->where('code', $request->qr_code)->first();

            if (!$qr || !$qr->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code inválido ou inativo'
                ], 400);
            }

            // Verificar se já fez check-in hoje
            $checkinHoje = CheckIn::where('user_id', $user->id)
                ->where('empresa_id', $qr->empresa_id)
                ->whereDate('created_at', today())
                ->first();

            if ($checkinHoje) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já fez check-in neste estabelecimento hoje'
                ], 400);
            }

            // Calcular pontos com multiplicador da empresa
            $pontosBase = floor($request->valor_compra); // 1 ponto por real
            $multiplicador = $qr->empresa->points_multiplier ?? 1.0;
            $pontosCalculados = floor($pontosBase * $multiplicador);

            // Aplicar bônus das ofertas ativas
            $activeOffers = $qr->getActiveOffers();
            $bonusPoints = 0;
            
            foreach ($activeOffers as $offer) {
                if ($offer['type'] === 'bonus_points') {
                    if ($request->valor_compra >= ($offer['min_amount'] ?? 0)) {
                        $bonusPoints += $offer['bonus_points'] ?? 0;
                    }
                }
            }

            $pontosCalculados += $bonusPoints;
            $pontosCalculados = max(1, $pontosCalculados); // Mínimo 1 ponto

            // Criar check-in
            $checkin = CheckIn::create([
                'user_id' => $user->id,
                'empresa_id' => $qr->empresa_id,
                'qr_code_id' => $qr->id,
                'valor_compra' => $request->valor_compra,
                'pontos_calculados' => $pontosCalculados,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'observacoes' => $request->observacoes,
                'status' => 'approved', // Auto-aprovado para QR Codes
                'codigo_validacao' => strtoupper(\Illuminate\Support\Str::random(8)),
                'aprovado_em' => now(),
                'bonus_applied' => $bonusPoints > 0 ? $activeOffers : null
            ]);

            // Adicionar pontos diretamente (sem pendência)
            $user->increment('pontos', $pontosCalculados);

            // Registrar no histórico
            Ponto::create([
                'user_id' => $user->id,
                'empresa_id' => $qr->empresa_id,
                'checkin_id' => $checkin->id,
                'pontos' => $pontosCalculados,
                'descricao' => "Check-in via QR no {$qr->empresa->nome} - R$ " . number_format($request->valor_compra, 2, ',', '.'),
                'tipo' => 'earn'
            ]);

            // Registrar uso do QR Code
            $qr->recordUsage();

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado com sucesso!',
                'data' => [
                    'checkin' => $checkin,
                    'pontos_ganhos' => $pontosCalculados,
                    'bonus_aplicado' => $bonusPoints,
                    'total_pontos' => $user->fresh()->pontos,
                    'ofertas_aplicadas' => array_filter($activeOffers, fn($o) => $o['type'] === 'bonus_points')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar QR Code para empresa (admin)
     */
    public function generateQR(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'empresa_id' => 'required|exists:empresas,id',
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:255',
                'offers' => 'nullable|array'
            ]);

            // Verificar se o usuário tem permissão para esta empresa
            $empresa = Empresa::findOrFail($request->empresa_id);
            
            $qr = QRCode::create([
                'empresa_id' => $request->empresa_id,
                'name' => $request->name,
                'location' => $request->location,
                'active_offers' => $request->offers
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR Code gerado com sucesso!',
                'data' => [
                    'qr_code' => $qr,
                    'url' => $qr->url,
                    'qr_data' => $qr->qr_data
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar QR Codes de uma empresa
     */
    public function listQRCodes(Request $request, int $empresaId): JsonResponse
    {
        try {
            $qrCodes = QRCode::byEmpresa($empresaId)
                ->with('empresa:id,nome')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $qrCodes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar ofertas de um QR Code
     */
    public function updateOffers(Request $request, int $qrId): JsonResponse
    {
        try {
            $request->validate([
                'offers' => 'required|array'
            ]);

            $qr = QRCode::findOrFail($qrId);
            $qr->update(['active_offers' => $request->offers]);

            return response()->json([
                'success' => true,
                'message' => 'Ofertas atualizadas com sucesso!',
                'data' => $qr->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar/desativar QR Code
     */
    public function toggleStatus(Request $request, int $qrId): JsonResponse
    {
        try {
            $qr = QRCode::findOrFail($qrId);
            $qr->update(['active' => !$qr->active]);

            $status = $qr->active ? 'ativado' : 'desativado';

            return response()->json([
                'success' => true,
                'message' => "QR Code {$status} com sucesso!",
                'data' => $qr
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas de uso dos QR Codes
     */
    public function getQRStats(Request $request, int $empresaId): JsonResponse
    {
        try {
            $qrCodes = QRCode::byEmpresa($empresaId)->get();
            
            $stats = [
                'total_qr_codes' => $qrCodes->count(),
                'active_qr_codes' => $qrCodes->where('active', true)->count(),
                'total_usage' => $qrCodes->sum('usage_count'),
                'most_used' => $qrCodes->sortByDesc('usage_count')->first(),
                'recent_activity' => $qrCodes->where('last_used_at', '>', now()->subDays(7))->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}