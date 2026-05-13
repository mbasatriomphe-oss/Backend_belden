<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier si l'utilisateur est admin
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les administrateurs peuvent effectuer cette action.'
            ], 403);
        }

        return $next($request);
    }
}