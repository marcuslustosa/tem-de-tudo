<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\User;
use App\Services\BonusAdesaoService;
use App\Services\ClienteQrCodeService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class QRCodeController extends Controller
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function meuQRCode()
    {
        try {
            $user = Auth::user();
            $qrData = app(ClienteQrCodeService::class)->gerar($user);
            $qrSvg = QrCodeGenerator::format('svg')
                ->size(300)
                ->generate($qrData['code']);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $qrData['code'],
                    'qr_url' => null,
                    'qr_image' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
                    'qr_svg' => $qrSvg,
                    'version' => $qrData['version'],
                    'expires_at' => $qrData['expires_at']->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter QR Code', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Erro ao obter QR Code'], 500);
        }
    }

    public function qrCodeEmpresa()
    {
        try {
            $user = Auth::user();
            $empresa = $user->empresa;

            if (!$empresa) {
                return response()->json(['success' => false, 'message' => 'Empresa nao encontrada'], 404);
            }

            $qrCode = $this->qrCodeService->gerarQRCodeEmpresa($empresa);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $qrCode->code,
                    'qr_url' => $this->qrCodeService->getQRCodeUrl($qrCode),
                    'qr_image' => $this->qrCodeService->getQRCodeImageDataUrl($qrCode),
                    'empresa' => ['id' => $empresa->id, 'nome' => $empresa->nome],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter QR Code da empresa', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Erro ao obter QR Code'], 500);
        }
    }

    /**
     * @deprecated Fluxo legado anterior ao caminho canonico da Fase 2/3.
     *
     * Mantido apenas por compatibilidade defensiva. Nao pode mais:
     * - liberar bonus de adesao automaticamente;
     * - gerar cupom de bonus;
     * - marcar `inscricoes_empresa.bonus_adesao_resgatado`.
     *
     * Caminho canonico atual:
     * - vinculo cliente/empresa: POST /api/cliente/vincular-empresa-qrcode
     * - leitura/estado do bonus: BonusAdesaoService
     * - validacao real: empresa autenticada lendo QR do cliente
     */
    public function escanearEmpresa(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $user = Auth::user();
            $this->logLegacyUsage(__METHOD__, [
                'user_id' => $user?->id,
            ]);

            $validacao = $this->qrCodeService->validarCodigo($request->code);
            if (!$validacao['valido'] || $validacao['type'] !== 'empresa') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code invalido ou nao pertence a uma empresa',
                ], 400);
            }

            $empresa = $validacao['empresa'];
            if (!$empresa instanceof Empresa || !$empresa->isPubliclyVisible()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa indisponivel para vinculacao por este fluxo legado.',
                    'deprecated' => $this->legacyMetadata('cliente'),
                ], 404);
            }

            $inscricao = InscricaoEmpresa::where('user_id', $user->id)
                ->where('empresa_id', $empresa->id)
                ->first();

            $primeiraVisita = !$inscricao;

            if (!$inscricao) {
                $inscricao = InscricaoEmpresa::create([
                    'user_id' => $user->id,
                    'empresa_id' => $empresa->id,
                    'data_inscricao' => now(),
                    'bonus_adesao_resgatado' => false,
                    'ultima_visita' => now(),
                ]);
            } else {
                $inscricao->update(['ultima_visita' => now()]);
            }

            $bonusStatus = app(BonusAdesaoService::class)->evaluateCustomerBonus($empresa, $user);

            return response()->json([
                'success' => true,
                'message' => $primeiraVisita ? 'Vinculado com sucesso a empresa' : 'Check-in realizado',
                'data' => [
                    'empresa' => $empresa,
                    'primeira_visita' => $primeiraVisita,
                    'bonus_liberado' => null,
                    'bonus_adesao' => $bonusStatus,
                    'inscricao' => $inscricao,
                ],
                'deprecated' => $this->legacyMetadata('cliente'),
            ], $primeiraVisita ? 201 : 200);
        } catch (\Exception $e) {
            Log::error('Erro ao escanear empresa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar QR Code',
            ], 500);
        }
    }

    /**
     * @deprecated Fluxo legado anterior ao uso canonico de:
     * - POST /api/empresa/clientes/qrcode/consultar
     * - POST /api/empresa/bonus-adesao/{id}/validar
     * - POST /api/empresa/escanear-cliente para pontos/check-in
     */
    public function escanearCliente(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);

            $userEmpresa = Auth::user();
            $this->logLegacyUsage(__METHOD__, [
                'user_id' => $userEmpresa?->id,
            ]);
            $empresa = $userEmpresa->empresa;
            $decodedQr = app(ClienteQrCodeService::class)->decodificar((string) $request->code);

            if (!$decodedQr) {
                return response()->json(['success' => false, 'message' => 'QR Code invalido'], 400);
            }

            $cliente = User::query()->find((int) $decodedQr['user_id']);
            if (!$cliente) {
                return response()->json(['success' => false, 'message' => 'Cliente nao encontrado'], 404);
            }

            $inscricao = InscricaoEmpresa::where('user_id', $cliente->id)
                ->where('empresa_id', $empresa->id)
                ->first();

            if (!$inscricao) {
                return response()->json(['success' => false, 'message' => 'Cliente nao inscrito'], 400);
            }

            $inscricao->update(['ultima_visita' => now()]);

            return response()->json([
                'success' => true,
                'data' => ['cliente' => $cliente, 'inscricao' => $inscricao],
                'deprecated' => $this->legacyMetadata('empresa'),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao escanear cliente', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Erro'], 500);
        }
    }

    private function logLegacyUsage(string $method, array $context = []): void
    {
        Log::warning('Fluxo legado de QRCodeController acionado', array_merge([
            'method' => $method,
        ], $context));
    }

    private function legacyMetadata(string $actor): array
    {
        return [
            'is_legacy' => true,
            'actor' => $actor,
            'message' => 'Este fluxo esta depreciado e nao pode validar bonus de adesao.',
            'canonical_flow' => [
                'cliente' => [
                    'vinculo_empresa_qr' => '/api/cliente/vincular-empresa-qrcode',
                    'bonus_disponivel' => '/api/cliente/bonus-adesao/disponivel/{empresa_id}',
                ],
                'empresa' => [
                    'consultar_cliente_qr' => '/api/empresa/clientes/qrcode/consultar',
                    'validar_bonus_adesao' => '/api/empresa/bonus-adesao/{id}/validar',
                    'escanear_cliente_fidelidade' => '/api/empresa/escanear-cliente',
                ],
            ],
        ];
    }
}
