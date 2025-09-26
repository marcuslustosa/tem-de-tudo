<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;

class AdminPermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->get('authenticated_user');

        // Verificar se é um admin
        if (!$user || !method_exists($user, 'hasPermission')) {
            AuditLog::logEvent('permission_denied', $user->id ?? null, $request, 
                'Acesso negado - usuário não é admin');
            
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado'
            ], 403);
        }

        // Super admin tem todas as permissões
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Verificar permissões específicas
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!$user->hasPermission($permission)) {
                    AuditLog::logEvent('permission_denied', $user->id, $request, 
                        "Permissão negada: {$permission}");
                    
                    return response()->json([
                        'success' => false,
                        'message' => "Permissão negada para: {$permission}"
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}