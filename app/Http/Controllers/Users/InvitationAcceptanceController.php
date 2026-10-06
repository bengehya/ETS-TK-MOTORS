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

    public function create(string $token): Response
    {
        $invitation = $this->invitations->findByToken($token);

        if ($invitation === null || ! $invitation->isAcceptable()) {
            return Inertia::render('Auth/AcceptInvitation', [
                'token' => $token,
                'invitation' => null,
                'invalid' => true,
            ]);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'invalid' => false,
            'invitation' => [
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'name' => $invitation->displayName(),
                'email' => $invitation->email,
                'civility_label' => $invitation->civility->label(),
                'role_label' => $invitation->role->label(),
            ],
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $user = $this->invitations->accept($token, $request->string('password')->toString());

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.last_activity_at', now()->getTimestamp());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Votre compte est activé.');
    }
}
