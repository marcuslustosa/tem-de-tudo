<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
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
        if ($user->perfil !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Perfil insuficiente.'
            ], 403);
        }

        return $next($request);
    }
}
