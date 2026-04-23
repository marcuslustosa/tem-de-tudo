<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Models\User;
use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;

/**
 * CheckCompanySubscription - Bloqueia acesso de empresas inadimplentes.
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
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $perfil = $this->normalizePerfil($user->perfil ?? null);
        if ($perfil !== 'empresa') {
            return $next($request);
        }

        $companyId = $this->resolveCompanyId($user);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario de empresa sem estabelecimento vinculado.',
                'error' => 'company_not_linked',
            ], 403);
        }

        $check = $this->billingService->canOperate($companyId);

        // Modo de compatibilidade: ainda permite operar sem assinatura vinculada.
        $allowNoSubscription = (bool) config('billing.allow_without_subscription', true);
        if (
            $allowNoSubscription
            && !$check['allowed']
            && ($check['reason'] ?? '') === 'Nenhuma assinatura encontrada'
        ) {
            return $next($request);
        }

        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'error' => 'subscription_blocked',
                'requires_payment' => true,
            ], 403);
        }

        $response = $next($request);
        if (!empty($check['reason'])) {
            $response->headers->set('X-Subscription-Warning', $check['reason']);
        }

        return $response;
    }

    private function resolveCompanyId(User $user): ?int
    {
        if (isset($user->empresa_id) && is_numeric($user->empresa_id) && (int) $user->empresa_id > 0) {
            return (int) $user->empresa_id;
        }

        if (method_exists($user, 'empresa')) {
            $empresa = $user->empresa()->first();
            if ($empresa?->id) {
                return (int) $empresa->id;
            }
        }

        // Compatibilidade com modelos antigos onde user_id de empresa = empresa.id
        if (Empresa::query()->whereKey($user->id)->exists()) {
            return (int) $user->id;
        }

        return null;
    }

    private function normalizePerfil(?string $perfil): ?string
    {
        if (!$perfil) {
            return null;
        }

        $value = strtolower(trim($perfil));
        if (in_array($value, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true)) {
            return 'admin';
        }

        if (in_array($value, ['empresa', 'estabelecimento', 'parceiro', 'lojista'], true)) {
            return 'empresa';
        }

        if (in_array($value, ['cliente', 'customer'], true)) {
            return 'cliente';
        }

        return $value;
    }
}

