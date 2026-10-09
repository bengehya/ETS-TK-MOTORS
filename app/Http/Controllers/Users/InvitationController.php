<?php

namespace App\Http\Controllers\Users;

use App\Enums\Civility;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreInvitationRequest;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Support\UserPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission(Permission::ManageEmployees), 403);

        $invitations = Invitation::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with('inviter')
            ->latest('id')
            ->get()
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'name' => $invitation->displayName(),
                'email' => $invitation->email,
                'civility_label' => $invitation->civility->label(),
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'status' => $invitation->status(),
                'status_label' => $invitation->statusLabel(),
                'created_at' => $invitation->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'expires_at' => $invitation->expires_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'can_revoke' => $invitation->isAcceptable(),
                'inviter' => UserPresenter::identity($invitation->inviter),
            ])
            ->all();

        return Inertia::render('Users/Index', [
            'invitations' => $invitations,
            'activationCode' => $request->session()->get('invitation_code'),
            'activationCodeTtlDays' => InvitationService::CODE_TTL_DAYS,
            'emailDeliveryAvailable' => false,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission(Permission::ManageEmployees), 403);

        $roles = [
            ['value' => Role::Employe->value, 'label' => Role::Employe->label()],
        ];

        if ($request->user()->hasPermission(Permission::InviteBoss)) {
            $roles[] = ['value' => Role::BossSecondaire->value, 'label' => Role::BossSecondaire->label()];
        }

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'civilities' => collect(Civility::cases())->map(fn (Civility $civility) => [
                'value' => $civility->value,
                'label' => $civility->label(),
            ])->values(),
        ]);
    }

    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $created = $this->invitations->create($request->user(), $request->validated());

        return redirect()
            ->route('users.invitations.index')
            ->with('status', 'Invitation créée. Transmettez ce code à 5 chiffres. Il ne sera plus affiché.')
            ->with('invitation_code', $created['code']);
    }

    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->invitations->revoke($request->user(), $invitation);

        return back()->with('status', 'Invitation révoquée.');
    }
}
