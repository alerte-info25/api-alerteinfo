<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CustomLogServices\CustomLogService;

class RequestLoggerMiddleware
{
    protected $customLogService;

    public function __construct(CustomLogService $customLogService)
    {
        $this->customLogService = $customLogService;
    }




    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log d'information pour la requête
        $this->customLogService->info('Requête entrante', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
        ]);

        $response = $next($request);



        // Log d'information pour la réponse
        if ($response->getStatusCode() >= 400) {
            $this->customLogService->warning("Réponse avec état d'erreur", [
                'status_code' => $response->getStatusCode(),
            ]);
        } else {
            $this->customLogService->info('Réponse envoyée', [
                'status_code' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
