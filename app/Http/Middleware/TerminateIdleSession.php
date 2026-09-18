<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TerminateIdleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeoutSeconds = ((int) config('session.lifetime')) * 60;
        $lastActivity = $request->session()->get('auth.last_activity_at');

        if (Auth::check() && is_numeric($lastActivity)) {
            $idleFor = now()->getTimestamp() - (int) $lastActivity;

            if ($idleFor > $timeoutSeconds) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($this->wantsJsonFailure($request)) {
                    return response()->json([
                        'message' => 'Session expirée pour inactivité.',
                    ], 401);
                }

                return redirect()->route('login');
            }
        }

        if (Auth::check()) {
            $request->session()->put('auth.last_activity_at', now()->getTimestamp());
        }

        return $next($request);
    }

    private function wantsJsonFailure(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->expectsJson() || $request->is('api/*');
    }
}
