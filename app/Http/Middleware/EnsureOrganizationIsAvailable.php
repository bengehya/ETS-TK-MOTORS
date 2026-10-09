<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationIsAvailable
{
    public function __construct(private MaintenanceService $maintenance) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isBoss()) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        if (! $this->maintenance->isEnabled((int) $user->organization_id)) {
            return $next($request);
        }

        if ($this->wantsJsonFailure($request)) {
            return response()->json([
                'message' => 'L’application est en maintenance.',
            ], 503);
        }

        return Inertia::render('Maintenance/Blocked', [
            'name' => config('tkmotors.name'),
            'company' => config('tkmotors.company'),
            'slogan' => config('tkmotors.slogan'),
        ])->toResponse($request);
    }

    private function wantsJsonFailure(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->expectsJson() || $request->is('api/*');
    }
}
