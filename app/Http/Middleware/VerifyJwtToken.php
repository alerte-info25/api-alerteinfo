<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupère le token depuis l'en-tête Authorization
        $token = $request->bearerToken();

        

        //$payload = JWTAuth::decode(JWTAuth::setToken($token)->getToken());
        
        if ($token != 'null') {
            
            try {
                // Vérifie et valide le token
                $user = JWTAuth::parseToken()->authenticate();

                // Ajoute l'utilisateur à la requête pour une utilisation ultérieure
                $request->attributes->add(['user' => $user]);

                return $next($request);
            } catch (TokenExpiredException $e) {
                return response()->json(['error' => 'Token expiré'], 401);
            } catch (TokenInvalidException $e) {
                return response()->json(['error' => 'Token invalide'], 401);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Erreur de token'], 401);
            }
        }

        
        // Si aucun token n'est présent, autorise la requête à continuer
        return $next($request);
    }
}


