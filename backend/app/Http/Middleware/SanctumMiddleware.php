<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class SanctumMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('sanctum')->check()) {
            AuditLog::logEvent('unauthorized_access', null, $request, 'Token não fornecido ou inválido');
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido ou inválido'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            AuditLog::logEvent('unauthorized_access', null, $request, 'Usuário não encontrado');
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado'
            ], 404);
        }

        // Verificar se o usuário está ativo
        if (isset($user->status) && $user->status !== 'ativo') {
            AuditLog::logEvent('unauthorized_access', $user->id, $request, 'Usuário inativo');
            return response()->json([
                'success' => false,
                'message' => 'Usuário inativo'
            ], 403);
        }

        // Verificar se a conta está bloqueada
        if (isset($user->locked_until) && $user->locked_until && now()->lt($user->locked_until)) {
            AuditLog::logEvent('unauthorized_access', $user->id, $request, 'Conta temporariamente bloqueada');
            return response()->json([
                'success' => false,
                'message' => 'Conta temporariamente bloqueada'
            ], 423);
        }

        // Adicionar usuário ao request
        $request->merge(['authenticated_user' => $user]);

        return $next($request);
    }
}
