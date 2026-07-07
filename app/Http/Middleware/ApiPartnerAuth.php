<?php

namespace App\Http\Middleware;

use App\Models\PartnersNews\ApiPartnerNewsModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPartnerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Token manquant.'], 401);
        }

        $partner = ApiPartnerNewsModel::where('api_token', $token)->active()->first();

        if (! $partner) {
            return response()->json(['message' => 'Accès non autorisé.'], 401);
        }

        // Optionnel : mettre à jour last_used_at
        $partner->update(['last_used_at' => now()]);

        // Ajouter le partenaire au request pour traçabilité
        $request->attributes->set('api_partner', $partner);

        return $next($request);
    }
}
