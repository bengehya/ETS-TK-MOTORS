<?php

namespace App\Http\Middleware;

use App\Services\BootstrapRegistrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBootstrapRegistrationIsOpen
{
    public function __construct(private BootstrapRegistrationService $registration)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->registration->isOpen()) {
            return $next($request);
        }

        abort_if($request->isMethod('POST') || $request->expectsJson(), 403, 'L’inscription publique est fermée.');

        return redirect()
            ->route('login')
            ->with('status', 'L’inscription publique est fermée. Un accès doit être accordé par un patron.');
    }
}
