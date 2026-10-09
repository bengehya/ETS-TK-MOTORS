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
     * Durée de validité d'un code d'invitation. Documentée pour la V1.
     */
    public const CODE_TTL_DAYS = 7;

    /**
     * Crée une invitation réelle, sans envoi d'e-mail.
     *
     * Le code à 5 chiffres n'est retourné qu'une fois. Seul son empreinte
     * SHA-256 est stockée. Un envoi par e-mail pourra transmettre ce même
     * code plus tard, sans second système d'invitation et sans le journaliser.
     *
     * @param  array{first_name: string, last_name: string, email: string, civility: string, role: string}  $attributes
     * @return array{invitation: Invitation, code: string}
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

        $plainCode = $this->issueUniqueCode();

        $invitation = Invitation::query()->create([
            'organization_id' => $inviter->organization_id,
            'invited_by' => $inviter->id,
            'first_name' => trim($attributes['first_name']),
            'last_name' => trim($attributes['last_name']),
            'email' => $email,
            'civility' => Civility::from($attributes['civility']),
            'role' => $role,
            'token_hash' => hash('sha256', $plainCode),
            'expires_at' => now()->addDays(self::CODE_TTL_DAYS),
        ]);

        app(AuditLogger::class)->record($inviter, 'invitation.created', $invitation, null, [
            'email' => $email,
            'role' => $role->value,
            'civility' => $invitation->civility->value,
        ]);

        return [
            'invitation' => $invitation,
            'code' => $plainCode,
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

        app(AuditLogger::class)->record($actor, 'invitation.revoked', $invitation, null, [
            'email' => $invitation->email,
            'role' => $invitation->role->value,
        ]);
    }

    public function accept(string $code, string $password): User
    {
        $invitation = $this->findAcceptableByCode($code);

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'code' => 'Ce code d’invitation n’est pas valable.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $password): User {
            /** @var Invitation $locked */
            $locked = Invitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isAcceptable()) {
                throw ValidationException::withMessages([
                    'code' => 'Ce code d’invitation n’est pas valable.',
                ]);
            }

            if ($locked->role === Role::BossPrincipal) {
                throw ValidationException::withMessages([
                    'code' => 'Ce code d’invitation n’est pas valable.',
                ]);
            }

            if (User::query()->where('email', $locked->email)->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Ce code d’invitation n’est pas valable.',
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

            app(AuditLogger::class)->record($user, 'invitation.accepted', $locked, null, [
                'email' => $user->email,
                'role' => $user->role->value,
                'user_id' => $user->id,
            ]);

            return $user;
        });
    }

    public function findAcceptableByCode(string $code): ?Invitation
    {
        if (! preg_match('/\A\d{5}\z/', $code)) {
            return null;
        }

        return Invitation::query()
            ->where('token_hash', hash('sha256', $code))
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    private function issueUniqueCode(): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->deriveNumericCode();

            $taken = Invitation::query()
                ->where('token_hash', hash('sha256', $code))
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists();

            if (! $taken) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'email' => 'Impossible de créer l’invitation pour le moment. Réessayez.',
        ]);
    }

    /**
     * Dérive 5 chiffres (00000 à 99999) d'un UUID et d'octets aléatoires.
     * Le résultat n'est pas une suite prévisible.
     */
    private function deriveNumericCode(): string
    {
        $material = Str::uuid()->toString().'|'.bin2hex(random_bytes(16));
        $digest = hash('sha256', $material, true);
        $unpacked = unpack('N', substr($digest, 0, 4));
        $number = ($unpacked[1] ?? random_int(0, 99999)) % 100000;

        return str_pad((string) $number, 5, '0', STR_PAD_LEFT);
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
