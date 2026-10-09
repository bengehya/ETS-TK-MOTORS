<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $required = Permission::from($permission);

        if ($user !== null && $user->hasPermission($required)) {
            return $next($request);
        }

        app(AuditLogger::class)->safeRecord(
            $user,
            'permission.denied',
            null,
            null,
            [
                'permission' => $required->value,
                'method' => $request->method(),
                'path' => '/'.$request->path(),
            ],
            'Action non autorisée.',
            'denied',
        );

        abort(403, 'Action non autorisée.');
    }
}
