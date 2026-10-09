<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlignSessionCookieSecurity
{
    /**
     * Le cookie de session suit le schéma réel de la requête lorsque
     * SESSION_SECURE_COOKIE n'est pas fixé : HTTP en localhost, HTTPS derrière
     * le proxy. Une valeur explicite true ou false est respectée.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('session.secure');
        $followRequest = $configured === null || $configured === '';

        if ($followRequest) {
            config(['session.secure' => $request->isSecure()]);
        }

        try {
            return $next($request);
        } finally {
            if ($followRequest) {
                config(['session.secure' => $configured]);
            }
        }
    }
}
