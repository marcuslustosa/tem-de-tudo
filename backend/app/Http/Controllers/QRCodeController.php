<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QRCodeService;
use App\Models\InscricaoEmpresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QRCodeController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Obter QR Code do cliente autenticado
     */
    public function meuQRCode()
    {
        try {
            $user = Auth::user();
            $qrCode = $this->qrCodeService->gerarQRCodeCliente($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $qrCode->code,
                    'qr_image' => $this->qrCodeService->getQRCodeImageDataUrl($qrCode)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter QR Code', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erro ao obter QR Code'], 500);
        }
    }

    /**
     * Obter QR Code da empresa
     */
    public function qrCodeEmpresa()
    {
        try {
            $user = Auth::user();
            $empresa = $user->empresa;

            if (!$empresa) {
                return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 404);
            }

            $qrCode = $this->qrCodeService->gerarQRCodeEmpresa($empresa);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $qrCode->code,
                    'qr_image' => $this->qrCodeService->getQRCodeImageDataUrl($qrCode),
                    'empresa' => ['id' => $empresa->id, 'nome' => $empresa->nome]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter QR Code da empresa', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erro ao obter QR Code'], 500);
        }
    }

    /**
     * Cliente escaneia empresa (inscrição + bônus automático)
     */
    public function escanearEmpresa(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $user = Auth::user();
            
            // Validar QR Code
            $validacao = $this->qrCodeService->validarCodigo($request->code);

            if (!$validacao['valido'] || $validacao['type'] !== 'empresa') {
                return response()->json([
                    'success' => false, 
                    'message' => 'QR Code inválido ou não pertence a uma empresa'
                ], 400);
            }

            $empresa = $validacao['empresa'];
            
            // Verificar se já está inscrito
            $inscricao = InscricaoEmpresa::where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->first();
            
            $primeiraVisita = !$inscricao;
            
            // Criar ou atualizar inscrição
            if (!$inscricao) {
                $inscricao = InscricaoEmpresa::create([
                    'user_id' => $user->id,
                    'empresa_id' => $empresa->id,
                    'data_inscricao' => now(),
                    'bonus_adesao_resgatado' => false,
                    'ultima_visita' => now()
                ]);
            } else {
                $inscricao->update(['ultima_visita' => now()]);
            }
            
            // Buscar bônus de adesão da empresa
            $bonusAdesao = \App\Models\BonusAdesao::where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->first();
            
            $bonusLiberado = null;
            
            // Se é primeira visita E tem bônus E não foi resgatado
            if ($primeiraVisita && $bonusAdesao && !$inscricao->bonus_adesao_resgatado) {
                // Criar cupom de bônus de adesão para o cliente
                $cupom = \App\Models\Cupom::create([
                    'user_id' => $user->id,
                    'empresa_id' => $empresa->id,
                    'bonus_adesao_id' => $bonusAdesao->id,
                    'tipo' => 'bonus_adesao',
                    'codigo' => 'ADESAO-' . strtoupper(uniqid()),
                    'titulo' => $bonusAdesao->titulo,
                    'descricao' => $bonusAdesao->descricao,
                    'tipo_desconto' => $bonusAdesao->tipo_desconto,
                    'valor_desconto' => $bonusAdesao->valor_desconto,
                    'data_emissao' => now(),
                    'validade' => now()->addDays(90), // 90 dias para usar
                    'status' => 'ativo'
                ]);
                
                // Marcar bônus como resgatado
                $inscricao->update(['bonus_adesao_resgatado' => true]);
                
                $bonusLiberado = [
                    'bonus' => $bonusAdesao,
                    'cupom' => $cupom,
                    'mensagem' => '🎉 Bônus de Boas-Vindas Liberado!'
                ];
                
                Log::info('Bônus de adesão liberado', [
                    'user_id' => $user->id,
                    'empresa_id' => $empresa->id,
                    'cupom_id' => $cupom->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $primeiraVisita ? 'Vinculado com sucesso à empresa!' : 'Check-in realizado!',
                'data' => [
                    'empresa' => $empresa,
                    'primeira_visita' => $primeiraVisita,
                    'bonus_liberado' => $bonusLiberado,
                    'inscricao' => $inscricao
                ]
            ], $primeiraVisita ? 201 : 200);
            
        } catch (\Exception $e) {
            Log::error('Erro ao escanear empresa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Erro ao processar QR Code'
            ], 500);
        }
    }

    /**
     * Empresa escaneia cliente
     */
    public function escanearCliente(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $userEmpresa = Auth::user();
            $empresa = $userEmpresa->empresa;
            $validacao = $this->qrCodeService->validarCodigo($request->code);

            if (!$validacao['valido'] || $validacao['type'] !== 'cliente') {
                return response()->json(['success' => false, 'message' => 'QR Code inválido'], 400);
            }

            $cliente = $validacao['user'];
            $inscricao = InscricaoEmpresa::where('user_id', $cliente->id)
                ->where('empresa_id', $empresa->id)
                ->first();

            if (!$inscricao) {
                return response()->json(['success' => false, 'message' => 'Cliente não inscrito'], 400);
            }

            $inscricao->update(['ultima_visita' => now()]);

            return response()->json([
                'success' => true,
                'data' => ['cliente' => $cliente, 'inscricao' => $inscricao]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao escanear cliente', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erro'], 500);
        }
    }
}
