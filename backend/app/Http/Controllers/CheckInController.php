<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Empresa;
use App\Models\QRCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class CheckInController extends Controller
{
    /**
     * Fazer check-in via QR Code
     */
    public function fazerCheckIn(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'valor_compra' => 'nullable|numeric|min:0'
        ]);

        $user = $request->user();
        $qr_code_data = $request->qr_code;
        $valor_compra = $request->valor_compra * 100 ?? 0; // converter para centavos

        try {
            // Decodificar QR Code
            $qr_info = $this->decodificarQRCode($qr_code_data);
            
            if (!$qr_info) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code inválido ou expirado'
                ], 400);
            }

            $empresa = Empresa::find($qr_info['empresa_id']);
            
            if (!$empresa || !$empresa->ativo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa não encontrada ou inativa'
                ], 404);
            }

            // Verificar se já fez check-in hoje nesta empresa
            $hoje = now()->format('Y-m-d');
            $checkin_existente = CheckIn::where('user_id', $user->id)
                                       ->where('empresa_id', $empresa->id)
                                       ->whereDate('created_at', $hoje)
                                       ->first();

            if ($checkin_existente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já fez check-in nesta empresa hoje!',
                    'checkin_existente' => $checkin_existente
                ], 400);
            }

            // Calcular pontos
            $pontos_calculados = $this->calcularPontos($user, $empresa, $valor_compra);

            // Criar check-in
            $checkin = CheckIn::create([
                'user_id' => $user->id,
                'empresa_id' => $empresa->id,
                'pontos_ganhos' => $pontos_calculados['total'],
                'pontos_base' => $pontos_calculados['base'],
                'multiplicador' => $pontos_calculados['multiplicador'],
                'valor_compra' => $valor_compra,
                'detalhes_calculo' => $pontos_calculados,
                'qr_code_id' => $qr_info['qr_code_id'] ?? null,
                'ip_origem' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Atualizar pontos do usuário
            $user->pontos += $pontos_calculados['total'];
            $user->save();

            // Registrar no histórico de pontos
            $user->pontos_historico()->create([
                'pontos' => $pontos_calculados['total'],
                'tipo' => 'checkin',
                'descricao' => "Check-in em {$empresa->nome}",
                'empresa_id' => $empresa->id,
                'data_expiracao' => now()->addYear()
            ]);

            // Processar para sistema VIP e badges
            $badges_novos = $user->processarCheckin($checkin);

            // Verificar se é aniversário (bônus especial)
            $bonus_aniversario = 0;
            if ($user->ehAniversarioHoje()) {
                $bonus_aniversario = 100;
                $user->pontos += $bonus_aniversario;
                $user->save();

                $user->pontos_historico()->create([
                    'pontos' => $bonus_aniversario,
                    'tipo' => 'bonus_aniversario',
                    'descricao' => 'Bônus de aniversário! 🎉',
                    'empresa_id' => $empresa->id,
                    'data_expiracao' => now()->addYear()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado com sucesso!',
                'checkin' => [
                    'id' => $checkin->id,
                    'pontos_ganhos' => $checkin->pontos_ganhos,
                    'empresa' => $empresa->only(['id', 'nome', 'logo']),
                    'bonus_aniversario' => $bonus_aniversario,
                    'badges_novos' => $badges_novos,
                    'nivel_atual' => $user->calcularNivel(),
                    'total_pontos' => $user->pontos
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gerar QR Code para empresa
     */
    public function gerarQRCode(Request $request)
    {
        $user = $request->user();
        
        if ($user->perfil !== 'empresa' || !$user->empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        $empresa = $user->empresa;

        // Criar ou atualizar QR Code da empresa
        $qrCode = QRCode::updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'codigo' => $this->gerarCodigoUnico(),
                'ativo' => true,
                'expiracao' => now()->addDays(30) // QR Code válido por 30 dias
            ]
        );

        // Dados do QR Code
        $qr_data = [
            'type' => 'checkin',
            'empresa_id' => $empresa->id,
            'qr_code_id' => $qrCode->id,
            'codigo' => $qrCode->codigo,
            'timestamp' => time()
        ];

        $qr_string = base64_encode(json_encode($qr_data));

        // Gerar imagem do QR Code
        $qr_image = QrCodeGenerator::format('png')
                                   ->size(300)
                                   ->margin(1)
                                   ->generate($qr_string);

        // Salvar imagem
        $filename = "qrcodes/empresa_{$empresa->id}.png";
        Storage::disk('public')->put($filename, $qr_image);

        $qrCode->update(['imagem_path' => $filename]);

        return response()->json([
            'success' => true,
            'qr_code' => [
                'id' => $qrCode->id,
                'codigo' => $qrCode->codigo,
                'string' => $qr_string,
                'imagem_url' => Storage::url($filename),
                'expiracao' => $qrCode->expiracao,
                'empresa' => $empresa->only(['id', 'nome'])
            ]
        ]);
    }

    /**
     * Histórico de check-ins do usuário
     */
    public function meuHistorico(Request $request)
    {
        $user = $request->user();
        
        $checkins = $user->checkIns()
                         ->with(['empresa:id,nome,logo'])
                         ->orderBy('created_at', 'desc')
                         ->paginate(20);

        return response()->json([
            'success' => true,
            'checkins' => $checkins->items(),
            'estatisticas' => [
                'total_checkins' => $user->checkIns()->count(),
                'empresas_visitadas' => $user->checkIns()->distinct('empresa_id')->count(),
                'pontos_checkins' => $user->checkIns()->sum('pontos_ganhos'),
                'dias_consecutivos' => $user->dias_consecutivos
            ],
            'pagination' => [
                'current_page' => $checkins->currentPage(),
                'total_pages' => $checkins->lastPage(),
                'total' => $checkins->total()
            ]
        ]);
    }

    /**
     * Check-ins de uma empresa (para proprietário)
     */
    public function checkinsEmpresa(Request $request)
    {
        $user = $request->user();
        
        if ($user->perfil !== 'empresa' || !$user->empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        $empresa = $user->empresa;
        
        $checkins = $empresa->checkins()
                           ->with(['user:id,name,email'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(50);

        // Estatísticas
        $hoje = now()->startOfDay();
        $este_mes = now()->startOfMonth();
        
        $stats = [
            'hoje' => $empresa->checkins()->whereDate('created_at', $hoje)->count(),
            'este_mes' => $empresa->checkins()->where('created_at', '>=', $este_mes)->count(),
            'total' => $empresa->checkins()->count(),
            'pontos_distribuidos_mes' => $empresa->checkins()->where('created_at', '>=', $este_mes)->sum('pontos_ganhos'),
            'clientes_unicos_mes' => $empresa->checkins()->where('created_at', '>=', $este_mes)->distinct('user_id')->count()
        ];

        return response()->json([
            'success' => true,
            'checkins' => $checkins->items(),
            'estatisticas' => $stats,
            'pagination' => [
                'current_page' => $checkins->currentPage(),
                'total_pages' => $checkins->lastPage(),
                'total' => $checkins->total()
            ]
        ]);
    }

    /**
     * Decodificar QR Code
     */
    private function decodificarQRCode($qr_string)
    {
        try {
            $decoded = json_decode(base64_decode($qr_string), true);
            
            if (!$decoded || !isset($decoded['type']) || $decoded['type'] !== 'checkin') {
                return null;
            }

            // Verificar se QR Code existe e está ativo
            if (isset($decoded['qr_code_id'])) {
                $qrCode = QRCode::find($decoded['qr_code_id']);
                
                if (!$qrCode || !$qrCode->ativo || $qrCode->expiracao < now()) {
                    return null;
                }
            }

            return $decoded;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calcular pontos do check-in
     */
    private function calcularPontos(User $user, Empresa $empresa, $valor_compra = 0)
    {
        // Pontos base: 10 pontos por check-in + 1 ponto por real gasto
        $pontos_base = 10;
        
        if ($valor_compra > 0) {
            $pontos_base += intval($valor_compra / 100); // 1 ponto por real
        }

        // Multiplicador do nível VIP
        $nivel_info = $user->calcularNivel();
        $multiplicador = $nivel_info['multiplicador'];

        // Bônus por dias consecutivos
        $bonus_consecutivo = 0;
        if ($user->dias_consecutivos >= 7) {
            $bonus_consecutivo = 20; // 20 pontos extras por semana consecutiva
        } elseif ($user->dias_consecutivos >= 3) {
            $bonus_consecutivo = 10; // 10 pontos extras por 3+ dias
        }

        // Cálculo final
        $pontos_com_multiplicador = intval($pontos_base * $multiplicador);
        $pontos_total = $pontos_com_multiplicador + $bonus_consecutivo;

        return [
            'base' => $pontos_base,
            'multiplicador' => $multiplicador,
            'bonus_consecutivo' => $bonus_consecutivo,
            'total' => $pontos_total,
            'detalhes' => [
                'pontos_checkin' => 10,
                'pontos_compra' => $valor_compra > 0 ? intval($valor_compra / 100) : 0,
                'nivel' => $nivel_info['nome'],
                'dias_consecutivos' => $user->dias_consecutivos
            ]
        ];
    }

    /**
     * Gerar código único para QR Code
     */
    private function gerarCodigoUnico()
    {
        do {
            $codigo = 'QR' . strtoupper(uniqid()) . rand(1000, 9999);
        } while (QRCode::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Validar QR Code (para teste)
     */
    public function validarQRCode(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        $qr_info = $this->decodificarQRCode($request->qr_code);
        
        if (!$qr_info) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code inválido ou expirado'
            ]);
        }

        $empresa = Empresa::find($qr_info['empresa_id']);
        
        return response()->json([
            'success' => true,
            'message' => 'QR Code válido',
            'empresa' => $empresa->only(['id', 'nome', 'logo', 'endereco'])
        ]);
    }
}