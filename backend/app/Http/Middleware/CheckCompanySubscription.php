<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CheckCompanySubscription - Bloqueia acesso de empresas inadimplentes.
 * 
 * Aplique em rotas que empresas usam para operar o sistema.
 */
class CheckCompanySubscription
{
    public function __construct(
        private readonly BillingService $billingService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Só valida para usuários de empresa
        if (!$user || !in_array($user->perfil, ['empresa', 'loja', 'estabelecimento'])) {
            return $next($request);
        }

        // Obtém empresa_id
        $companyId = $user->empresa_id ?? $user->id;

        // Verifica se pode operar
        $check = $this->billingService->canOperate($companyId);

        // Se bloqueado, retorna erro
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'error' => 'subscription_blocked',
                'requires_payment' => true,
            ], 403);
        }

        // Se em atraso mas ainda permitido, adiciona header de aviso
        if ($check['reason']) {
            $response = $next($request);
            $response->headers->set('X-Subscription-Warning', $check['reason']);
            return $response;
        }

        return $next($request);
    }
}
