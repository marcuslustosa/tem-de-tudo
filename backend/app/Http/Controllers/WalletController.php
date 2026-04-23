<?php

namespace App\Http\Controllers;

use App\Models\PontoTransacao;
use App\Models\User;
use App\Services\ClienteQrCodeService;
use App\Services\LedgerService;
use App\Services\LoyaltyProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * WalletController - Sistema de Fidelidade Completo
 *
 * Gerencia cartao virtual, pontos, niveis e historico.
 * Usa LedgerService para todas operações de pontos.
 */
class WalletController extends Controller
{
    public function __construct(
        private readonly ClienteQrCodeService $clienteQrCodeService,
        private readonly LedgerService $ledgerService,
        private readonly LoyaltyProgramService $loyaltyProgramService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Retorna dados do cartao de fidelidade do usuario.
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();
        $qrTtl = (int) config('services.wallet.client_qr_ttl', ClienteQrCodeService::DEFAULT_TTL_SECONDS);
        $qrData = $this->clienteQrCodeService->gerar($user, $qrTtl);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'pontos' => $user->pontos ?? 0,
                'nivel' => $user->nivel ?? 'bronze',
                'nivel_formatado' => ucfirst((string) ($user->nivel ?? 'bronze')),
                'qr_code' => $qrData['code'],
                'qr_code_versao' => $qrData['version'],
                'qr_code_expira_em' => $qrData['expires_at']->toIso8601String(),
                'cor' => $this->corPorNivel($user->nivel),
                'proximos_pontos' => $this->pontosProximoNivel((int) ($user->pontos ?? 0)),
            ],
        ]);
    }

    /**
     * Adiciona pontos ao usuario (estabelecimento/admin).
     */
    public function adicionarPontos(Request $request): JsonResponse
    {
        $operador = Auth::user();
        if (!$this->canManagePoints($operador)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado para adicionar pontos.',
            ], 403);
        }

        $validated = $request->validate([
            'cliente_id' => 'required|exists:users,id',
            'pontos' => 'required|integer|min:1|max:1000',
            'valor_compra' => 'required|numeric|min:0',
        ]);

        try {
            $cliente = User::find((int) $validated['cliente_id']);
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente nao encontrado.',
                ], 404);
            }

            if (!$this->isCliente($cliente)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pontos so podem ser creditados para usuarios com perfil cliente.',
                ], 422);
            }

            $pontosAnteriores = $this->ledgerService->getBalance($cliente->id);
            $nivelAnterior = (string) ($cliente->nivel ?? 'bronze');
            $pontosAdicionados = (int) $validated['pontos'];

            // Usa LedgerService para adicionar pontos
            $ledger = $this->ledgerService->credit(
                userId: $cliente->id,
                points: $pontosAdicionados,
                description: "Compra no valor de R$ " . number_format((float) $validated['valor_compra'], 2, ',', '.'),
                options: [
                    'company_id' => $operador->empresa_id ?? null,
                    'type' => 'earn',
                    'source' => 'api',
                    'metadata' => [
                        'valor_compra' => $validated['valor_compra'],
                        'estabelecimento_id' => $operador->id,
                        'estabelecimento_nome' => $operador->name,
                    ],
                ]
            );

            // Atualiza nível
            $cliente->refresh();
            $novoNivel = $this->calcularNivel((int) $cliente->pontos);
            if ($cliente->nivel !== $novoNivel) {
                $cliente->nivel = $novoNivel;
                $cliente->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pontos adicionados com sucesso!',
                'data' => [
                    'pontos_anteriores' => $pontosAnteriores,
                    'pontos_adicionados' => $pontosAdicionados,
                    'pontos_atuais' => $cliente->pontos,
                    'nivel_anterior' => $nivelAnterior,
                    'nivel_atual' => $cliente->nivel,
                    'nivel_subiu' => $nivelAnterior !== $cliente->nivel,
                    'ledger_id' => $ledger->id,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?? 'Falha ao adicionar pontos.',
            ], 500);
        }
    }

    /**
     * Resgata pontos (troca por beneficios).
     */
    public function resgatarPontos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pontos' => 'required|integer|min:1',
            'descricao' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        try {
            $pontosSolicitados = (int) $validated['pontos'];
            $minimoResgate = $this->loyaltyProgramService->minRedeemPoints();
            if ($pontosSolicitados < $minimoResgate) {
                return response()->json([
                    'success' => false,
                    'message' => "Resgate minimo de {$minimoResgate} pontos.",
                ], 422);
            }

            $pontosAnteriores = $this->ledgerService->getBalance($user->id);
            $nivelAnterior = (string) ($user->nivel ?? 'bronze');

            // Usa LedgerService para debitar pontos
            $ledger = $this->ledgerService->debit(
                userId: $user->id,
                points: $pontosSolicitados,
                description: $validated['descricao'],
                options: [
                    'type' => 'redeem',
                    'source' => 'api',
                ]
            );

            // Atualiza nível
            $user->refresh();
            $novoNivel = $this->calcularNivel((int) $user->pontos);
            if ($user->nivel !== $novoNivel) {
                $user->nivel = $novoNivel;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pontos resgatados com sucesso!',
                'data' => [
                    'pontos_anteriores' => $pontosAnteriores,
                    'pontos_resgatados' => $pontosSolicitados,
                    'pontos_atuais' => $user->pontos,
                    'nivel_anterior' => $nivelAnterior,
                    'nivel_atual' => $user->nivel,
                    'ledger_id' => $ledger->id,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?? 'Falha ao resgatar pontos.',
            ], 500);
        }
    }

    /**
     * Historico de transacoes de pontos.
     */
    public function historico(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        // Usa LedgerService para buscar histórico
        $transacoes = $this->ledgerService->getHistory(
            userId: $user->id,
            limit: $perPage,
            offset: ($page - 1) * $perPage
        );

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'data' => $transacoes,
                'total' => $transacoes->count(),
            ],
        ]);
    }

    /**
     * Valida QR Code do cliente (para estabelecimento escanear).
     */
    public function validarQRCode(Request $request): JsonResponse
    {
        $operador = Auth::user();
        if (!$this->canManagePoints($operador)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado para validar QR Code.',
            ], 403);
        }

        $validated = $request->validate([
            'qr_code' => 'required|string|max:1024',
        ]);

        $qrData = $this->clienteQrCodeService->decodificar($validated['qr_code']);
        if (!$qrData) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code invalido ou expirado.',
            ], 400);
        }

        $cliente = User::find($qrData['user_id']);
        if (!$cliente || !$this->isCliente($cliente)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente nao encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'nome' => $cliente->name,
                'email' => $cliente->email,
                'pontos' => $cliente->pontos ?? 0,
                'nivel' => $cliente->nivel ?? 'bronze',
                'qr_version' => $qrData['version'],
                'qr_expira_em' => $qrData['expires_at'],
            ],
        ]);
    }

    /**
     * Endpoint para botao "Adicionar ao Google Wallet".
     */
    public function googleWalletPass(Request $request): JsonResponse
    {
        $user = Auth::user();
        $qrData = $this->clienteQrCodeService->gerar($user);
        $templateUrl = config('services.wallet.google_add_url');
        $addUrl = $this->resolveWalletUrl($templateUrl, $user, $qrData['code']);

        return response()->json([
            'success' => !empty($addUrl),
            'message' => !empty($addUrl)
                ? 'Link Google Wallet gerado com sucesso.'
                : 'Google Wallet nao configurado. Defina GOOGLE_WALLET_ADD_URL no ambiente.',
            'data' => [
                'add_url' => $addUrl,
                'card' => $this->buildWalletCard($user, $qrData),
            ],
        ]);
    }

    /**
     * Endpoint para botao "Adicionar ao Apple Wallet".
     */
    public function appleWalletPass(Request $request): JsonResponse
    {
        $user = Auth::user();
        $qrData = $this->clienteQrCodeService->gerar($user);
        $templateUrl = config('services.wallet.apple_download_url');
        $downloadUrl = $this->resolveWalletUrl($templateUrl, $user, $qrData['code']);

        return response()->json([
            'success' => !empty($downloadUrl),
            'message' => !empty($downloadUrl)
                ? 'Link Apple Wallet gerado com sucesso.'
                : 'Apple Wallet nao configurado. Defina APPLE_WALLET_DOWNLOAD_URL no ambiente.',
            'data' => [
                'download_url' => $downloadUrl,
                'card' => $this->buildWalletCard($user, $qrData),
            ],
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function gerarQRCode(User $user): string
    {
        return $this->clienteQrCodeService->gerar($user)['code'];
    }

    private function calcularNivel(int $pontos): string
    {
        if ($pontos >= 1000) {
            return 'platina';
        }
        if ($pontos >= 500) {
            return 'ouro';
        }
        if ($pontos >= 200) {
            return 'prata';
        }

        return 'bronze';
    }

    private function pontosProximoNivel(int $pontos): array
    {
        $niveis = [
            'bronze' => ['pontos' => 200, 'nome' => 'Prata'],
            'prata' => ['pontos' => 500, 'nome' => 'Ouro'],
            'ouro' => ['pontos' => 1000, 'nome' => 'Platina'],
            'platina' => ['pontos' => null, 'nome' => 'Maximo'],
        ];

        $nivelAtual = $this->calcularNivel($pontos);
        $proximoNivel = $niveis[$nivelAtual];

        if ($proximoNivel['pontos'] === null) {
            return [
                'nivel_atual' => 'platina',
                'proximo_nivel' => 'Platina (Maximo)',
                'pontos_faltando' => 0,
                'porcentagem' => 100,
            ];
        }

        $pontosFaltando = $proximoNivel['pontos'] - $pontos;
        $pontosMinimo = match ($nivelAtual) {
            'bronze' => 0,
            'prata' => 200,
            'ouro' => 500,
            default => 0,
        };

        $porcentagem = (($pontos - $pontosMinimo) / ($proximoNivel['pontos'] - $pontosMinimo)) * 100;

        return [
            'nivel_atual' => $nivelAtual,
            'proximo_nivel' => $proximoNivel['nome'],
            'pontos_faltando' => $pontosFaltando,
            'porcentagem' => round($porcentagem, 2),
        ];
    }

    private function corPorNivel($nivel): string
    {
        return match (strtolower((string) ($nivel ?? ''))) {
            'prata' => '#93A3B8',
            'ouro' => '#D4A017',
            'platina' => '#A8B820',
            default => '#CD7F32', // bronze
        };
    }

    private function normalizePerfil(?string $perfil): ?string
    {
        if (!$perfil) {
            return null;
        }

        $perfil = strtolower(trim($perfil));

        return match (true) {
            in_array($perfil, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true) => 'admin',
            in_array($perfil, ['empresa', 'estabelecimento', 'parceiro', 'lojista'], true) => 'empresa',
            in_array($perfil, ['cliente', 'customer'], true) => 'cliente',
            default => $perfil,
        };
    }

    private function canManagePoints(?User $user): bool
    {
        $perfil = $this->normalizePerfil($user?->perfil);

        return in_array($perfil, ['admin', 'empresa'], true);
    }

    private function isCliente(User $user): bool
    {
        return $this->normalizePerfil($user->perfil) === 'cliente';
    }

    private function resolveWalletUrl(?string $templateUrl, User $user, string $qrCode): ?string
    {
        if (!$templateUrl) {
            return null;
        }

        $resolved = str_replace('{user_id}', (string) $user->id, $templateUrl);
        $resolved = str_replace('{email}', rawurlencode((string) $user->email), $resolved);
        $resolved = str_replace('{qr_code}', rawurlencode($qrCode), $resolved);

        return $resolved;
    }

    private function buildWalletCard(User $user, array $qrData): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'pontos' => (int) ($user->pontos ?? 0),
            'nivel' => $user->nivel ?? 'bronze',
            'qr_code' => $qrData['code'],
            'qr_expira_em' => $qrData['expires_at']->toIso8601String(),
        ];
    }
}
