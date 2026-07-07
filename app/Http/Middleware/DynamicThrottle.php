<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = $request->attributes->get('api_partner');

        if ($partner) {
            $rateLimit = $partner->rate_limit;
            $key = 'api_partner_' . $partner->id;
            $window = 60; // 60 secondes

            $throttle = app(\Illuminate\Routing\Middleware\ThrottleRequests::class);
            return $throttle->handle($request, $next, $rateLimit, $window, $key);
        }

        return $next($request);
    }
}
