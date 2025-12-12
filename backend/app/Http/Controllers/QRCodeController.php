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
     * Cliente escaneia empresa (inscrição)
     */
    public function escanearEmpresa(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $user = Auth::user();
            $validacao = $this->qrCodeService->validarCodigo($request->code);

            if (!$validacao['valido'] || $validacao['type'] !== 'empresa') {
                return response()->json(['success' => false, 'message' => 'QR Code inválido'], 400);
            }

            $empresa = $validacao['empresa'];
            $inscricao = InscricaoEmpresa::firstOrCreate(
                ['user_id' => $user->id, 'empresa_id' => $empresa->id],
                ['data_inscricao' => now(), 'bonus_adesao_resgatado' => false]
            );

            return response()->json([
                'success' => true,
                'data' => ['empresa' => $empresa, 'bonus_adesao' => $empresa->bonusAdesao]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao escanear empresa', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erro'], 500);
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
