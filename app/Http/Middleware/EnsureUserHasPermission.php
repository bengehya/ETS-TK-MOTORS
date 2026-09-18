<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $required = Permission::from($permission);

        abort_unless(
            $user !== null && $user->hasPermission($required),
            403,
            'Action non autorisée.',
        );

        return $next($request);
    }
}
