<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(
            static fn (string $role): Role => Role::from($role),
            $roles,
        );

        abort_unless(
            $user !== null && in_array($user->role, $allowed, true),
            403,
            'Rôle insuffisant.',
        );

        return $next($request);
    }
}
