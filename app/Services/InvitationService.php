<?php

namespace App\Services;

use App\Enums\Civility;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * Crée une invitation réelle, sans envoi d'e-mail.
     * Le lien d'activation n'est retourné qu'une fois : il n'est pas stocké en clair.
     *
     * @param  array{first_name: string, last_name: string, email: string, civility: string, role: string}  $attributes
     * @return array{invitation: Invitation, url: string}
     */
    public function create(User $inviter, array $attributes): array
    {
        $role = Role::from($attributes['role']);
        $this->assertCanAssign($inviter, $role);

        $email = strtolower(trim($attributes['email']));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse e-mail est déjà utilisée.',
            ]);
        }

        $pending = Invitation::query()
            ->where('organization_id', $inviter->organization_id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'email' => 'Une invitation est déjà en attente pour cette adresse.',
            ]);
        }

        $plainToken = Str::random(64);

        $invitation = Invitation::query()->create([
            'organization_id' => $inviter->organization_id,
            'invited_by' => $inviter->id,
            'first_name' => trim($attributes['first_name']),
            'last_name' => trim($attributes['last_name']),
            'email' => $email,
            'civility' => Civility::from($attributes['civility']),
            'role' => $role,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
        ]);

        return [
            'invitation' => $invitation,
            'url' => route('invitations.accept', ['token' => $plainToken]),
        ];
    }

    public function revoke(User $actor, Invitation $invitation): void
    {
        if ($actor->organization_id !== $invitation->organization_id || ! $actor->hasPermission(Permission::ManageEmployees)) {
            abort(403);
        }

        if (! $invitation->isAcceptable()) {
            throw ValidationException::withMessages([
                'invitation' => 'Cette invitation ne peut plus être révoquée.',
            ]);
        }

        $invitation->forceFill(['revoked_at' => now()])->save();
    }

    public function accept(string $token, string $password): User
    {
        $invitation = $this->findByToken($token);

        if ($invitation === null || ! $invitation->isAcceptable()) {
            throw ValidationException::withMessages([
                'token' => 'Cette invitation n’est plus valable.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $password): User {
            /** @var Invitation $locked */
            $locked = Invitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isAcceptable()) {
                throw ValidationException::withMessages([
                    'token' => 'Cette invitation n’est plus valable.',
                ]);
            }

            if ($locked->role === Role::BossPrincipal) {
                throw ValidationException::withMessages([
                    'token' => 'Cette invitation n’est plus valable.',
                ]);
            }

            if (User::query()->where('email', $locked->email)->exists()) {
                throw ValidationException::withMessages([
                    'token' => 'Un compte existe déjà pour cette adresse e-mail.',
                ]);
            }

            $user = User::query()->create([
                'organization_id' => $locked->organization_id,
                'name' => $locked->displayName(),
                'first_name' => $locked->first_name,
                'last_name' => $locked->last_name,
                'civility' => $locked->civility,
                'email' => $locked->email,
                'password' => $password,
                'role' => $locked->role,
            ]);

            $locked->forceFill(['accepted_at' => now()])->save();

            return $user;
        });
    }

    public function findByToken(string $token): ?Invitation
    {
        if (! preg_match('/\A[A-Za-z0-9]{64}\z/', $token)) {
            return null;
        }

        return Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    private function assertCanAssign(User $inviter, Role $role): void
    {
        if (! $inviter->hasPermission(Permission::ManageEmployees)) {
            abort(403);
        }

        if ($role === Role::BossPrincipal) {
            throw ValidationException::withMessages([
                'role' => 'Le patron principal ne peut pas être invité.',
            ]);
        }

        if ($role === Role::BossSecondaire && ! $inviter->hasPermission(Permission::InviteBoss)) {
            throw ValidationException::withMessages([
                'role' => 'Vous n’êtes pas autorisé à inviter un patron.',
            ]);
        }

        if (! in_array($role, [Role::Employe, Role::BossSecondaire], true)) {
            throw ValidationException::withMessages([
                'role' => 'Ce rôle ne peut pas être attribué par invitation.',
            ]);
        }
    }
}
