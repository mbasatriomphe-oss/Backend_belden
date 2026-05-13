<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendeurMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié avec le guard vendeur
        if (!Auth::guard('vendeur')->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accès non autorisé. Authentification vendeur requise.'
            ], 401);
        }

        return $next($request);
    }
}
