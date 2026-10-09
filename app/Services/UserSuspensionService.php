<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserSuspensionService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function present(User $actor): array
    {
        return User::query()
            ->where('organization_id', $actor->organization_id)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(function (User $member) use ($actor): array {
                $suspended = $member->isSuspended();
                $allowed = $this->mayChange($actor, $member);

                return [
                    'id' => $member->id,
                    'name' => $member->displayName(),
                    'email' => $member->email,
                    'role' => $member->role->value,
                    'role_label' => $member->role->label(),
                    'status' => $suspended ? 'suspended' : 'active',
                    'status_label' => $suspended ? 'Suspendu' : 'Actif',
                    'can_suspend' => $allowed && ! $suspended,
                    'can_reactivate' => $allowed && $suspended,
                ];
            })
            ->all();
    }

    public function suspend(User $actor, User $target): void
    {
        $this->assertCanChange($actor, $target);

        if ($target->isSuspended()) {
            throw new InvalidArgumentException('Ce compte est déjà suspendu.');
        }

        DB::transaction(function () use ($actor, $target): void {
            $target->forceFill([
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
                'remember_token' => null,
            ])->save();

            $this->invalidateSessions($target);

            $this->audit->record(
                $actor,
                'user.suspended',
                $target,
                ['suspended_at' => null],
                [
                    'suspended_at' => $target->suspended_at?->toIso8601String(),
                    'target_role' => $target->role->value,
                ],
                null,
                'success',
            );
        });
    }

    public function reactivate(User $actor, User $target): void
    {
        $this->assertCanChange($actor, $target);

        if (! $target->isSuspended()) {
            throw new InvalidArgumentException('Ce compte est déjà actif.');
        }

        DB::transaction(function () use ($actor, $target): void {
            $target->forceFill([
                'suspended_at' => null,
                'suspended_by' => null,
            ])->save();

            $this->audit->record(
                $actor,
                'user.reactivated',
                $target,
                null,
                ['target_role' => $target->role->value],
                null,
                'success',
            );
        });
    }

    public function mayChange(User $actor, User $target): bool
    {
        if ($actor->is($target) || $target->organization_id !== $actor->organization_id) {
            return false;
        }

        if (! $actor->hasPermission(Permission::ManageEmployees)) {
            return false;
        }

        if ($target->role === Role::BossPrincipal) {
            return false;
        }

        if ($target->role === Role::BossSecondaire && ! $actor->hasPermission(Permission::InviteBoss)) {
            return false;
        }

        return true;
    }

    private function assertCanChange(User $actor, User $target): void
    {
        if ($target->organization_id !== $actor->organization_id) {
            throw (new ModelNotFoundException)->setModel(User::class, [$target->getKey()]);
        }

        if ($this->mayChange($actor, $target)) {
            return;
        }

        $this->audit->safeRecord(
            $actor,
            'user.suspension_refused',
            $target,
            null,
            ['target_role' => $target->role->value],
            'Action non autorisée.',
            'denied',
        );

        throw new AuthorizationException('Action non autorisée.');
    }

    private function invalidateSessions(User $target): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = config('session.table', 'sessions');

        if (! is_string($table) || $table === '') {
            return;
        }

        DB::table($table)->where('user_id', $target->id)->delete();
    }
}
