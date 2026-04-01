<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
    private function normalizePerfil(?string $perfil): ?string
    {
        if (!$perfil) return null;
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

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  O perfil necessário para acessar a rota
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Verificar se usuário está autenticado
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autenticado'
            ], 401);
        }

        $user = Auth::user();

        // Verificar se o usuário tem o perfil necessário
        $userPerfil = $this->normalizePerfil((string) ($user->perfil ?? ''));
        $requiredPerfil = $this->normalizePerfil($role);

        if ($userPerfil !== $requiredPerfil) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Perfil insuficiente.'
            ], 403);
        }

        return $next($request);
    }
}
