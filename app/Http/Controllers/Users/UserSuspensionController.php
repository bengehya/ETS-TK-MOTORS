<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserSuspensionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class UserSuspensionController extends Controller
{
    public function __construct(private UserSuspensionService $suspensions) {}

    public function store(Request $request, User $member): RedirectResponse
    {
        $this->change($request, $member, suspend: true);

        return back()->with('status', 'Compte suspendu.');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $this->change($request, $member, suspend: false);

        return back()->with('status', 'Compte réactivé.');
    }

    private function change(Request $request, User $member, bool $suspend): void
    {
        try {
            if ($suspend) {
                $this->suspensions->suspend($request->user(), $member);
            } else {
                $this->suspensions->reactivate($request->user(), $member);
            }
        } catch (AuthorizationException) {
            abort(403, 'Action non autorisée.');
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'member' => $exception->getMessage(),
            ]);
        }
    }
}
