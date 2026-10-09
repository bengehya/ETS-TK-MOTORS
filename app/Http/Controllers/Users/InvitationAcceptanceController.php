<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AcceptInvitationRequest;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function create(): Response
    {
        return Inertia::render('Auth/AcceptInvitation', [
            'codeTtlDays' => InvitationService::CODE_TTL_DAYS,
        ]);
    }

    public function store(AcceptInvitationRequest $request): RedirectResponse
    {
        $user = $this->invitations->accept(
            $request->string('code')->toString(),
            $request->string('password')->toString(),
        );

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.last_activity_at', now()->getTimestamp());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Votre compte est activé.');
    }
}
