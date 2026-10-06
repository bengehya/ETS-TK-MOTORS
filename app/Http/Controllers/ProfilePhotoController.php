<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Models\User;
use App\Services\ProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function __construct(private ProfilePhotoService $photos) {}

    public function show(Request $request, User $member): StreamedResponse
    {
        abort_unless($request->user()?->organization_id === $member->organization_id, 404);

        return $this->photos->response($member);
    }

    public function store(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        $this->photos->store($request->user(), $request->file('photo'));

        return back()->with('status', 'Photo de profil enregistrée.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->photos->clear($request->user());

        return back()->with('status', 'Photo de profil supprimée.');
    }
}
