<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Services\BootstrapRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class RegisteredUserController extends Controller
{
    public function __construct(private BootstrapRegistrationService $registration)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        try {
            $user = $this->registration->registerPrincipal($request->validated());
        } catch (RuntimeException) {
            abort(403, 'L’inscription publique est fermée.');
        }

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put('auth.last_activity_at', now()->getTimestamp());

        return redirect(route('dashboard', absolute: false));
    }
}
