<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $suspended = User::query()
            ->whereKey(Auth::id())
            ->whereNotNull('suspended_at')
            ->exists();

        if (! $suspended) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($this->wantsJsonFailure($request)) {
            return response()->json([
                'message' => 'Ce compte est suspendu.',
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with('error', 'Ce compte est suspendu.');
    }

    private function wantsJsonFailure(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->expectsJson() || $request->is('api/*');
    }
}
