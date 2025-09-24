<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\AuditLog;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verificar se o token existe
            if (!$token = JWTAuth::getToken()) {
                AuditLog::logEvent('unauthorized_access', null, $request, 'Token não fornecido');
                return response()->json([
                    'success' => false,
                    'message' => 'Token não fornecido'
                ], 401);
            }

            // Verificar e autenticar o token
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                AuditLog::logEvent('unauthorized_access', null, $request, 'Token inválido - usuário não encontrado');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }

            // Verificar se o usuário está ativo
            if (isset($user->is_active) && !$user->is_active) {
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

        } catch (TokenExpiredException $e) {
            AuditLog::logEvent('token_expired', null, $request, 'Token expirado');
            return response()->json([
                'success' => false,
                'message' => 'Token expirado'
            ], 401);

        } catch (TokenInvalidException $e) {
            AuditLog::logEvent('unauthorized_access', null, $request, 'Token inválido');
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);

        } catch (JWTException $e) {
            AuditLog::logEvent('jwt_error', null, $request, 'Erro JWT: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro de autenticação'
            ], 401);
        }

        return $next($request);
    }
}