<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tighten\Ziggy\BladeRouteGenerator;

class RefreshZiggyRoutes
{
    /**
     * Chaque requête HTTP reçoit les routes avec l'hôte courant.
     * Sans cela, un processus qui sert plusieurs requêtes ne republie
     * plus l'URL après la première page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        BladeRouteGenerator::$generated = false;

        return $next($request);
    }
}
