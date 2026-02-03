<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->ativo) {
            return response()->json([
                'success' => false,
                'message' => 'Sua conta está inativa. Entre em contato com o suporte.'
            ], 403);
        }

        return $next($request);
    }
}
