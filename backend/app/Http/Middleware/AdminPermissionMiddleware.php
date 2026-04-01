<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    private function isAdminPerfil($perfil): bool
    {
        $value = strtolower(trim((string) $perfil));
        return in_array($value, ['admin', 'administrador', 'master', 'admin_master', 'administrador_master'], true);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->get('authenticated_user') ?? $request->user();

        if (!$user) {
            AuditLog::logEvent('permission_denied', null, $request, 'Acesso negado - usuario nao autenticado');
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        // Super admin e perfis administrativos podem acessar sem bloqueio adicional.
        if (($user->role ?? null) === 'super_admin' || $this->isAdminPerfil($user->perfil ?? null)) {
            return $next($request);
        }

        if (!method_exists($user, 'hasPermission')) {
            AuditLog::logEvent('permission_denied', $user->id ?? null, $request, 'Acesso negado - metodo hasPermission indisponivel');
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                AuditLog::logEvent('permission_denied', $user->id, $request, "Permissao negada: {$permission}");
                return response()->json(['success' => false, 'message' => "Permissao negada para: {$permission}"], 403);
            }
        }

        return $next($request);
    }
}
