<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ControlAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //dd('Middleware exécuté');
        if (!auth('admin')->check()) {
            return response()->json([
                'status' => 'Accès Interdite',
                'message' => 'Vous devez être authentifié pour accéder à cette ressource.'
            ], 401); // ⬅ Ajoute explicitement le statut HTTP 401 ici
        }
        return $next($request);
    }
}
